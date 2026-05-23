<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SavTicket;
use App\Models\SavTicketComment;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Services\SavService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavController extends Controller
{
    public function __construct(
        private readonly SavService $savService,
    ) {}

    /**
     * Liste des tickets SAV
     * Admin voit toutes les boutiques, autres voient leur boutique
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $shopId = $request->input('shop_id');
        
        // Admin peut voir toutes les boutiques
        if ($isAdmin) {
            $query = SavTicket::withoutGlobalScope('shop')->with(['customer', 'product', 'creator', 'assignedUser', 'shop']);
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }
        } else {
            $query = SavTicket::with(['customer', 'product', 'creator', 'assignedUser']);
        }

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('issue_description', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Statistiques (avec filtre boutique pour admin)
        $statsQuery = $isAdmin ? SavTicket::withoutGlobalScope('shop') : SavTicket::query();
        if ($isAdmin && $shopId) {
            $statsQuery->where('shop_id', $shopId);
        }
        
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'open' => (clone $statsQuery)->open()->count(),
            'urgent' => (clone $statsQuery)->open()->urgent()->count(),
            'resolved_today' => (clone $statsQuery)->whereDate('resolved_at', today())->count(),
        ];
        
        $shops = $isAdmin ? Shop::active()->orderBy('name')->get() : collect();

        $tickets = $query->latest()->paginate(20);

        return view('sav.index', compact('tickets', 'stats', 'shops'));
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('first_name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $shopId = auth()->user()->shop_id;
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'caissiere']);
        })->where(function($q) use ($shopId) {
            $q->whereHas('roles', fn($r) => $r->where('name', 'admin'))
              ->orWhere('shop_id', $shopId);
        })->get();
        
        // Pré-remplir si vente spécifiée
        $sale = null;
        if ($request->filled('sale_id')) {
            $sale = Sale::with(['customer', 'items.product'])->find($request->sale_id);
        }

        // Pré-remplir si réparation spécifiée
        $repair = null;
        if ($request->filled('repair_id')) {
            $repair = Repair::with(['customer'])->find($request->repair_id);
        }

        return view('sav.create', compact('customers', 'products', 'users', 'sale', 'repair'));
    }

    /**
     * Enregistrer un nouveau ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'repair_id' => 'nullable|exists:repairs,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:return,exchange,warranty,repair_warranty,complaint,refund,other',
            'product_name' => 'nullable|string|max:255',
            'product_serial' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'issue_description' => 'required|string',
            'customer_request' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Vérifier que sale_id et repair_id appartiennent à la boutique courante
        $shopId = auth()->user()->shop_id;
        if (!empty($validated['sale_id'])) {
            $crossSale = Sale::find($validated['sale_id']);
            if (!$crossSale || $crossSale->shop_id !== $shopId) {
                return back()->withErrors(['sale_id' => 'Cette vente n\'appartient pas à votre boutique.'])->withInput();
            }
        }
        if (!empty($validated['repair_id'])) {
            $crossRepair = Repair::find($validated['repair_id']);
            if (!$crossRepair || $crossRepair->shop_id !== $shopId) {
                return back()->withErrors(['repair_id' => 'Cette réparation n\'appartient pas à votre boutique.'])->withInput();
            }
        }

        // Vérification de la garantie pour les types nécessitant une vente/réparation
        $warrantyTypes = ['return', 'exchange', 'warranty', 'repair_warranty', 'refund'];

        if (in_array($validated['type'], $warrantyTypes)) {
            if (!empty($validated['sale_id'])) {
                $sale = Sale::find($validated['sale_id']);
                if ($sale) {
                    $saleWarrantyDays = (int) Setting::get('sale_warranty_days', 7);
                    $saleDate         = $sale->completed_at ?? $sale->created_at;
                    $warrantyExpiry   = $saleDate->copy()->addDays($saleWarrantyDays);
                    if (now()->gt($warrantyExpiry)) {
                        return back()->with('error', "La garantie de cette vente a expiré le {$warrantyExpiry->format('d/m/Y')}. Aucun retour/échange n'est possible après {$saleWarrantyDays} jours.")->withInput();
                    }
                }
            }

            if (!empty($validated['repair_id'])) {
                $repair = Repair::find($validated['repair_id']);
                if ($repair && $repair->delivered_at) {
                    $repairWarrantyDays = (int) Setting::get('repair_warranty_days', 30);
                    $warrantyExpiry     = $repair->delivered_at->copy()->addDays($repairWarrantyDays);
                    if (now()->gt($warrantyExpiry)) {
                        return back()->with('error', "La garantie de cette réparation a expiré le {$warrantyExpiry->format('d/m/Y')}. Aucune réclamation n'est possible après {$repairWarrantyDays} jours.")->withInput();
                    }
                }
            }
        }

        $ticket = $this->savService->createTicket($validated, Auth::id());

        return redirect()->route('sav.show', $ticket)
            ->with('success', "Ticket SAV {$ticket->ticket_number} créé avec succès.");
    }

    /**
     * Afficher un ticket
     */
    public function show(SavTicket $sav)
    {
        $sav->load(['customer', 'sale.items.product', 'repair.customer', 'product', 'creator', 'assignedUser', 'comments.user']);
        
        $shopId = auth()->user()->shop_id;
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'caissiere']);
        })->where(function($q) use ($shopId) {
            $q->whereHas('roles', fn($r) => $r->where('name', 'admin'))
              ->orWhere('shop_id', $shopId);
        })->get();

        return view('sav.show', compact('sav', 'users'));
    }

    /**
     * Mettre à jour le statut
     */
    public function updateStatus(Request $request, SavTicket $sav)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,waiting_parts,resolved,closed,rejected',
            'resolution_type' => 'nullable|required_if:status,resolved,closed|in:repaired,exchanged,refunded,rejected,no_action,other',
            'resolution_notes' => 'nullable|string',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $oldStatus = $sav->status;
        $sav->status = $validated['status'];

        if (in_array($validated['status'], ['resolved', 'closed'])) {
            $sav->resolution_type = $validated['resolution_type'] ?? null;
            $sav->resolution_notes = $validated['resolution_notes'] ?? null;
            $sav->refund_amount = $validated['refund_amount'] ?? 0;
            
            if ($validated['status'] === 'resolved' && !$sav->resolved_at) {
                $sav->resolved_at = now();
            }
            if ($validated['status'] === 'closed') {
                $sav->closed_at = now();
            }
        }

        $sav->save();

        // Ajouter un commentaire automatique
        SavTicketComment::create([
            'sav_ticket_id' => $sav->id,
            'user_id' => Auth::id(),
            'comment' => "Statut changé de '{$oldStatus}' à '{$sav->status}'",
            'is_internal' => true,
        ]);

        ActivityLog::log('update', $sav, ['status' => $oldStatus], ['status' => $sav->status], "Changement statut SAV: {$sav->ticket_number}");

        return back()->with('success', 'Statut mis à jour avec succès.');
    }

    /**
     * Assigner un ticket
     */
    public function assign(Request $request, SavTicket $sav)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $sav->update($validated);

        if ($sav->assigned_to) {
            SavTicketComment::create([
                'sav_ticket_id' => $sav->id,
                'user_id' => Auth::id(),
                'comment' => "Ticket assigné à " . $sav->assignedUser->name,
                'is_internal' => true,
            ]);
        }

        return back()->with('success', 'Ticket assigné avec succès.');
    }

    /**
     * Ajouter un commentaire
     */
    public function addComment(Request $request, SavTicket $sav)
    {
        $validated = $request->validate([
            'comment' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $validated['sav_ticket_id'] = $sav->id;
        $validated['user_id'] = Auth::id();
        $validated['is_internal'] = $request->has('is_internal');

        SavTicketComment::create($validated);

        return back()->with('success', 'Commentaire ajouté.');
    }

    /**
     * Recherche rapide de vente pour SAV
     */
    public function searchSale(Request $request)
    {
        $search = $request->input('q');

        // Extraire le numéro de facture depuis un QR code (URL de suivi ou scan direct)
        if (str_contains($search, '/track/sale/')) {
            $search = last(explode('/', rtrim($search, '/')));
        }

        $saleWarrantyDays = (int) Setting::get('sale_warranty_days', 7);

        $sales = Sale::with(['customer', 'reseller', 'items.product'])
            ->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('phone', 'like', "%{$search}%");
                  });
            })
            ->limit(10)
            ->get()
            ->map(function($sale) use ($saleWarrantyDays) {
                $saleDate = $sale->completed_at ?? $sale->created_at;
                $warrantyExpiry = $saleDate->copy()->addDays($saleWarrantyDays);
                $warrantyValid = now()->lte($warrantyExpiry);
                $daysRemaining = now()->diffInDays($warrantyExpiry, false);

                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => $sale->total_amount,
                    'created_at' => $sale->created_at,
                    'completed_at' => $sale->completed_at,
                    'warranty_valid' => $warrantyValid,
                    'warranty_expiry' => $warrantyExpiry->format('d/m/Y'),
                    'warranty_days_remaining' => $warrantyValid ? $daysRemaining : 0,
                    'client_name' => $sale->client_name,
                    'customer' => $sale->customer ? [
                        'id'        => $sale->customer->id,
                        'full_name' => trim($sale->customer->full_name) ?: ($sale->customer->phone ?? 'Client'),
                        'phone'     => $sale->customer->phone,
                    ] : null,
                    'items' => $sale->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Produit',
                        'quantity' => $item->quantity,
                    ]),
                ];
            });

        return response()->json($sales);
    }

    /**
     * Recherche rapide de réparation pour SAV
     */
    public function searchRepair(Request $request)
    {
        $search = $request->input('q');

        // Extraire le numéro depuis un QR code scané
        // Format URL tracking : https://.../track/repair/REP-0001
        if (str_contains($search, '/track/repair/')) {
            $search = last(explode('/', rtrim($search, '/')));
        }
        // Format autocollant : "N°: REP-0001\nClient: ..." (avec \n littéral ou espace)
        if (preg_match('/N[°o]\s*:\s*([A-Z0-9\-]+)/i', $search, $m)) {
            $search = $m[1];
        }

        $repairWarrantyDays = (int) Setting::get('repair_warranty_days', 30);

        $repairs = Repair::with(['customer'])
            ->whereIn('status', ['delivered', 'repaired', 'ready_for_pickup']) // Réparations terminées ou livrées
            ->where(function($query) use ($search) {
                $query->where('repair_number', 'like', "%{$search}%")
                      ->orWhere('device_imei', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('phone', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                      });
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($repair) use ($repairWarrantyDays) {
                $warrantyValid = false;
                $warrantyExpiry = null;
                $daysRemaining = 0;
                
                // Calculer la garantie à partir de la date de livraison ou de réparation
                $warrantyStartDate = $repair->delivered_at ?? $repair->repaired_at ?? $repair->updated_at;
                if ($warrantyStartDate) {
                    $warrantyExpiry = $warrantyStartDate->copy()->addDays($repairWarrantyDays);
                    $warrantyValid = now()->lte($warrantyExpiry);
                    $daysRemaining = now()->diffInDays($warrantyExpiry, false);
                }
                
                return [
                    'id' => $repair->id,
                    'repair_number' => $repair->repair_number,
                    'device_brand' => $repair->device_brand,
                    'device_model' => $repair->device_model,
                    'status' => $repair->status,
                    'status_label' => $repair->status_label,
                    'delivered_at' => $repair->delivered_at,
                    'repaired_at' => $repair->repaired_at,
                    'warranty_valid' => $warrantyValid,
                    'warranty_expiry' => $warrantyExpiry?->format('d/m/Y'),
                    'warranty_days_remaining' => $warrantyValid ? max(0, $daysRemaining) : 0,
                    'customer' => $repair->customer ? [
                        'name'  => trim($repair->customer->full_name ?? '') ?: ($repair->customer->phone ?? 'Client'),
                        'phone' => $repair->customer->phone,
                    ] : null,
                ];
            });

        return response()->json($repairs);
    }

    /**
     * Tableau de bord SAV
     */
    public function dashboard()
    {
        $stats = [
            'total_open' => SavTicket::open()->count(),
            'urgent' => SavTicket::open()->where('priority', 'urgent')->count(),
            'high' => SavTicket::open()->where('priority', 'high')->count(),
            'waiting_customer' => SavTicket::where('status', 'waiting_customer')->count(),
            'resolved_this_month' => SavTicket::whereMonth('resolved_at', now()->month)->count(),
            'avg_resolution_time' => SavTicket::whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        // Tickets par type
        $byType = SavTicket::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        // Tickets ouverts récents
        $recentOpen = SavTicket::open()
            ->with(['customer', 'assignedUser'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->latest()
            ->limit(10)
            ->get();

        // Tickets non assignés
        $unassigned = SavTicket::open()
            ->whereNull('assigned_to')
            ->count();

        return view('sav.dashboard', compact('stats', 'byType', 'recentOpen', 'unassigned'));
    }

    /**
     * Formulaire de retour en stock
     */
    public function stockReturnForm(SavTicket $ticket)
    {
        $ticket->load(['customer', 'product', 'sale.items.product']);
        
        // Récupérer les produits concernés et les quantités de la facture
        $products = collect();
        $invoiceQuantities = []; // product_id => quantité sur la facture

        if ($ticket->product_id) {
            $products->push($ticket->product);
            $invoiceQuantities[$ticket->product_id] = 1;
        } elseif ($ticket->sale_id) {
            foreach ($ticket->sale->items as $item) {
                if ($item->product) {
                    $products->push($item->product);
                    $invoiceQuantities[$item->product_id] = (int) $item->quantity;
                }
            }
        }

        return view('sav.stock-return', compact('ticket', 'products', 'invoiceQuantities'));
    }

    /**
     * Effectuer le retour en stock
     */
    public function processStockReturn(Request $request, SavTicket $ticket)
    {
        $validated = $request->validate([
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|integer|min:1',
            'products.*.condition'  => 'required|in:new,good,damaged,defective',
            'refund_damaged'        => 'boolean',
            'refund_method'         => 'nullable|in:cash,mobile_money,card',
            'return_notes'          => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->savService->processStockReturn($ticket, $validated, Auth::id());

            $message = "Retour en stock effectué avec succès. {$result['total_returned']} article(s) remis en stock.";
            if ($result['total_refund'] > 0) {
                $message .= " Remboursement de " . number_format($result['total_refund'], 0, ',', ' ') . " F enregistré.";
            }


            return redirect()->route('sav.show', $ticket)->with('success', $message);

        } catch (\DomainException|\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du retour en stock'));
        }
    }

    /**
     * Annuler un retour en stock
     */
    public function cancelStockReturn(SavTicket $ticket)
    {
        try {
            $refundAmount = (float) ($ticket->refund_amount ?? 0);
            $this->savService->cancelStockReturn($ticket, Auth::id());

            $message = 'Le retour en stock a été annulé.';
            if ($refundAmount > 0) {
                $message .= " Le remboursement de " . number_format($refundAmount, 0, ',', ' ') . " F a été annulé.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'sav.annulation'));
        }
    }

    /**
     * Formulaire de remplacement de pièce pour SAV réparation
     */
    public function replacePartForm(SavTicket $ticket)
    {
        if (!$ticket->repair_id) {
            return back()->with('error', 'Ce ticket SAV n\'est pas lié à une réparation.');
        }

        $ticket->load(['repair.parts.product.category', 'repair.technician']);
        
        // Pièces de rechange disponibles
        $spareParts = Product::with('category')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('sav.replace-part', compact('ticket', 'spareParts'));
    }

    /**
     * Traiter le remplacement de pièce défectueuse
     * Déduit le coût de la pièce défectueuse du CA du technicien
     */
    public function processReplacePart(Request $request, SavTicket $ticket)
    {
        if (!$ticket->repair_id) {
            return back()->with('error', 'Ce ticket SAV n\'est pas lié à une réparation.');
        }

        $validated = $request->validate([
            'original_repair_part_id' => 'required|exists:repair_parts,id',
            'replacement_product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $ticket->load(['repair.technician', 'repair.parts']);

        // Vérifier que la pièce originale appartient à cette réparation
        $originalPart = $ticket->repair->parts()->find($validated['original_repair_part_id']);
        if (!$originalPart) {
            return back()->with('error', 'Cette pièce ne fait pas partie de la réparation.');
        }

        $replacementProduct = Product::findOrFail($validated['replacement_product_id']);

        // Vérifier le stock
        if ($replacementProduct->quantity_in_stock < $validated['quantity']) {
            return back()->with('error', 'Stock insuffisant pour la pièce de remplacement.');
        }

        try {
            $replacedPart = $this->savService->processReplacePart($ticket, $validated, Auth::id());
            $cost = number_format((float) $replacedPart->defective_part_cost, 0, ',', ' ');

            return redirect()->route('sav.show', $ticket)
                ->with('success', "Pièce remplacée avec succès. {$cost} F déduits du CA du technicien.");

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du remplacement'));
        }
    }
}
