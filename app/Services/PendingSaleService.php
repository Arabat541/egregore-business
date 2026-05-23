<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\PaymentMethod;
use App\Models\PendingSale;
use App\Models\PendingSaleItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PendingSaleService
{
    /**
     * Add (or merge) an item into the reseller's pending sale for today.
     *
     * @param array{reseller_id:int,product_id:int,quantity:int,unit_price:float,discount?:float} $validated
     * @throws \DomainException when merged quantity exceeds available stock
     */
    public function addItem(array $validated, User $user): PendingSaleItem
    {
        return DB::transaction(function () use ($validated, $user): PendingSaleItem {
            $pendingSale = PendingSale::getOrCreateForResellerToday(
                $validated['reseller_id'],
                $user->id,
                $user->shop_id
            );

            $product      = Product::findOrFail($validated['product_id']);
            $existingItem = $pendingSale->items()->where('product_id', $validated['product_id'])->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + (int) $validated['quantity'];

                if (!$product->hasStock($newQuantity)) {
                    throw new \DomainException("Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
                }

                $existingItem->update([
                    'quantity'    => $newQuantity,
                    'unit_price'  => (float) $validated['unit_price'],
                    'discount'    => ($validated['discount'] ?? 0) + $existingItem->discount,
                    'total_price' => ((float) $validated['unit_price'] * $newQuantity) - (($validated['discount'] ?? 0) + $existingItem->discount),
                ]);

                return $existingItem->fresh();
            }

            return PendingSaleItem::create([
                'pending_sale_id' => $pendingSale->id,
                'product_id'      => $validated['product_id'],
                'quantity'        => (int) $validated['quantity'],
                'unit_price'      => (float) $validated['unit_price'],
                'discount'        => (float) ($validated['discount'] ?? 0),
                'total_price'     => ((float) $validated['unit_price'] * (int) $validated['quantity']) - (float) ($validated['discount'] ?? 0),
            ]);
        });
    }

    /**
     * Convert a pending sale into a real Sale with stock exits and cash transaction.
     *
     * @throws \DomainException on insufficient stock or credit
     */
    public function validate(
        PendingSale $pendingSale,
        PaymentMethod $paymentMethod,
        float $amountGiven,
        ?string $notes,
        CashRegister $cashRegister,
        User $user,
        float $globalDiscount = 0.0,
    ): Sale {
        return DB::transaction(function () use ($pendingSale, $paymentMethod, $amountGiven, $notes, $cashRegister, $user, $globalDiscount): Sale {
            $reseller = $pendingSale->reseller;

            foreach ($pendingSale->items as $item) {
                if (!$item->product->hasStock($item->quantity)) {
                    throw new \DomainException("Stock insuffisant pour {$item->product->name}. Disponible: {$item->product->quantity_in_stock}");
                }
            }

            $grossSubtotal  = $pendingSale->items->sum(fn($i) => $i->unit_price * $i->quantity);
            $lineDiscounts  = (float) $pendingSale->items->sum('discount');
            $totalDiscounts = $lineDiscounts + $globalDiscount;
            $totalAmount    = max(0.0, (float) $pendingSale->total_amount - $globalDiscount);

            $amountPaid    = min($amountGiven, $totalAmount);
            $amountDue     = max(0.0, $totalAmount - $amountGiven);
            $isCredit      = $amountDue > 0 && $reseller !== null;
            $paymentStatus = $isCredit ? 'credit' : 'paid';

            if ($reseller === null && $amountDue > 0) {
                throw new \DomainException('Le montant reçu doit couvrir le total de la vente.');
            }

            if ($isCredit && !$reseller->canPurchaseOnCredit($amountDue)) {
                throw new \DomainException("Crédit insuffisant pour ce revendeur. Disponible: {$reseller->available_credit} FCFA");
            }

            $sale = Sale::create([
                'user_id'         => $user->id,
                'reseller_id'     => $reseller?->id,
                'client_type'     => 'reseller',
                'subtotal'        => $grossSubtotal,
                'discount_amount' => $totalDiscounts,
                'tax_amount'      => 0,
                'total_amount'    => $totalAmount,
                'amount_paid'     => $amountPaid,
                'amount_given'    => $amountGiven,
                'amount_due'      => $amountDue,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $paymentMethod->type ?? 'cash',
                'notes'           => $notes ?? $pendingSale->notes,
                'completed_at'    => now(),
            ]);

            foreach ($pendingSale->items as $item) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'discount'    => $item->discount,
                    'total_price' => $item->total_price,
                ]);

                StockMovement::recordExit(
                    $item->product,
                    $user,
                    $item->quantity,
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            if ($isCredit && $amountDue > 0) {
                $reseller->addDebt($amountDue);
            }

            if ($reseller !== null) {
                $reseller->addPurchase($totalAmount);
            }

            if ($amountPaid > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_SALE,
                    $amountPaid,
                    $paymentMethod->type ?? 'cash',
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            $pendingSale->update([
                'status'       => 'validated',
                'validated_at' => now(),
                'validated_by' => $user->id,
                'sale_id'      => $sale->id,
            ]);

            ActivityLog::log('sale', $sale, null, $sale->toArray(), "Vente #{$sale->invoice_number} (depuis vente en attente)");

            return $sale;
        });
    }
}
