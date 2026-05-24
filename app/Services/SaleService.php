<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SaleService
{
    /**
     * Calcule le prix correct selon la quantité et le type de client.
     * Clients comptoir/enregistrés → prix normal uniquement.
     * Revendeurs → grille dégressive (réparateur / demi-gros / gros), jamais le prix normal.
     */
    public function calculateCorrectPrice(Product $product, int $quantity, string $clientType): float
    {
        if ($clientType !== 'reseller') {
            return (float) $product->normal_price;
        }

        $qtyWholesale     = (int) ($product->qty_wholesale_min      ?? 10);
        $qtySemiWholesale = (int) ($product->qty_semi_wholesale_min ?? 3);

        if ($quantity >= $qtyWholesale) {
            return (float) ($product->wholesale_price ?? $product->semi_wholesale_price ?? $product->reseller_price);
        }

        if ($quantity >= $qtySemiWholesale) {
            return (float) ($product->semi_wholesale_price ?? $product->reseller_price);
        }

        return (float) $product->reseller_price;
    }

    public function create(array $validated, User $user, CashRegister $cashRegister): Sale
    {
        $paymentMethod  = \App\Models\PaymentMethod::find($validated['payment_method_id']);
        $products       = Product::whereIn('id', array_column($validated['items'], 'product_id'))
            ->get()->keyBy('id');

        $subtotal       = (float) collect($validated['items'])->sum(
            fn(array $i): float => ((float) $i['unit_price'] * (int) $i['quantity']) - (float) ($i['discount'] ?? 0)
        );
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        $total          = $subtotal - $discountAmount;
        $amountPaid     = (float) $validated['paid_amount'];
        $amountDue      = max(0.0, $total - $amountPaid);
        $actualPaid     = min($amountPaid, $total);
        $paymentStatus  = ($amountDue > 0 && $validated['client_type'] === 'reseller') ? 'credit' : 'paid';

        return DB::transaction(function () use (
            $validated, $user, $cashRegister, $paymentMethod,
            $products, $subtotal, $discountAmount, $total,
            $amountPaid, $amountDue, $actualPaid, $paymentStatus
        ): Sale {
            $sale = Sale::create([
                'shop_id'         => $user->shop_id,
                'user_id'         => $user->id,
                'customer_id'     => $validated['customer_id'] ?? null,
                'reseller_id'     => $validated['reseller_id'] ?? null,
                'client_type'     => $validated['client_type'] === 'walk-in' ? 'customer' : $validated['client_type'],
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => 0,
                'total_amount'    => $total,
                'amount_paid'     => $actualPaid,
                'amount_given'    => $amountPaid,
                'amount_due'      => $amountDue,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $paymentMethod?->type ?? 'cash',
                'notes'           => $validated['notes'] ?? null,
                'completed_at'    => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product->id,
                    'quantity'    => (int) $item['quantity'],
                    'unit_price'  => (float) $item['unit_price'],
                    'discount'    => (float) ($item['discount'] ?? 0),
                    'total_price' => ((float) $item['unit_price'] * (int) $item['quantity']) - (float) ($item['discount'] ?? 0),
                ]);

                StockMovement::recordExit(
                    $product,
                    $user,
                    (int) $item['quantity'],
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            if ($validated['client_type'] === 'reseller' && !empty($validated['reseller_id'])) {
                $reseller = Reseller::find($validated['reseller_id']);
                if ($reseller) {
                    if ($amountDue > 0) {
                        $reseller->addDebt($amountDue);
                    }
                    $reseller->addPurchase($total);
                }
            }

            if ($actualPaid > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_SALE,
                    $actualPaid,
                    $paymentMethod?->type ?? 'cash',
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            ActivityLog::log('sale', $sale, null, $sale->toArray(), "Vente #{$sale->invoice_number}");

            return $sale;
        });
    }

    public function cancel(Sale $sale, string $reason, User $user): void
    {
        if ($sale->payment_status === 'cancelled') {
            throw new \LogicException('Cette vente est déjà annulée.');
        }

        DB::transaction(function () use ($sale, $reason, $user): void {
            $originalStatus = $sale->payment_status;
            $amountPaid     = (float) $sale->amount_paid;
            $amountDue      = (float) $sale->amount_due;

            foreach ($sale->items as $item) {
                if ($item->product) {
                    StockMovement::create([
                        'shop_id'        => $sale->shop_id,
                        'product_id'     => $item->product_id,
                        'user_id'        => $user->id,
                        'type'           => StockMovement::TYPE_SALE_CANCEL,
                        'quantity'       => $item->quantity,
                        'unit_cost'      => $item->product->purchase_price,
                        'reason'         => 'sale_cancelled',
                        'reference_type' => Sale::class,
                        'reference_id'   => $sale->id,
                        'notes'          => "Annulation vente #{$sale->invoice_number}",
                    ]);
                    $item->product->increment('quantity_in_stock', $item->quantity);
                }
            }

            $sale->update([
                'payment_status' => 'cancelled',
                'amount_paid'    => 0,
                'amount_due'     => 0,
                'notes'          => ($sale->notes ? $sale->notes . "\n" : '')
                    . "Annulée le " . now()->format('d/m/Y H:i')
                    . " par {$user->name} — Motif : {$reason}",
            ]);

            if (
                $sale->client_type === 'reseller'
                && $sale->reseller_id
                && in_array($originalStatus, ['credit', 'paid'], true)
            ) {
                $reseller = Reseller::find($sale->reseller_id);
                if ($reseller) {
                    if ($amountDue > 0) {
                        $reseller->removeDebt($amountDue);
                    }
                    $reseller->removePurchase((float) $sale->total_amount);
                }
            }

            if ($amountPaid > 0) {
                $cashRegister = CashRegister::getOpenRegisterForUser($user->id);
                $cashRegister?->addTransaction(
                    CashTransaction::TYPE_EXPENSE,
                    CashTransaction::CATEGORY_ADJUSTMENT,
                    $amountPaid,
                    $sale->payment_method ?? 'cash',
                    $sale,
                    "Annulation vente #{$sale->invoice_number}"
                );
            }

            ActivityLog::log(
                'cancel', $sale, null,
                ['status' => 'cancelled', 'reason' => $reason],
                "Annulation vente #{$sale->invoice_number}"
            );
        });
    }

    public function update(Sale $sale, array $items, float $discountAmount, ?string $notes, User $user): Sale
    {
        if ($sale->payment_status === 'cancelled') {
            throw new \LogicException('Une vente annulée ne peut pas être modifiée.');
        }

        return DB::transaction(function () use ($sale, $items, $discountAmount, $notes, $user): Sale {
            $oldTotal = (float) $sale->total_amount;
            $sale->load(['items.product']);

            // Restaurer le stock des anciens articles
            foreach ($sale->items as $oldItem) {
                if ($oldItem->product) {
                    $oldItem->product->increment('quantity_in_stock', $oldItem->quantity);
                    StockMovement::create([
                        'shop_id'        => $sale->shop_id,
                        'product_id'     => $oldItem->product_id,
                        'user_id'        => $user->id,
                        'type'           => StockMovement::TYPE_SALE_CANCEL,
                        'quantity'       => $oldItem->quantity,
                        'unit_cost'      => $oldItem->product->purchase_price,
                        'reason'         => 'sale_edited',
                        'reference_type' => Sale::class,
                        'reference_id'   => $sale->id,
                        'notes'          => "Correction vente #{$sale->invoice_number} — ancienne ligne",
                    ]);
                }
            }

            $sale->items()->delete();

            // Créer les nouveaux articles
            $subtotal = 0.0;
            foreach ($items as $itemData) {
                $product   = Product::findOrFail($itemData['product_id']);
                $qty       = (int) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $discount  = (float) ($itemData['discount'] ?? 0);
                $lineTotal = ($unitPrice * $qty) - $discount;

                if ($product->quantity_in_stock < $qty) {
                    throw new \DomainException(
                        "Stock insuffisant pour {$product->name}. Disponible : {$product->quantity_in_stock}"
                    );
                }

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product->id,
                    'quantity'    => $qty,
                    'unit_price'  => $unitPrice,
                    'discount'    => $discount,
                    'total_price' => $lineTotal,
                ]);

                StockMovement::recordExit($product, $user, $qty, $sale, "Vente #{$sale->invoice_number} (corrigée)");

                $subtotal += $lineTotal;
            }

            $newTotal     = $subtotal - $discountAmount;
            $newAmountDue = max(0.0, $newTotal - (float) $sale->amount_paid);

            $editNote = sprintf(
                "\n[Modifié le %s par %s — Total : %s → %s FCFA]",
                now()->format('d/m/Y H:i'),
                $user->name,
                number_format($oldTotal, 0, ',', ' '),
                number_format($newTotal, 0, ',', ' ')
            );

            $sale->update([
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount'    => $newTotal,
                'amount_due'      => $newAmountDue,
                'payment_status'  => ($newAmountDue <= 0 && $sale->payment_status === 'credit')
                    ? 'paid'
                    : $sale->payment_status,
                'notes'           => ($notes ?? $sale->notes) . $editNote,
            ]);

            // Recalculer la dette revendeur
            if ($sale->client_type === 'reseller' && $sale->reseller_id) {
                $oldDebtPart = max(0.0, $oldTotal - (float) $sale->amount_paid);
                $newDebtPart = max(0.0, $newTotal - (float) $sale->amount_paid);
                $delta       = $newDebtPart - $oldDebtPart;

                if ($delta !== 0.0) {
                    $reseller = Reseller::find($sale->reseller_id);
                    if ($reseller) {
                        $delta > 0 ? $reseller->addDebt($delta) : $reseller->removeDebt(abs($delta));
                    }
                }
            }

            ActivityLog::log(
                'update', $sale, null,
                ['old_total' => $oldTotal, 'new_total' => $newTotal],
                "Modification vente #{$sale->invoice_number}"
            );

            return $sale->fresh();
        });
    }
}
