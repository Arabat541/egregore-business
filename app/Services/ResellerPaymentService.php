<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

final class ResellerPaymentService
{
    /**
     * Paiement global : espèces + retours de produits optionnels.
     *
     * @param  array{
     *   cash_amount?: float,
     *   payment_method_id?: int,
     *   sale_id?: int,
     *   notes?: string,
     *   returns?: array<array{product_id:int,quantity:int,unit_price:float,condition:string,restock?:bool,sale_id?:int,sale_item_id?:int}>
     * } $validated
     */
    public function processPayment(
        Reseller $reseller,
        array $validated,
        ?PaymentMethod $paymentMethod,
        CashRegister $cashRegister,
        int $userId,
        int $shopId,
    ): ResellerPayment {
        $cashAmount   = (float) ($validated['cash_amount'] ?? 0);
        $returns      = $validated['returns'] ?? [];
        $returnAmount = 0.0;

        foreach ($returns as $r) {
            if (!empty($r['product_id']) && !empty($r['quantity'])) {
                $returnAmount += (float) $r['unit_price'] * (int) $r['quantity'];
            }
        }

        $totalPayment = $cashAmount + $returnAmount;

        return DB::transaction(function () use (
            $reseller, $validated, $paymentMethod, $cashRegister,
            $userId, $shopId, $cashAmount, $returns, $returnAmount, $totalPayment
        ): ResellerPayment {
            $debtBefore = (float) $reseller->current_debt;

            $payment = ResellerPayment::create([
                'reseller_id'       => $reseller->id,
                'user_id'           => $userId,
                'shop_id'           => $shopId,
                'sale_id'           => $validated['sale_id'] ?? null,
                'amount'            => $totalPayment,
                'cash_amount'       => $cashAmount,
                'return_amount'     => $returnAmount,
                'has_product_return'=> count($returns) > 0,
                'debt_before'       => $debtBefore,
                'debt_after'        => 0,
                'payment_method'    => $paymentMethod?->type ?? 'product_return',
                'notes'             => $validated['notes'] ?? null,
            ]);

            foreach ($returns as $r) {
                if (empty($r['product_id']) || empty($r['quantity'])) {
                    continue;
                }
                $product = Product::find($r['product_id']);
                if (!$product) {
                    continue;
                }
                ProductReturn::create([
                    'reseller_id'          => $reseller->id,
                    'reseller_payment_id'  => $payment->id,
                    'sale_id'              => $r['sale_id'] ?? null,
                    'sale_item_id'         => $r['sale_item_id'] ?? null,
                    'product_id'           => $r['product_id'],
                    'user_id'              => $userId,
                    'shop_id'              => $shopId,
                    'quantity'             => (int) $r['quantity'],
                    'unit_price'           => (float) $r['unit_price'],
                    'total_value'          => (float) $r['unit_price'] * (int) $r['quantity'],
                    'condition'            => $r['condition'],
                    'restock'              => isset($r['restock']) ? (bool) $r['restock'] : ($r['condition'] !== 'damaged'),
                    'reason'               => 'Retour pour paiement créance',
                    'notes'                => null,
                ]);
            }

            $reseller->reduceDebt($totalPayment);
            ResellerPayment::distributePaymentToSales($reseller, $totalPayment, $shopId);
            $payment->update(['debt_after' => $reseller->getShopDebt($shopId)]);

            if ($cashAmount > 0) {
                $description = "Paiement créance {$reseller->company_name}";
                if ($returnAmount > 0) {
                    $description .= ' (+ retour produits: ' . number_format($returnAmount, 0, ',', ' ') . ' FCFA)';
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

            return $payment;
        });
    }

    /**
     * Annuler un paiement et inverser toutes ses répercussions.
     *
     * Stratégie : reset complet des ventes concernées + rejouer les paiements actifs (FIFO).
     * Cela garantit la cohérence quelle que soit la méthode d'origine (global ou par facture).
     */
    public function cancelPayment(ResellerPayment $payment, string $reason, int $cancelledBy): void
    {
        if ($payment->is_cancelled) {
            throw new \LogicException('Ce paiement est déjà annulé.');
        }

        DB::transaction(function () use ($payment, $reason, $cancelledBy): void {
            $reseller = $payment->reseller;
            $payment->load('productReturns.product');

            // 1. Marquer comme annulé
            $payment->update([
                'cancelled_at'        => now(),
                'cancelled_by'        => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);

            // 2. Inverser les retours produits (dé-stocker ce qui avait été remis en stock)
            foreach ($payment->productReturns as $ret) {
                if ($ret->restock && $ret->condition !== ProductReturn::CONDITION_DAMAGED) {
                    $product = $ret->product;
                    if ($product) {
                        $product->decrement('quantity_in_stock', $ret->quantity);
                        StockMovement::create([
                            'product_id'    => $product->id,
                            'user_id'       => $cancelledBy,
                            'shop_id'       => $payment->shop_id,
                            'type'          => 'exit',
                            'quantity'      => $ret->quantity,
                            'reason'        => 'Annulation paiement PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
                        ]);
                    }
                }
                $ret->delete();
            }

            // 3. Remettre à zéro les ventes crédit du revendeur (tous shops)
            // et rejouer uniquement les paiements actifs (non annulés) en ordre chronologique.
            Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->update([
                    'amount_paid'    => 0,
                    'amount_due'     => DB::raw('total_amount'),
                    'payment_status' => 'credit',
                ]);

            $activePayments = ResellerPayment::where('reseller_id', $reseller->id)
                ->whereNull('cancelled_at')
                ->orderBy('created_at')
                ->get();

            foreach ($activePayments as $p) {
                if ($p->sale_id) {
                    // Paiement direct sur une facture précise
                    $sale = Sale::withoutGlobalScope('shop')->find($p->sale_id);
                    if ($sale) {
                        $newPaid = (float) $sale->amount_paid + (float) $p->cash_amount;
                        $newDue  = max(0.0, (float) $sale->amount_due - (float) $p->cash_amount);
                        $sale->update([
                            'amount_paid'    => $newPaid,
                            'amount_due'     => $newDue,
                            'payment_status' => $newDue <= 0 ? 'paid' : 'credit',
                        ]);
                    }
                } else {
                    // Distribution FIFO multi-factures
                    ResellerPayment::distributePaymentToSales($reseller, (float) $p->amount, $p->shop_id);
                }
            }

            // 4. Recalculer current_debt = somme des amount_due restants
            $newDebt = (float) Sale::withoutGlobalScope('shop')
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'cancelled')
                ->sum('amount_due');
            $reseller->update(['current_debt' => $newDebt]);

            // 5. Annuler la transaction de caisse (entrée créée lors du paiement)
            $cashTx = CashTransaction::where('transactionable_type', ResellerPayment::class)
                ->where('transactionable_id', $payment->id)
                ->first();
            if ($cashTx && (float) $payment->cash_amount > 0) {
                // Créer une transaction corrective (sortie) dans le même registre
                $cashTx->cashRegister->addTransaction(
                    CashTransaction::TYPE_EXPENSE,
                    CashTransaction::CATEGORY_ADJUSTMENT,
                    (float) $payment->cash_amount,
                    $payment->payment_method,
                    $payment,
                    'Annulation paiement PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT) . ' — ' . $reason
                );
            }

            // 6. Journaliser
            ActivityLog::log(
                'cancel',
                $payment,
                null,
                ['reason' => $reason, 'amount' => $payment->amount],
                'Annulation paiement créance ' . $reseller->company_name
                    . ' — PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT)
            );
        });
    }

    /**
     * Paiement partiel d'une facture spécifique.
     */
    public function processInvoicePartialPayment(
        Reseller $reseller,
        Sale $sale,
        float $cashAmount,
        PaymentMethod $paymentMethod,
        CashRegister $cashRegister,
        int $userId,
        ?int $shopId = null,
    ): ResellerPayment {
        return DB::transaction(function () use (
            $reseller, $sale, $cashAmount, $paymentMethod, $cashRegister, $userId, $shopId
        ): ResellerPayment {
            $debtBefore    = (float) $reseller->current_debt;
            $newAmountPaid = (float) $sale->amount_paid + $cashAmount;
            $newAmountDue  = (float) $sale->amount_due  - $cashAmount;

            $update = [
                'amount_paid' => $newAmountPaid,
                'amount_due'  => max(0.0, $newAmountDue),
            ];
            if ($newAmountDue <= 0) {
                $update['payment_status'] = 'paid';
            }
            $sale->update($update);

            $reseller->reduceDebt($cashAmount);

            $payment = ResellerPayment::create([
                'reseller_id'       => $reseller->id,
                'user_id'           => $userId,
                'shop_id'           => $shopId,
                'sale_id'           => $sale->id,
                'amount'            => $cashAmount,
                'cash_amount'       => $cashAmount,
                'return_amount'     => 0,
                'has_product_return'=> false,
                'debt_before'       => $debtBefore,
                'debt_after'        => $shopId ? $reseller->getShopDebt($shopId) : $reseller->fresh()->current_debt,
                'payment_method'    => $paymentMethod->type,
                'notes'             => 'Paiement partiel facture ' . $sale->invoice_number,
            ]);

            $cashRegister->addTransaction(
                CashTransaction::TYPE_INCOME,
                CashTransaction::CATEGORY_DEBT_PAYMENT,
                $cashAmount,
                $paymentMethod->type,
                $payment,
                "Paiement créance {$reseller->company_name} - Facture {$sale->invoice_number}"
            );

            ActivityLog::log('payment', $payment, null, $payment->toArray(), "Paiement partiel facture: {$sale->invoice_number}");

            return $payment;
        });
    }
}
