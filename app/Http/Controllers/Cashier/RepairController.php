<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Setting;
use App\Models\User;
use App\Http\Requests\Cashier\StoreRepairRequest;
use App\Services\RepairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestion des réparations (création et paiement) - Caissière
 */
class RepairController extends Controller
{
    public function __construct(
        private readonly RepairService $repairService,
    ) {}

    public function index(Request $request)
    {
        $query = Repair::with(['customer', 'technician', 'creator', 'parts']);

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

        $perPage = in_array((int) $request->input('per_page'), [10, 20, 50, 100]) ? (int) $request->input('per_page') : 20;
        $repairs = $query->latest()->paginate($perPage)->withQueryString();

        // Statistiques
        $stats = [
            'in_repair' => Repair::where('status', 'in_repair')->count(),
            'ready' => Repair::whereIn('status', ['repaired', 'ready_for_pickup'])->count(),
            'today' => Repair::whereDate('created_at', today())->count(),
            'delivered_today' => Repair::where('status', 'delivered')->whereDate('delivered_at', today())->count(),
            // CA Réparations = main d'œuvre uniquement
            'revenue_today' => Repair::whereDate('created_at', today())->sum('labor_cost'),
        ];

        // Techniciens pour le filtre (uniquement ceux de la même boutique)
        $technicians = User::role('technicien')->where('shop_id', Auth::user()->shop_id)->get();
        
        // Méthodes de paiement pour le modal de livraison
        $paymentMethods = PaymentMethod::active()->ordered()->get();

        return view('cashier.repairs.index', compact('repairs', 'stats', 'technicians', 'paymentMethods', 'perPage'));
    }

    /**
     * Formulaire de création de réparation
     */
    public function create()
    {
        $customers = Customer::active()->orderBy('first_name')->get();
        $technicians = User::technicians()->active()->where('shop_id', Auth::user()->shop_id)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        
        // Pièces de rechange disponibles en stock
        $spareParts = Product::with('category')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('cashier.repairs.create', compact('customers', 'technicians', 'paymentMethods', 'spareParts'));
    }

    /**
     * Enregistrer une nouvelle réparation (workflow simplifié : diagnostic + paiement immédiat)
     */
    public function store(StoreRepairRequest $request)
    {
        $validated = $request->validated();

        $cashRegister = null;
        if ($validated['amount_paid'] > 0) {
            $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
            if (!$cashRegister) {
                return back()->withInput()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
            }
        }

        try {
            $repair = $this->repairService->create($validated, auth()->user(), $cashRegister);

            $amountGiven = $validated['amount_given'] ?? $validated['amount_paid'];
            $change      = max(0, $amountGiven - $validated['amount_paid']);

            return redirect()
                ->route('cashier.repairs.show', $repair)
                ->with('show_print_modal', true)
                ->with('print_amount_given', $amountGiven)
                ->with('print_change', $change);

        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $this->handleException($e, 'repair.store'));
        }
    }

    public function show(Repair $repair)
    {
        $repair->load(['customer', 'technician', 'creator', 'parts.product.category', 'cashTransactions']);
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        
        return view('cashier.repairs.show', compact('repair', 'paymentMethods'));
    }

    public function edit(Repair $repair)
    {
        if (!$repair->canBeEdited()) {
            return back()->with('error', 'Cette réparation ne peut plus être modifiée.');
        }

        $customers = Customer::active()->orderBy('first_name')->get();
        $technicians = User::technicians()->active()->where('shop_id', Auth::user()->shop_id)->get();

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
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile_money,card',
        ]);

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir la caisse.');
        }

        try {
            $this->repairService->processPayment($repair, (float) $validated['amount'], $validated['payment_method'], $cashRegister);

            return redirect()->route('cashier.repairs.show', $repair)
                ->with('success', 'Paiement enregistré. La réparation est prête pour le diagnostic.');

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du paiement'));
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
            'deposit_amount'    => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
        }

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

        try {
            $this->repairService->recordDeposit($repair, (float) $validated['deposit_amount'], $paymentMethod, $cashRegister);

            return redirect()->route('cashier.repairs.ticket', ['repair' => $repair, 'auto' => 1]);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du paiement'));
        }
    }

    /**
     * Livrer la réparation et encaisser le solde
     */
    public function deliver(Request $request, Repair $repair)
    {
        if (!$repair->canBeDelivered()) {
            return back()->with('error', 'Cette réparation ne peut pas être livrée.');
        }

        $validated = $request->validate([
            'paid_amount'       => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $paidAmount    = (float) $validated['paid_amount'];
        $cashRegister  = null;
        $paymentMethod = null;

        if ($paidAmount > 0) {
            $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
            if (!$cashRegister) {
                return back()->with('error', 'Veuillez ouvrir votre caisse avant d\'encaisser un paiement.');
            }
            $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        }

        $originalStatus = $repair->status;

        try {
            $this->repairService->deliver($repair, $paidAmount, $paymentMethod, $cashRegister);

            $successMsg = $originalStatus === Repair::STATUS_UNREPAIRABLE
                ? "Appareil #{$repair->repair_number} rendu au client. Acompte remboursé si applicable."
                : 'Réparation livrée au client avec succès !';

            return redirect()->route('cashier.repairs.ticket', ['repair' => $repair])
                ->with('success', $successMsg);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'repair.deliver'));
        }
    }

    /**
     * Annuler une réparation et remettre les pièces en stock
     */
    public function cancel(Request $request, Repair $repair)
    {
        if (in_array($repair->status, [Repair::STATUS_DELIVERED, Repair::STATUS_CANCELLED])) {
            return back()->with('error', 'Cette réparation ne peut pas être annulée.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->repairService->cancel($repair, $validated['cancel_reason'], auth()->user());

            $msg = "Réparation #{$repair->repair_number} annulée.";
            if ($result['parts_count'] > 0) {
                $msg .= " {$result['parts_count']} pièce(s) remise(s) en stock.";
            }
            if ($result['amount_refunded'] > 0) {
                $formatted = number_format($result['amount_refunded'], 0, ',', ' ');
                $msg .= $result['refund_done']
                    ? " Remboursement de {$formatted} F enregistré en caisse."
                    : " Acompte de {$formatted} F à rembourser manuellement (caisse fermée).";
            }

            return redirect()->route('cashier.repairs.index')->with('success', $msg);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors de l\'annulation'));
        }
    }

    /**
     * Imprimer le reçu
     */
    public function printReceipt(Repair $repair)
    {
        $repair->load(['customer', 'parts.product.category']);
        return view('cashier.repairs.receipt', compact('repair'));
    }

    /**
     * Imprimer le ticket de dépôt (format thermique avec QR code)
     */
    public function printTicket(Repair $repair)
    {
        $repair->load(['customer', 'technician', 'creator', 'parts.product.category']);
        
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
            'company_name' => Setting::get('company_name', 'EGREGORE BUSINESS'),
            'company_address' => Setting::get('company_address', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'receipt_footer' => Setting::get('receipt_footer', 'Merci de votre confiance !'),
        ];
        return view('cashier.repairs.ticket', compact('repair', 'settings', 'ticketData'));
    }

    /**
     * Imprimer le bon de retrait
     */
    public function printDeliveryNote(Repair $repair)
    {
        $repair->load(['customer', 'parts.product.category']);
        return view('cashier.repairs.delivery-note', compact('repair'));
    }

    /**
     * Imprimer l'étiquette autocollante (à coller sur l'appareil)
     */
    public function printSticker(Repair $repair)
    {
        $repair->load(['customer']);

        // QR code : numéro de réparation seulement (scannable directement dans le champ de recherche SAV)
        $qrData = $repair->repair_number;

        $qrCode = null;
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->generate($qrData);
            $qrCode = 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {}

        return view('cashier.repairs.sticker', compact('repair', 'qrCode'));
    }
}
