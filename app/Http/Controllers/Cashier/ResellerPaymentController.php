<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des créances revendeurs - Caissière
 */
class ResellerPaymentController extends Controller
{
    /**
     * Liste des revendeurs avec créances
     */
    public function index(Request $request)
    {
        $query = Reseller::where('current_debt', '>', 0);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                  ->orWhere('contact_name', 'like', "%{$request->search}%");
            });
        }

        $resellersWithDebt = $query->orderByDesc('current_debt')->paginate(20);
        $totalDebt = Reseller::sum('current_debt');
        $todayPayments = ResellerPayment::whereDate('created_at', today())->sum('amount');
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.index', compact('resellersWithDebt', 'totalDebt', 'todayPayments', 'paymentMethods'));
    }

    /**
     * Détail des créances d'un revendeur
     */
    public function show(Reseller $reseller)
    {
        $reseller->load([
            'sales' => fn($q) => $q->onCredit()->latest(),
            'payments' => fn($q) => $q->with('user')->latest(),
        ]);

        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.show', compact('reseller', 'paymentMethods'));
    }

    /**
     * Formulaire de paiement
     */
    public function createPayment(Reseller $reseller)
    {
        if ($reseller->current_debt <= 0) {
            return back()->with('info', 'Ce revendeur n\'a pas de dette.');
        }

        // Charger les ventes avec les items pour permettre le retour de produits
        $reseller->load([
            'sales' => fn($q) => $q->where('amount_due', '>', 0)->with('items.product')->oldest(),
        ]);

        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.reseller-payments.create', compact('reseller', 'paymentMethods'));
    }

    /**
     * Enregistrer un paiement (espèces + retour produits optionnel)
     */
    public function storePayment(Request $request, Reseller $reseller)
    {
        // Validation de base
        $rules = [
            'cash_amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'required_if:cash_amount,>,0|nullable|exists:payment_methods,id',
            'sale_id' => 'nullable|exists:sales,id',
            'notes' => 'nullable|string|max:500',
            // Retours de produits
            'returns' => 'nullable|array',
            'returns.*.product_id' => 'required_with:returns|exists:products,id',
            'returns.*.quantity' => 'required_with:returns|integer|min:1',
            'returns.*.unit_price' => 'required_with:returns|numeric|min:0',
            'returns.*.condition' => 'required_with:returns|in:new,good,damaged',
            'returns.*.restock' => 'nullable|boolean',
            'returns.*.sale_id' => 'nullable|exists:sales,id',
            'returns.*.sale_item_id' => 'nullable|exists:sale_items,id',
        ];

        $validated = $request->validate($rules);

        $cashAmount = (float) ($validated['cash_amount'] ?? 0);
        $returns = $validated['returns'] ?? [];
        
        // Calculer la valeur totale des retours et valider par vente
        $returnAmount = 0;
        $returnsBySale = [];
        
        foreach ($returns as $index => $return) {
            if (!empty($return['product_id']) && !empty($return['quantity'])) {
                $quantity = (int) $return['quantity'];
                $unitPrice = (float) $return['unit_price'];
                $lineValue = $unitPrice * $quantity;
                $returnAmount += $lineValue;
                
                // Valider la quantité par rapport à la ligne de vente originale
                if (!empty($return['sale_item_id'])) {
                    $saleItem = \App\Models\SaleItem::find($return['sale_item_id']);
                    if ($saleItem && $quantity > $saleItem->quantity) {
                        $product = Product::find($return['product_id']);
                        return back()->with('error', 
                            'La quantité retournée (' . $quantity . ') pour "' . ($product->name ?? 'Produit') . '" ' .
                            'dépasse la quantité achetée (' . $saleItem->quantity . ').'
                        );
                    }
                }
                
                // Grouper par vente pour validation
                $saleId = $return['sale_id'] ?? 'global';
                if (!isset($returnsBySale[$saleId])) {
                    $returnsBySale[$saleId] = 0;
                }
                $returnsBySale[$saleId] += $lineValue;
            }
        }

        // Valider que les retours par vente ne dépassent pas le montant dû de chaque vente
        foreach ($returnsBySale as $saleId => $returnValue) {
            if ($saleId !== 'global') {
                $sale = \App\Models\Sale::find($saleId);
                if ($sale && $returnValue > (float) $sale->amount_due) {
                    return back()->with('error', 
                        'La valeur des retours (' . number_format($returnValue, 0, ',', ' ') . ' FCFA) ' .
                        'dépasse le montant restant dû (' . number_format($sale->amount_due, 0, ',', ' ') . ' FCFA) ' .
                        'pour la facture ' . $sale->invoice_number . '. ' .
                        'Le revendeur a déjà payé une partie, vous ne pouvez retourner que pour la valeur restante.'
                    );
                }
            }
        }

        $totalPayment = $cashAmount + $returnAmount;

        // Vérifier qu'il y a au moins un paiement ou un retour
        if ($totalPayment <= 0) {
            return back()->with('error', 'Veuillez entrer un montant ou sélectionner des produits à retourner.');
        }

        // Vérifier que le total ne dépasse pas la dette globale
        if ($totalPayment > (float) $reseller->current_debt) {
            return back()->with('error', 'Le montant total (' . number_format($totalPayment, 0, ',', ' ') . ' FCFA) dépasse la dette (' . number_format($reseller->current_debt, 0, ',', ' ') . ' FCFA).');
        }

        // Récupérer la méthode de paiement si paiement en espèces
        $paymentMethod = null;
        if ($cashAmount > 0) {
            $paymentMethod = \App\Models\PaymentMethod::find($validated['payment_method_id']);
            if (!$paymentMethod) {
                return back()->with('error', 'Veuillez sélectionner un mode de paiement pour le montant en espèces.');
            }
        }

        // Vérifier la caisse
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Veuillez ouvrir la caisse.');
        }

        $shopId = session('current_shop_id', auth()->user()->shop_id);

        DB::beginTransaction();
        try {
            $debtBefore = $reseller->current_debt;

            // Créer l'enregistrement du paiement
            $payment = ResellerPayment::create([
                'reseller_id' => $reseller->id,
                'user_id' => auth()->id(),
                'sale_id' => $validated['sale_id'] ?? null,
                'amount' => $totalPayment,
                'cash_amount' => $cashAmount,
                'return_amount' => $returnAmount,
                'has_product_return' => count($returns) > 0,
                'debt_before' => $debtBefore,
                'debt_after' => 0, // Sera mis à jour après
                'payment_method' => $paymentMethod?->type ?? 'product_return',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Traiter les retours de produits
            if (!empty($returns)) {
                foreach ($returns as $returnData) {
                    if (empty($returnData['product_id']) || empty($returnData['quantity'])) {
                        continue;
                    }

                    $product = Product::find($returnData['product_id']);
                    if (!$product) continue;

                    ProductReturn::create([
                        'reseller_id' => $reseller->id,
                        'reseller_payment_id' => $payment->id,
                        'sale_id' => $returnData['sale_id'] ?? null,
                        'sale_item_id' => $returnData['sale_item_id'] ?? null,
                        'product_id' => $returnData['product_id'],
                        'user_id' => auth()->id(),
                        'shop_id' => $shopId,
                        'quantity' => $returnData['quantity'],
                        'unit_price' => $returnData['unit_price'],
                        'total_value' => $returnData['unit_price'] * $returnData['quantity'],
                        'condition' => $returnData['condition'],
                        'restock' => isset($returnData['restock']) ? (bool) $returnData['restock'] : ($returnData['condition'] !== 'damaged'),
                        'reason' => 'Retour pour paiement créance',
                        'notes' => null,
                    ]);
                }
            }

            // Réduire la dette globale
            $reseller->reduceDebt($totalPayment);

            // Distribuer le paiement sur les ventes (FIFO)
            ResellerPayment::distributePaymentToSales($reseller, $totalPayment);

            // Mettre à jour debt_after
            $payment->update(['debt_after' => $reseller->fresh()->current_debt]);

            // Enregistrer la transaction de caisse (seulement pour le montant espèces)
            if ($cashAmount > 0) {
                $description = "Paiement créance {$reseller->company_name}";
                if ($returnAmount > 0) {
                    $description .= " (+ retour produits: " . number_format($returnAmount, 0, ',', ' ') . " FCFA)";
                }

                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_DEBT_PAYMENT,
                    $cashAmount,
                    $paymentMethod->type,
                    $payment,
                    $description
                );
            }

            ActivityLog::log('payment', $payment, null, $payment->toArray(), "Paiement créance: {$reseller->company_name}");

            DB::commit();

            // Message de succès détaillé
            $message = 'Paiement enregistré. ';
            if ($cashAmount > 0 && $returnAmount > 0) {
                $message .= 'Espèces: ' . number_format($cashAmount, 0, ',', ' ') . ' FCFA + Retours: ' . number_format($returnAmount, 0, ',', ' ') . ' FCFA. ';
            } elseif ($cashAmount > 0) {
                $message .= 'Montant: ' . number_format($cashAmount, 0, ',', ' ') . ' FCFA. ';
            } else {
                $message .= 'Retour produits: ' . number_format($returnAmount, 0, ',', ' ') . ' FCFA. ';
            }
            $message .= 'Nouvelle dette: ' . number_format((float) $reseller->fresh()->current_debt, 0, ',', ' ') . ' FCFA';

            return redirect()->route('cashier.reseller-payments.show', $reseller)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Payer une vente spécifique
     */
    protected function paySpecificSale(Reseller $reseller, \App\Models\Sale $sale, float $amount, string $paymentMethod, ?string $notes): ResellerPayment
    {
        $debtBefore = $reseller->current_debt;

        // Mettre à jour la vente
        $newAmountPaid = (float)$sale->amount_paid + $amount;
        $newAmountDue = (float)$sale->amount_due - $amount;

        $updateData = [
            'amount_paid' => $newAmountPaid,
            'amount_due' => max(0, $newAmountDue),
        ];

        // Si la vente est entièrement payée, changer le statut
        if ($newAmountDue <= 0) {
            $updateData['payment_status'] = 'paid';
        }

        $sale->update($updateData);

        // Réduire la dette globale
        $reseller->reduceDebt($amount);

        // Créer l'enregistrement du paiement
        return ResellerPayment::create([
            'reseller_id' => $reseller->id,
            'user_id' => auth()->id(),
            'sale_id' => $sale->id,
            'amount' => $amount,
            'debt_before' => $debtBefore,
            'debt_after' => $reseller->fresh()->current_debt,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);
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

        $payments = $query->latest()->paginate(20);
        $resellers = Reseller::orderBy('company_name')->get();

        return view('cashier.reseller-payments.history', compact('payments', 'resellers'));
    }
}
