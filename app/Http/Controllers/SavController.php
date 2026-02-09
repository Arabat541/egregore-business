<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavTicket;
use App\Models\SavTicketComment;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavController extends Controller
{
    /**
     * Liste des tickets SAV
     * Admin voit toutes les boutiques, autres voient leur boutique
     */
    public function index(Request $request)
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $shopId = $request->get('shop_id');
        
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
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'caissiere']);
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

        $validated['ticket_number'] = SavTicket::generateTicketNumber();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'open';

        // Si c'est une garantie réparation, récupérer les infos de la réparation
        if ($validated['type'] === 'repair_warranty' && !empty($validated['repair_id'])) {
            $repair = Repair::find($validated['repair_id']);
            if ($repair) {
                $validated['customer_id'] = $validated['customer_id'] ?? $repair->customer_id;
                $validated['product_name'] = $validated['product_name'] ?? "{$repair->device_brand} {$repair->device_model}";
                $validated['product_serial'] = $validated['product_serial'] ?? $repair->device_imei;
                $validated['purchase_date'] = $validated['purchase_date'] ?? $repair->delivered_at?->format('Y-m-d');
            }
        }

        $ticket = SavTicket::create($validated);

        ActivityLog::log('create', $ticket, null, $ticket->toArray(), "Création ticket SAV: {$ticket->ticket_number}");

        // Notifier les admins et caissières du nouveau ticket SAV
        $notificationService = app(NotificationService::class);
        $type = $validated['priority'] === 'urgent' ? Notification::TYPE_SAV_URGENT : Notification::TYPE_SAV_NEW;
        $notificationService->notifyRole(
            'admin',
            $type,
            $validated['priority'] === 'urgent' ? '🚨 Ticket SAV Urgent' : 'Nouveau Ticket SAV',
            "#{$ticket->ticket_number} - {$ticket->type_name}",
            route('sav.show', $ticket),
            $ticket
        );

        return redirect()->route('sav.show', $ticket)
            ->with('success', "Ticket SAV {$ticket->ticket_number} créé avec succès.");
    }

    /**
     * Afficher un ticket
     */
    public function show(SavTicket $sav)
    {
        $sav->load(['customer', 'sale.items.product', 'repair.customer', 'product', 'creator', 'assignedUser', 'comments.user']);
        
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'caissiere']);
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
            'user_id' => auth()->id(),
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
                'user_id' => auth()->id(),
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
        $validated['user_id'] = auth()->id();
        $validated['is_internal'] = $request->has('is_internal');

        SavTicketComment::create($validated);

        return back()->with('success', 'Commentaire ajouté.');
    }

    /**
     * Recherche rapide de vente pour SAV
     */
    public function searchSale(Request $request)
    {
        $search = $request->get('q');
        
        $sales = Sale::with(['customer', 'items.product'])
            ->where('invoice_number', 'like', "%{$search}%")
            ->orWhereHas('customer', function($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        return response()->json($sales);
    }

    /**
     * Recherche rapide de réparation pour SAV
     */
    public function searchRepair(Request $request)
    {
        $search = $request->get('q');
        
        $repairs = Repair::with(['customer'])
            ->where('status', 'delivered') // Seulement les réparations livrées
            ->where(function($query) use ($search) {
                $query->where('repair_number', 'like', "%{$search}%")
                      ->orWhere('device_imei', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('phone', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                      });
            })
            ->orderBy('delivered_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($repair) {
                return [
                    'id' => $repair->id,
                    'repair_number' => $repair->repair_number,
                    'device_brand' => $repair->device_brand,
                    'device_model' => $repair->device_model,
                    'status' => $repair->status,
                    'status_label' => $repair->status_label,
                    'delivered_at' => $repair->delivered_at,
                    'customer' => $repair->customer ? [
                        'name' => $repair->customer->name ?? $repair->customer->full_name ?? 'Client',
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
        
        // Récupérer les produits concernés
        $products = collect();
        
        if ($ticket->product_id) {
            $products->push($ticket->product);
        } elseif ($ticket->sale_id) {
            $products = $ticket->sale->items->map(fn($item) => $item->product)->filter();
        }
        
        return view('sav.stock-return', compact('ticket', 'products'));
    }

    /**
     * Effectuer le retour en stock
     */
    public function processStockReturn(Request $request, SavTicket $ticket)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.condition' => 'required|in:new,good,damaged,defective',
            'return_notes' => 'nullable|string|max:1000',
        ]);

        if ($ticket->stock_returned) {
            return back()->with('error', 'Le retour en stock a déjà été effectué pour ce ticket.');
        }

        DB::beginTransaction();
        try {
            $totalReturned = 0;
            $totalRefundAmount = 0;
            $returnDetails = [];

            foreach ($validated['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];
                $condition = $productData['condition'];

                // Calculer le montant du remboursement basé sur la vente originale
                $refundAmount = 0;
                if ($ticket->sale) {
                    // Chercher le prix de vente original dans les items de la vente
                    $saleItem = SaleItem::where('sale_id', $ticket->sale_id)
                        ->where('product_id', $product->id)
                        ->first();
                    
                    if ($saleItem) {
                        $refundAmount = $saleItem->unit_price * $quantity;
                    } else {
                        // Si pas trouvé, utiliser le prix de vente actuel
                        $refundAmount = $product->selling_price * $quantity;
                    }
                } elseif ($ticket->repair) {
                    // Pour les réparations, utiliser le coût des pièces
                    $refundAmount = ($product->purchase_price ?? 0) * $quantity;
                }

                // Ne remettre en stock que les produits en bon état ou neuf
                if (in_array($condition, ['new', 'good'])) {
                    $quantityBefore = $product->quantity_in_stock;
                    
                    // Créer le mouvement de stock
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'entry',
                        'quantity' => $quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityBefore + $quantity,
                        'moveable_type' => SavTicket::class,
                        'moveable_id' => $ticket->id,
                        'reason' => "Retour SAV #{$ticket->ticket_number} - État: {$condition}",
                    ]);

                    // Mettre à jour le stock du produit
                    $product->increment('quantity_in_stock', $quantity);
                    $totalReturned += $quantity;
                    $totalRefundAmount += $refundAmount;

                    $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - " . number_format($refundAmount, 0, ',', ' ') . " F";
                } else {
                    // Produit endommagé ou défectueux - ne pas remettre en stock mais quand même rembourser si applicable
                    if ($validated['refund_damaged'] ?? false) {
                        $totalRefundAmount += $refundAmount;
                        $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - Remboursé: " . number_format($refundAmount, 0, ',', ' ') . " F - NON remis en stock";
                    } else {
                        $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - NON remis en stock, NON remboursé";
                    }
                }
            }

            // Créer la transaction de caisse négative (déduction du CA)
            if ($totalRefundAmount > 0) {
                // Trouver la caisse ouverte (status = 'open')
                $cashRegister = CashRegister::where('status', 'open')->first();
                
                if ($cashRegister) {
                    // Montant NÉGATIF pour que ça soit comptabilisé comme une sortie
                    CashTransaction::create([
                        'cash_register_id' => $cashRegister->id,
                        'user_id' => auth()->id(),
                        'type' => CashTransaction::TYPE_EXPENSE, // Sortie de caisse
                        'category' => CashTransaction::CATEGORY_SAV_REFUND,
                        'amount' => -$totalRefundAmount, // Montant négatif
                        'payment_method' => $validated['refund_method'] ?? 'cash',
                        'description' => "Remboursement SAV #{$ticket->ticket_number} - {$totalReturned} article(s)",
                        'transactionable_type' => SavTicket::class,
                        'transactionable_id' => $ticket->id,
                    ]);

                    // Mettre à jour le solde théorique de la caisse (expected_balance)
                    $cashRegister->decrement('expected_balance', $totalRefundAmount);
                }

                // Mettre à jour le statut de paiement de la vente originale si applicable
                if ($ticket->sale) {
                    $sale = $ticket->sale;
                    $sale->update([
                        'refund_amount' => ($sale->refund_amount ?? 0) + $totalRefundAmount,
                        'notes' => ($sale->notes ? $sale->notes . "\n" : '') . 
                            "[" . now()->format('d/m/Y H:i') . "] Remboursement SAV: " . number_format($totalRefundAmount, 0, ',', ' ') . " F",
                    ]);
                }
            }

            // Mettre à jour le ticket SAV
            $ticket->update([
                'stock_returned' => true,
                'stock_returned_at' => now(),
                'stock_returned_by' => auth()->id(),
                'quantity_returned' => $totalReturned,
                'refund_amount' => $totalRefundAmount,
                'return_notes' => $validated['return_notes'] ?? implode(', ', $returnDetails),
            ]);

            // Log d'activité
            ActivityLog::log(
                'stock_return',
                $ticket,
                null,
                [
                    'products' => $returnDetails, 
                    'total_returned' => $totalReturned,
                    'refund_amount' => $totalRefundAmount,
                ],
                "Retour en stock SAV #{$ticket->ticket_number} - Remboursement: " . number_format($totalRefundAmount, 0, ',', ' ') . " F"
            );

            // Ajouter un commentaire au ticket
            $commentText = "🔄 Retour en stock effectué:\n" . implode("\n", $returnDetails);
            if ($totalRefundAmount > 0) {
                $commentText .= "\n\n💰 Montant remboursé: " . number_format($totalRefundAmount, 0, ',', ' ') . " F";
            }
            
            SavTicketComment::create([
                'sav_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment' => $commentText,
                'is_internal' => true,
            ]);

            DB::commit();

            $message = "Retour en stock effectué avec succès. {$totalReturned} article(s) remis en stock.";
            if ($totalRefundAmount > 0) {
                $message .= " Remboursement de " . number_format($totalRefundAmount, 0, ',', ' ') . " F enregistré.";
            }

            return redirect()
                ->route('sav.show', $ticket)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors du retour en stock: ' . $e->getMessage());
        }
    }

    /**
     * Annuler un retour en stock
     */
    public function cancelStockReturn(SavTicket $ticket)
    {
        if (!$ticket->stock_returned) {
            return back()->with('error', 'Aucun retour en stock à annuler.');
        }

        DB::beginTransaction();
        try {
            // Récupérer les mouvements de stock liés à ce ticket
            $movements = StockMovement::where('moveable_type', SavTicket::class)
                ->where('moveable_id', $ticket->id)
                ->where('type', 'entry')
                ->get();

            foreach ($movements as $movement) {
                // Récupérer le produit pour calculer quantity_before
                $product = Product::find($movement->product_id);
                $quantityBefore = $product ? $product->quantity_in_stock : 0;
                
                // Créer un mouvement inverse
                StockMovement::create([
                    'product_id' => $movement->product_id,
                    'user_id' => auth()->id(),
                    'type' => 'exit',
                    'quantity' => $movement->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityBefore - $movement->quantity,
                    'moveable_type' => SavTicket::class,
                    'moveable_id' => $ticket->id,
                    'reason' => "Annulation retour SAV #{$ticket->ticket_number}",
                ]);

                // Déduire du stock
                if ($product) {
                    $product->decrement('quantity_in_stock', $movement->quantity);
                }
            }

            // Annuler la transaction de remboursement si elle existe
            $refundAmount = $ticket->refund_amount ?? 0;
            if ($refundAmount > 0) {
                // Trouver et supprimer la transaction de remboursement
                $refundTransaction = CashTransaction::where('transactionable_type', SavTicket::class)
                    ->where('transactionable_id', $ticket->id)
                    ->where('category', 'sav_refund')
                    ->first();

                if ($refundTransaction) {
                    // Restaurer le solde théorique de la caisse
                    $cashRegister = CashRegister::find($refundTransaction->cash_register_id);
                    if ($cashRegister) {
                        $cashRegister->increment('expected_balance', $refundAmount);
                    }
                    
                    // Supprimer la transaction
                    $refundTransaction->delete();
                }

                // Restaurer le montant de remboursement sur la vente si applicable
                if ($ticket->sale) {
                    $sale = $ticket->sale;
                    $sale->update([
                        'refund_amount' => max(0, ($sale->refund_amount ?? 0) - $refundAmount),
                        'notes' => ($sale->notes ? $sale->notes . "\n" : '') . 
                            "[" . now()->format('d/m/Y H:i') . "] Annulation remboursement SAV: " . number_format($refundAmount, 0, ',', ' ') . " F",
                    ]);
                }
            }

            // Réinitialiser les champs de retour
            $ticket->update([
                'stock_returned' => false,
                'stock_returned_at' => null,
                'stock_returned_by' => null,
                'quantity_returned' => 0,
                'refund_amount' => 0,
                'return_notes' => null,
            ]);

            // Ajouter un commentaire
            $commentText = "❌ Retour en stock annulé";
            if ($refundAmount > 0) {
                $commentText .= "\n💰 Remboursement annulé: " . number_format($refundAmount, 0, ',', ' ') . " F";
            }
            
            SavTicketComment::create([
                'sav_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment' => $commentText,
                'is_internal' => true,
            ]);

            ActivityLog::log(
                'stock_return_cancelled', 
                $ticket, 
                null, 
                ['refund_cancelled' => $refundAmount], 
                "Annulation retour stock SAV #{$ticket->ticket_number}" . ($refundAmount > 0 ? " - Remboursement annulé: " . number_format($refundAmount, 0, ',', ' ') . " F" : "")
            );

            DB::commit();

            $message = 'Le retour en stock a été annulé.';
            if ($refundAmount > 0) {
                $message .= " Le remboursement de " . number_format($refundAmount, 0, ',', ' ') . " F a été annulé.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }
}
