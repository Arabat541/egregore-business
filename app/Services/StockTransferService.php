<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

final class StockTransferService
{
    /**
     * Crée un transfert (header + items) sans toucher au stock.
     *
     * @param  array<array{product_id:int, quantity:int, notes?:string}> $items
     */
    public function create(
        int $fromShopId,
        int $toShopId,
        int $userId,
        array $items,
        ?string $notes,
    ): StockTransfer {
        return DB::transaction(function () use ($fromShopId, $toShopId, $userId, $items, $notes): StockTransfer {
            $transfer = StockTransfer::create([
                'reference'    => 'TRF-' . strtoupper(uniqid()),
                'from_shop_id' => $fromShopId,
                'to_shop_id'   => $toShopId,
                'user_id'      => $userId,
                'status'       => StockTransfer::STATUS_PENDING,
                'notes'        => $notes,
            ]);

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'purchase_price'    => $product?->purchase_price,
                    'notes'             => $item['notes'] ?? null,
                ]);
            }

            return $transfer;
        });
    }

    /**
     * Expédie le transfert : déduit le stock source et passe en "en transit".
     * Admin uniquement.
     */
    public function ship(StockTransfer $transfer, int $userId): void
    {
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            throw new \LogicException('Ce transfert ne peut plus être expédié.');
        }

        DB::transaction(function () use ($transfer, $userId): void {
            foreach ($transfer->items as $item) {
                $source = Product::where('id', $item->product_id)
                    ->where('shop_id', $transfer->from_shop_id)
                    ->firstOrFail();

                if ($source->quantity_in_stock < $item->quantity) {
                    throw new \DomainException("Stock insuffisant pour {$source->name}.");
                }

                $before = $source->quantity_in_stock;
                $after  = $before - $item->quantity;
                $source->update(['quantity_in_stock' => $after]);

                StockMovement::create([
                    'shop_id'         => $transfer->from_shop_id,
                    'product_id'      => $item->product_id,
                    'user_id'         => $userId,
                    'type'            => 'transfer_out',
                    'quantity'        => -$item->quantity,
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'reference'       => $transfer->reference,
                    'reason'          => "Expédition vers {$transfer->toShop->name} — en attente de réception",
                ]);
            }

            $transfer->update([
                'status'        => StockTransfer::STATUS_IN_TRANSIT,
                'validated_by'  => $userId,
                'validated_at'  => now(),
                'sent_by'       => $userId,
                'in_transit_at' => now(),
            ]);
        });
    }

    /**
     * Confirme la réception côté destination.
     * Crée/trouve le produit dans la boutique destination, ajuste les écarts côté source.
     *
     * @param  array<array{item_id:int, quantity_received:int}> $items
     * @return bool true si au moins un écart de quantité
     */
    public function confirmReception(
        StockTransfer $transfer,
        array $items,
        ?string $receptionNotes,
        int $userId,
    ): bool {
        if ($transfer->status !== StockTransfer::STATUS_IN_TRANSIT) {
            throw new \LogicException("Ce transfert n'est pas en transit.");
        }

        return DB::transaction(function () use ($transfer, $items, $receptionNotes, $userId): bool {
            $hasDiscrepancy = false;

            $transfer->load(['items' => function ($q): void {
                $q->with(['product' => function ($pq): void {
                    $pq->withoutGlobalScope('shop');
                }]);
            }]);

            foreach ($items as $itemData) {
                $item        = $transfer->items->find($itemData['item_id']);
                $qtyReceived = (int) $itemData['quantity_received'];

                if (!$item) {
                    continue;
                }

                $item->update(['quantity_received' => $qtyReceived]);

                if ($qtyReceived !== $item->quantity) {
                    $hasDiscrepancy = true;
                }

                if ($qtyReceived <= 0) {
                    continue;
                }

                $sourceProduct = Product::withoutGlobalScope('shop')
                    ->where('id', $item->product_id)
                    ->where('shop_id', $transfer->from_shop_id)
                    ->first();

                $ref = $sourceProduct ?? $item->product;

                $destProduct = Product::withoutGlobalScope('shop')
                    ->where('shop_id', $transfer->to_shop_id)
                    ->where('name', $ref->name ?? '')
                    ->where('category_id', $ref->category_id)
                    ->first();

                if ($destProduct) {
                    $destBefore = $destProduct->quantity_in_stock;
                    $destAfter  = $destBefore + $qtyReceived;
                    $destProduct->update(['quantity_in_stock' => $destAfter]);
                } else {
                    $destProduct = Product::create([
                        'shop_id'               => $transfer->to_shop_id,
                        'category_id'           => $ref->category_id,
                        'name'                  => $ref->name,
                        'sku'                   => $ref->sku,
                        'description'           => $ref->description,
                        'purchase_price'        => $ref->purchase_price,
                        'normal_price'          => $ref->normal_price,
                        'reseller_price'        => $ref->reseller_price,
                        'semi_wholesale_price'  => $ref->semi_wholesale_price,
                        'wholesale_price'       => $ref->wholesale_price,
                        'quantity_in_stock'     => $qtyReceived,
                        'stock_alert_threshold' => $ref->stock_alert_threshold,
                        'is_active'             => true,
                    ]);
                    $destBefore = 0;
                    $destAfter  = $qtyReceived;
                }

                StockMovement::create([
                    'shop_id'         => $transfer->to_shop_id,
                    'product_id'      => $destProduct->id,
                    'user_id'         => $userId,
                    'type'            => 'transfer_in',
                    'quantity'        => $qtyReceived,
                    'quantity_before' => $destBefore,
                    'quantity_after'  => $destAfter,
                    'reference'       => $transfer->reference,
                    'reason'          => "Réception depuis {$transfer->fromShop->name}"
                        . ($qtyReceived !== $item->quantity
                            ? " [ÉCART: commandé {$item->quantity}, reçu {$qtyReceived}]"
                            : ''),
                ]);

                if ($qtyReceived < $item->quantity) {
                    $diff    = $item->quantity - $qtyReceived;
                    $srcProd = Product::withoutGlobalScope('shop')
                        ->where('id', $item->product_id)
                        ->where('shop_id', $transfer->from_shop_id)
                        ->first();

                    if ($srcProd) {
                        $before = $srcProd->quantity_in_stock;
                        $srcProd->increment('quantity_in_stock', $diff);
                        StockMovement::create([
                            'shop_id'         => $transfer->from_shop_id,
                            'product_id'      => $srcProd->id,
                            'user_id'         => $userId,
                            'type'            => 'adjustment',
                            'quantity'        => $diff,
                            'quantity_before' => $before,
                            'quantity_after'  => $before + $diff,
                            'reference'       => $transfer->reference,
                            'reason'          => "Régularisation écart — {$diff} unité(s) non reçue(s) par {$transfer->toShop->name}",
                        ]);
                    }
                }
            }

            $transfer->update([
                'status'           => StockTransfer::STATUS_RECEIVED,
                'received_by'      => $userId,
                'received_at'      => now(),
                'reception_notes'  => $receptionNotes,
                'reception_status' => $hasDiscrepancy ? 'discrepancy' : 'ok',
            ]);

            return $hasDiscrepancy;
        });
    }
}
