<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des réparations (création et paiement) - Caissière
 */
class RepairController extends Controller
{
    public function index(Request $request)
    {
        $query = Repair::with(['customer', 'technician', 'creator']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            // Gérer le filtre "ready" qui inclut plusieurs statuts
            if ($status === 'ready') {
                $query->whereIn('status', ['repaired', 'ready_for_pickup']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('technician')) {
            $query->where('technician_id', $request->technician);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $repairs = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'in_repair' => Repair::where('status', 'in_repair')->count(),
            'ready' => Repair::whereIn('status', ['repaired', 'ready_for_pickup'])->count(),
            'today' => Repair::whereDate('created_at', today())->count(),
            'delivered_today' => Repair::where('status', 'delivered')->whereDate('delivered_at', today())->count(),
            // CA Réparations = main d'œuvre uniquement
            'revenue_today' => Repair::whereDate('created_at', today())->sum('labor_cost'),
        ];

        // Techniciens pour le filtre
        $technicians = User::role('technicien')->get();
        
        // Méthodes de paiement pour le modal de livraison
        $paymentMethods = \App\Models\PaymentMethod::active()->ordered()->get();

        return view('cashier.repairs.index', compact('repairs', 'stats', 'technicians', 'paymentMethods'));
    }

    /**
     * Formulaire de création de réparation
     */
    public function create()
    {
        $customers = Customer::active()->orderBy('first_name')->get();
        $technicians = User::technicians()->active()->get();
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        
        // Pièces de rechange disponibles en stock
        $spareParts = \App\Models\Product::where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('cashier.repairs.create', compact('customers', 'technicians', 'paymentMethods', 'spareParts'));
    }

    /**
     * Enregistrer une nouvelle réparation (workflow simplifié : diagnostic + paiement immédiat)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'device_type' => 'required|string|max:100',
            'device_brand' => 'required|string|max:100',
            'device_model' => 'required|string|max:100',
            'device_imei' => 'nullable|string|max:50',
            'device_password' => 'nullable|string|max:50',
            'device_condition' => 'nullable|string',
            'accessories_received' => 'nullable|string',
            'reported_issue' => 'required|string',
            'diagnosis' => 'required|string',
            'repair_notes' => 'nullable|string',
            'technician_id' => 'nullable|exists:users,id',
            'final_status' => 'required|in:delivered,ready_for_pickup,in_repair',
            'labor_cost' => 'nullable|numeric|min:0',
            'final_cost' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'amount_given' => 'nullable|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'estimated_completion_date' => 'required|date',
            'print_ticket' => 'nullable',
            // Pièces de rechange
            'parts' => 'nullable|array',
            'parts.*.product_id' => 'required_with:parts|exists:products,id',
            'parts.*.quantity' => 'required_with:parts|integer|min:1',
            'parts.*.unit_price' => 'required_with:parts|numeric|min:0',
        ]);

        // Vérifier la caisse si paiement > 0
        $cashRegister = null;
        if ($validated['amount_paid'] > 0) {
            $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
            if (!$cashRegister) {
                return back()->withInput()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
            }
        }

        $paymentMethod = \App\Models\PaymentMethod::findOrFail($validated['payment_method_id']);

        DB::beginTransaction();
        try {
            // Calculer le coût des pièces
            $partsCost = 0;
            if (!empty($validated['parts'])) {
                foreach ($validated['parts'] as $partData) {
                    $partsCost += $partData['quantity'] * $partData['unit_price'];
                }
            }
            
            $laborCost = $validated['labor_cost'] ?? 0;
            $finalCost = $laborCost + $partsCost;

            // Créer la réparation avec diagnostic complet
            $repair = Repair::create([
                'customer_id' => $validated['customer_id'],
                'created_by' => auth()->id(),
                'technician_id' => $validated['technician_id'] ?? auth()->id(),
                'device_type' => $validated['device_type'],
                'device_brand' => $validated['device_brand'],
                'device_model' => $validated['device_model'],
                'device_imei' => $validated['device_imei'] ?? null,
                'device_password' => $validated['device_password'] ?? null,
                'device_condition' => $validated['device_condition'] ?? null,
                'accessories_received' => !empty($validated['accessories_received']) ? [$validated['accessories_received']] : null,
                'reported_issue' => $validated['reported_issue'],
                'diagnosis' => $validated['diagnosis'],
                'repair_notes' => $validated['repair_notes'] ?? null,
                'status' => $validated['final_status'],
                'estimated_cost' => $finalCost,
                'final_cost' => $finalCost,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'amount_paid' => $validated['amount_paid'],
                'payment_method' => $paymentMethod->code ?? $paymentMethod->type ?? 'cash',
                'estimated_completion_date' => $validated['estimated_completion_date'],
                'paid_at' => $validated['amount_paid'] > 0 ? now() : null,
                'diagnosis_at' => now(),
                'repaired_at' => in_array($validated['final_status'], ['delivered', 'ready_for_pickup']) ? now() : null,
                'delivered_at' => $validated['final_status'] === 'delivered' ? now() : null,
            ]);

            // Ajouter les pièces de rechange
            if (!empty($validated['parts'])) {
                foreach ($validated['parts'] as $partData) {
                    $product = \App\Models\Product::findOrFail($partData['product_id']);
                    
                    // Vérifier le stock
                    if ($product->quantity_in_stock < $partData['quantity']) {
                        throw new \Exception("Stock insuffisant pour {$product->name}");
                    }
                    
                    // Créer la pièce de réparation
                    \App\Models\RepairPart::create([
                        'repair_id' => $repair->id,
                        'product_id' => $product->id,
                        'quantity' => $partData['quantity'],
                        'unit_cost' => $partData['unit_price'],
                        'total_cost' => $partData['quantity'] * $partData['unit_price'],
                    ]);
                    
                    // Calculer les quantités avant/après
                    $quantityBefore = $product->quantity_in_stock;
                    $quantityAfter = $quantityBefore - $partData['quantity'];
                    
                    // Décrémenter le stock
                    $product->decrement('quantity_in_stock', $partData['quantity']);
                    
                    // Enregistrer le mouvement de stock
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'repair_usage',
                        'quantity' => -$partData['quantity'],
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'reason' => "Pièce utilisée pour réparation #{$repair->repair_number}",
                        'moveable_type' => Repair::class,
                        'moveable_id' => $repair->id,
                    ]);
                }
            }

            // Enregistrer la transaction de caisse si paiement > 0
            if ($validated['amount_paid'] > 0 && $cashRegister) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_REPAIR,
                    $validated['amount_paid'],
                    $paymentMethod->code ?? $paymentMethod->type ?? 'cash',
                    $repair,
                    "Réparation #{$repair->repair_number} - {$repair->device_brand} {$repair->device_model}"
                );
            }

            ActivityLog::log('create', $repair, null, $repair->toArray(), "Réparation #{$repair->repair_number} - Diagnostic + Paiement");

            // Envoyer notification aux techniciens
            $notificationService = app(NotificationService::class);
            $notificationService->repairCreated($repair);

            DB::commit();

            // Calculer la monnaie rendue
            $amountGiven = $validated['amount_given'] ?? $validated['amount_paid'];
            $change = max(0, $amountGiven - $validated['amount_paid']);

            // Toujours ouvrir le ticket automatiquement après création
            return redirect()->route('cashier.repairs.ticket', [
                'repair' => $repair, 
                'auto' => 1,
                'amount_given' => $amountGiven,
                'change' => $change
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
        }
    }

    public function show(Repair $repair)
    {
        $repair->load(['customer', 'technician', 'creator', 'parts.product', 'cashTransactions']);
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        
        return view('cashier.repairs.show', compact('repair', 'paymentMethods'));
    }

    public function edit(Repair $repair)
    {
        if (!$repair->canBeEdited()) {
            return back()->with('error', 'Cette réparation ne peut plus être modifiée.');
        }

        $customers = Customer::active()->orderBy('first_name')->get();
        $technicians = User::technicians()->active()->get();

        return view('cashier.repairs.edit', compact('repair', 'customers', 'technicians'));
    }

    public function update(Request $request, Repair $repair)
    {
        if (!$repair->canBeEdited()) {
            return back()->with('error', 'Cette réparation ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'device_type' => 'required|string|max:100',
            'device_brand' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'device_imei' => 'nullable|string|max:50',
            'device_password' => 'nullable|string|max:50',
            'device_condition' => 'nullable|string',
            'accessories_received' => 'nullable|array',
            'reported_issue' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'technician_id' => 'nullable|exists:users,id',
        ]);

        $oldValues = $repair->toArray();
        $repair->update($validated);

        ActivityLog::log('update', $repair, $oldValues, $repair->toArray(), "Modification réparation #{$repair->repair_number}");

        return redirect()->route('cashier.repairs.show', $repair)
            ->with('success', 'Réparation mise à jour.');
    }

    /**
     * Formulaire de paiement
     */
    public function paymentForm(Repair $repair)
    {
        if ($repair->status !== Repair::STATUS_PENDING_PAYMENT) {
            return back()->with('error', 'Cette réparation a déjà été payée.');
        }

        return view('cashier.repairs.payment', compact('repair'));
    }

    /**
     * Enregistrer le paiement
     */
    public function processPayment(Request $request, Repair $repair)
    {
        if ($repair->status !== Repair::STATUS_PENDING_PAYMENT) {
            return back()->with('error', 'Cette réparation a déjà été payée.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,card',
        ]);

        // Vérifier la caisse
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir la caisse.');
        }

        DB::beginTransaction();
        try {
            // Mettre à jour la réparation
            $repair->markAsPaid($validated['payment_method'], $validated['amount']);

            // Enregistrer la transaction de caisse
            $cashRegister->addTransaction(
                CashTransaction::TYPE_INCOME,
                CashTransaction::CATEGORY_REPAIR,
                $validated['amount'],
                $validated['payment_method'],
                $repair,
                "Paiement réparation #{$repair->repair_number}"
            );

            ActivityLog::log('payment', $repair, null, [
                'amount' => $validated['amount'],
                'method' => $validated['payment_method'],
            ], "Paiement réparation #{$repair->repair_number}");

            DB::commit();

            return redirect()->route('cashier.repairs.show', $repair)
                ->with('success', 'Paiement enregistré. La réparation est prête pour le diagnostic.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Payer l'acompte d'une réparation (version simplifiée depuis la page show)
     */
    public function pay(Request $request, Repair $repair)
    {
        if ($repair->status !== Repair::STATUS_PENDING_PAYMENT) {
            return back()->with('error', 'Cette réparation a déjà été payée.');
        }

        $validated = $request->validate([
            'deposit_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        // Vérifier la caisse
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
        }

        $paymentMethod = \App\Models\PaymentMethod::findOrFail($validated['payment_method_id']);

        DB::beginTransaction();
        try {
            // Mettre à jour la réparation
            $repair->update([
                'status' => Repair::STATUS_PAID_PENDING_DIAGNOSIS,
                'deposit_amount' => $validated['deposit_amount'],
                'payment_method' => $paymentMethod->code ?? $paymentMethod->type ?? 'cash',
                'paid_at' => now(),
            ]);

            // Enregistrer la transaction de caisse si montant > 0
            if ($validated['deposit_amount'] > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_REPAIR,
                    $validated['deposit_amount'],
                    $paymentMethod->code ?? $paymentMethod->type ?? 'cash',
                    $repair,
                    "Acompte réparation #{$repair->repair_number}"
                );
            }

            ActivityLog::log('payment', $repair, null, [
                'deposit_amount' => $validated['deposit_amount'],
                'payment_method' => $paymentMethod->name,
            ], "Acompte réparation #{$repair->repair_number}");

            DB::commit();

            // Rediriger directement vers le ticket pour impression automatique
            return redirect()->route('cashier.repairs.ticket', ['repair' => $repair, 'auto' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Livrer la réparation et encaisser le solde
     * Marque aussi les ventes de pièces comme payées
     */
    public function deliver(Request $request, Repair $repair)
    {
        if (!in_array($repair->status, [Repair::STATUS_REPAIRED, Repair::STATUS_READY_FOR_PICKUP])) {
            return back()->with('error', 'Cette réparation ne peut pas être livrée.');
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $paidAmount = $validated['paid_amount'];

        // Si paiement > 0, vérifier la caisse
        if ($paidAmount > 0) {
            $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
            if (!$cashRegister) {
                return back()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
            }

            $paymentMethod = \App\Models\PaymentMethod::findOrFail($validated['payment_method_id']);

            // Enregistrer la transaction de caisse
            $cashRegister->addTransaction(
                CashTransaction::TYPE_INCOME,
                CashTransaction::CATEGORY_REPAIR,
                $paidAmount,
                $paymentMethod->code ?? $paymentMethod->type ?? 'cash',
                $repair,
                "Solde réparation #{$repair->repair_number} - Livraison"
            );

            // Mettre à jour le montant payé total
            $repair->amount_paid = ($repair->amount_paid ?? 0) + $paidAmount;
        }

        // Marquer les ventes de pièces comme payées
        $partsSales = \App\Models\Sale::where('repair_id', $repair->id)
            ->where('is_repair_parts', true)
            ->where('payment_status', 'credit')
            ->get();
        
        foreach ($partsSales as $partSale) {
            $partSale->update([
                'amount_paid' => $partSale->total_amount,
                'amount_due' => 0,
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method_id'] 
                    ? (\App\Models\PaymentMethod::find($validated['payment_method_id'])->code ?? 'cash')
                    : 'cash',
                'completed_at' => now(),
            ]);
        }

        // Mettre à jour le statut
        $repair->status = Repair::STATUS_DELIVERED;
        $repair->delivered_at = now();
        $repair->save();

        ActivityLog::log('update', $repair, null, [
            'status' => 'delivered',
            'paid_amount' => $paidAmount,
            'parts_sales_paid' => $partsSales->count(),
        ], "Livraison réparation #{$repair->repair_number}");

        return redirect()->route('cashier.repairs.ticket', ['repair' => $repair])
            ->with('success', 'Réparation livrée au client avec succès !');
    }

    /**
     * Imprimer le reçu
     */
    public function printReceipt(Repair $repair)
    {
        $repair->load(['customer', 'parts.product']);
        return view('cashier.repairs.receipt', compact('repair'));
    }

    /**
     * Imprimer le ticket de dépôt (format thermique avec QR code)
     */
    public function printTicket(Repair $repair)
    {
        $repair->load(['customer', 'technician', 'creator', 'parts.product']);
        
        // Utiliser le service d'impression thermique
        $thermalService = app(\App\Services\ThermalPrinterService::class);
        
        $ticketData = $thermalService->getRepairTicketData($repair, [
            'amount_given' => request('amount_given', $repair->amount_paid),
            'change' => request('change', 0),
        ]);

        // Utiliser le ticket thermique optimisé
        if (request('format') === 'thermal' || request()->has('auto')) {
            return view('cashier.repairs.thermal-ticket', compact('repair', 'ticketData'));
        }

        // Sinon utiliser le ticket standard
        $settings = [
            'company_name' => \App\Models\Setting::get('company_name', 'EGREGORE BUSINESS'),
            'company_address' => \App\Models\Setting::get('company_address', ''),
            'company_phone' => \App\Models\Setting::get('company_phone', ''),
            'receipt_footer' => \App\Models\Setting::get('receipt_footer', 'Merci de votre confiance !'),
        ];
        return view('cashier.repairs.ticket', compact('repair', 'settings', 'ticketData'));
    }

    /**
     * Imprimer le bon de retrait
     */
    public function printDeliveryNote(Repair $repair)
    {
        $repair->load(['customer', 'parts.product']);
        return view('cashier.repairs.delivery-note', compact('repair'));
    }
}
