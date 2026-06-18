<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Shop;
use App\Services\ResellerPaymentService;
use Illuminate\Http\Request;

/**
 * Gestion des créances revendeurs - Caissière
 */
class ResellerPaymentController extends Controller
{
    public function __construct(
        private readonly ResellerPaymentService $resellerPaymentService,
    ) {}

    /**
     * Liste des revendeurs avec créances
     */
    public function index(Request $request)
    {
        $shopId = auth()->user()->shop_id;

        // Resellers avec des ventes à crédit non soldées dans cette boutique
        $query = Reseller::withSum(
            ['sales as shop_debt' => fn($q) => $q->withoutGlobalScope('shop')
                ->where('shop_id', $shopId)
                ->where('amount_due', '>', 0)
            ], 'amount_due'
        )
        ->whereHas('sales', fn($q) => $q->withoutGlobalScope('shop')
            ->where('shop_id', $shopId)
            ->where('amount_due', '>', 0)
        );

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                  ->orWhere('contact_name', 'like', "%{$request->search}%");
            });
        }

        $resellersWithDebt = $query->orderByDesc('shop_debt')->paginate(20);

        $totalDebt = Sale::withoutGlobalScope('shop')
            ->where('shop_id', $shopId)
            ->where('amount_due', '>', 0)
            ->sum('amount_due');

        $todayPayments = ResellerPayment::where('shop_id', $shopId)
            ->whereDate('created_at', today())
            ->sum('amount');

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.index', compact('resellersWithDebt', 'totalDebt', 'todayPayments', 'paymentMethods'));
    }

    /**
     * Détail des créances d'un revendeur
     */
    public function show(Reseller $reseller)
    {
        $shopId = auth()->user()->shop_id;

        // Sale global scope filtre déjà par shop pour les caissières.
        // On charge explicitement en précisant le scope pour clarté.
        $reseller->load([
            'sales'    => fn($q) => $q->withoutGlobalScope('shop')
                ->where('shop_id', $shopId)
                ->onCredit()
                ->latest(),
            'payments' => fn($q) => $q->where('shop_id', $shopId)
                ->with('user')
                ->latest(),
        ]);

        $shopDebt = $reseller->getShopDebt($shopId);
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.show', compact('reseller', 'shopDebt', 'paymentMethods'));
    }

    /**
     * Formulaire de paiement
     */
    public function createPayment(Request $request, Reseller $reseller)
    {
        $shopId   = auth()->user()->shop_id;
        $shopDebt = $reseller->getShopDebt($shopId);

        if ($shopDebt <= 0) {
            return back()->with('info', 'Ce revendeur n\'a pas de dette dans votre boutique.');
        }

        $dateFrom       = $request->get('date_from');
        $dateTo         = $request->get('date_to');
        $selectedSaleId = $request->get('sale_id');

        $salesQuery = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->where('shop_id', $shopId)
            ->where('amount_due', '>', 0)
            ->with('items.product');

        if ($dateFrom) {
            $salesQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $salesQuery->whereDate('created_at', '<=', $dateTo);
        }

        $filteredSales = $salesQuery->oldest()->get();

        // Ventes avec produits physiquement retournables : inclut les factures payées
        // (payment_status = 'paid') car les paiements automatiques FIFO peuvent avoir
        // soldé une facture dont le client rapporte encore physiquement les articles.
        $returnableSalesQuery = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->where('shop_id', $shopId)
            ->whereIn('payment_status', ['credit', 'paid'])
            ->with('items.product');

        if ($dateFrom) {
            $returnableSalesQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $returnableSalesQuery->whereDate('created_at', '<=', $dateTo);
        }
        // Si aucune période sélectionnée, limiter aux 6 derniers mois pour la lisibilité
        if (!$dateFrom && !$dateTo) {
            $returnableSalesQuery->where('created_at', '>=', now()->subMonths(6));
        }

        $returnableSales = $returnableSalesQuery->oldest()->get();

        $selectedSale = null;
        if ($selectedSaleId) {
            $selectedSale = Sale::withoutGlobalScope('shop')
                ->where('id', $selectedSaleId)
                ->where('reseller_id', $reseller->id)
                ->where('shop_id', $shopId)
                ->with('items.product')
                ->first();
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.create', compact(
            'reseller', 'shopDebt', 'paymentMethods', 'filteredSales', 'dateFrom', 'dateTo', 'selectedSale', 'returnableSales'
        ));
    }

    /**
     * Enregistrer un paiement (espèces + retour produits optionnel)
     */
    public function storePayment(Request $request, Reseller $reseller)
    {
        if ($request->get('payment_type') === 'invoice_partial' && $request->filled('sale_id')) {
            return $this->storeInvoicePartialPayment($request, $reseller);
        }

        $rules = [
            'cash_amount'            => 'nullable|numeric|min:0',
            'payment_method_id'      => 'required_if:cash_amount,>,0|nullable|exists:payment_methods,id',
            'sale_id'                => 'nullable|exists:sales,id',
            'notes'                  => 'nullable|string|max:500',
            'returns'                => 'nullable|array',
            'returns.*.product_id'   => 'required_with:returns|exists:products,id',
            'returns.*.quantity'     => 'required_with:returns|integer|min:1',
            'returns.*.unit_price'   => 'required_with:returns|numeric|min:0',
            'returns.*.condition'    => 'required_with:returns|in:new,good,damaged',
            'returns.*.restock'      => 'nullable|boolean',
            'returns.*.sale_id'      => 'nullable|exists:sales,id',
            'returns.*.sale_item_id' => 'nullable|exists:sale_items,id',
        ];

        $validated = $request->validate($rules);

        $cashAmount   = (float) ($validated['cash_amount'] ?? 0);
        $returns      = $validated['returns'] ?? [];
        $returnAmount = 0.0;
        $returnsBySale = [];

        foreach ($returns as $return) {
            if (!empty($return['product_id']) && !empty($return['quantity'])) {
                $quantity  = (int) $return['quantity'];
                $unitPrice = (float) $return['unit_price'];
                $lineValue = $unitPrice * $quantity;
                $returnAmount += $lineValue;

                if (!empty($return['sale_item_id'])) {
                    $saleItem = SaleItem::find($return['sale_item_id']);
                    if ($saleItem && $quantity > $saleItem->quantity) {
                        $product = Product::find($return['product_id']);
                        return back()->with('error',
                            'La quantité retournée (' . $quantity . ') pour "' . ($product->name ?? 'Produit') . '" ' .
                            'dépasse la quantité achetée (' . $saleItem->quantity . ').'
                        );
                    }
                }

                $saleKey = $return['sale_id'] ?? 'global';
                $returnsBySale[$saleKey] = ($returnsBySale[$saleKey] ?? 0.0) + $lineValue;
            }
        }

        foreach ($returnsBySale as $saleId => $returnValue) {
            if ($saleId !== 'global') {
                $sale = Sale::find($saleId);
                if ($sale && $returnValue > (float) $sale->total_amount) {
                    // Bloquer seulement si on essaie de retourner plus que le montant total de la facture.
                    // Retourner jusqu'au total_amount est autorisé même si une partie a déjà été payée :
                    // l'excédent (total_amount - amount_due) sera redistribué sur les autres factures ouvertes.
                    return back()->with('error',
                        'La valeur des retours (' . number_format($returnValue, 0, ',', ' ') . ' FCFA) ' .
                        'dépasse le montant total de la facture (' . number_format((float) $sale->total_amount, 0, ',', ' ') . ' FCFA).'
                    );
                }
            }
        }

        $totalPayment = $cashAmount + $returnAmount;
        $shopDebt     = $reseller->getShopDebt(auth()->user()->shop_id);

        if ($totalPayment <= 0) {
            return back()->with('error', 'Veuillez entrer un montant ou sélectionner des produits à retourner.');
        }

        if ($totalPayment > $shopDebt) {
            return back()->with('error', 'Le montant total (' . number_format($totalPayment, 0, ',', ' ') . ' FCFA) dépasse la dette de votre boutique (' . number_format($shopDebt, 0, ',', ' ') . ' FCFA).');
        }

        $paymentMethod = null;
        if ($cashAmount > 0) {
            $paymentMethod = PaymentMethod::find($validated['payment_method_id']);
            if (!$paymentMethod) {
                return back()->with('error', 'Veuillez sélectionner un mode de paiement pour le montant en espèces.');
            }
        }

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir la caisse.');
        }

        try {
            $payment = $this->resellerPaymentService->processPayment(
                $reseller, $validated, $paymentMethod, $cashRegister, auth()->id(), auth()->user()->shop_id
            );

            $message = 'Paiement enregistré. ';
            if ($cashAmount > 0 && $returnAmount > 0) {
                $message .= 'Espèces: ' . number_format($cashAmount, 0, ',', ' ') . ' FCFA + Retours: ' . number_format($returnAmount, 0, ',', ' ') . ' FCFA. ';
            } elseif ($cashAmount > 0) {
                $message .= 'Montant: ' . number_format($cashAmount, 0, ',', ' ') . ' FCFA. ';
            } else {
                $message .= 'Retour produits: ' . number_format($returnAmount, 0, ',', ' ') . ' FCFA. ';
            }
            $message .= 'Nouvelle dette: ' . number_format((float) $payment->debt_after, 0, ',', ' ') . ' FCFA';

            return redirect()->route('cashier.reseller-payments.receipt', [$reseller, $payment])
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du paiement'));
        }
    }

    /**
     * Paiement partiel d'une facture spécifique
     */
    protected function storeInvoicePartialPayment(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'sale_id'           => 'required|exists:sales,id',
            'cash_amount'       => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'notes'             => 'nullable|string|max:500',
        ]);

        $sale       = Sale::findOrFail($validated['sale_id']);
        $cashAmount = (float) $validated['cash_amount'];

        if ($cashAmount > (float) $sale->amount_due) {
            return back()->with('error', 'Le montant (' . number_format($cashAmount, 0, ',', ' ') . ' FCFA) dépasse le reste à payer de cette facture (' . number_format((float) $sale->amount_due, 0, ',', ' ') . ' FCFA).');
        }

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir la caisse.');
        }

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        $newAmountDue  = max(0.0, (float) $sale->amount_due - $cashAmount);

        try {
            $payment = $this->resellerPaymentService->processInvoicePartialPayment(
                $reseller, $sale, $cashAmount, $paymentMethod, $cashRegister, auth()->id(), auth()->user()->shop_id
            );

            $message  = 'Paiement enregistré pour la facture ' . $sale->invoice_number . '. ';
            $message .= 'Montant payé: ' . number_format($cashAmount, 0, ',', ' ') . ' FCFA. ';
            if ($newAmountDue > 0) {
                $message .= 'Reste à payer sur cette facture: ' . number_format($newAmountDue, 0, ',', ' ') . ' FCFA. ';
            } else {
                $message .= 'Facture entièrement soldée! ';
            }
            $message .= 'Nouvelle dette totale: ' . number_format((float) $payment->debt_after, 0, ',', ' ') . ' FCFA';

            return redirect()->route('cashier.reseller-payments.receipt', [$reseller, $payment])
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors du paiement'));
        }
    }

    /**
     * Reçu d'un paiement de créance
     */
    public function paymentReceipt(Reseller $reseller, ResellerPayment $payment)
    {
        $payment->load(['user', 'sale.items.product', 'productReturns.product']);

        $shop = Shop::find(auth()->user()->shop_id);
        $settings = [
            'shop_name'    => $shop?->name    ?: Setting::get('shop_name',    'EGREGORE BUSINESS'),
            'shop_address' => $shop?->address ?: Setting::get('shop_address', ''),
            'shop_phone'   => $shop?->phone   ?: Setting::get('shop_phone',   ''),
        ];

        return view('cashier.reseller-payments.payment-receipt', compact('reseller', 'payment', 'settings'));
    }

    /**
     * Historique des paiements
     */
    public function paymentHistory(Request $request)
    {
        $query = ResellerPayment::with(['reseller', 'user']);

        if ($request->filled('reseller_id')) {
            $query->forReseller($request->reseller_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments  = $query->latest()->paginate(20);
        $resellers = Reseller::orderBy('company_name')->get();

        return view('cashier.reseller-payments.history', compact('payments', 'resellers'));
    }
}
