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
            ResellerPayment::distributePaymentToSales($reseller, $totalPayment);
            $payment->update(['debt_after' => $reseller->fresh()->current_debt]);

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
     * Paiement partiel d'une facture spécifique.
     */
    public function processInvoicePartialPayment(
        Reseller $reseller,
        Sale $sale,
        float $cashAmount,
        PaymentMethod $paymentMethod,
        CashRegister $cashRegister,
        int $userId,
    ): ResellerPayment {
        return DB::transaction(function () use (
            $reseller, $sale, $cashAmount, $paymentMethod, $cashRegister, $userId
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
                'sale_id'           => $sale->id,
                'amount'            => $cashAmount,
                'cash_amount'       => $cashAmount,
                'return_amount'     => 0,
                'has_product_return'=> false,
                'debt_before'       => $debtBefore,
                'debt_after'        => $reseller->fresh()->current_debt,
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
