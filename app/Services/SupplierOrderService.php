<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierPriceHistory;
use App\Models\SupplierProductPrice;
use Illuminate\Support\Facades\DB;

final class SupplierOrderService
{
    /**
     * Create a supplier order (header + items). Sets total_amount to 0 initially — caller must update after.
     *
     * @param  array{supplier_id:int,shop_id:int,order_date:string,notes?:string,items:array<array{product_id:int,quantity:int,unit_price:float}>} $validated
     * @param  string|null $invoiceNumber  Explicit invoice number; auto-generated if null
     * @throws \RuntimeException when a product doesn't belong to the target shop
     */
    public function createOrder(array $validated, ?string $invoiceNumber, int $userId): SupplierOrder
    {
        return DB::transaction(function () use ($validated, $invoiceNumber, $userId): SupplierOrder {
            $order = SupplierOrder::create([
                'shop_id'      => $validated['shop_id'],
                'supplier_id'  => $validated['supplier_id'],
                'user_id'      => $userId,
                'reference'    => $invoiceNumber ?: SupplierOrder::generateReference(),
                'status'       => 'draft',
                'order_date'   => $validated['order_date'],
                'notes'        => $validated['notes'] ?? null,
                'total_amount' => 0,
            ]);

            $totalAmount = 0.0;

            foreach ($validated['items'] as $item) {
                $product = Product::withoutGlobalScope('shop')->findOrFail($item['product_id']);

                if ((int) $product->shop_id !== (int) $validated['shop_id']) {
                    throw new \RuntimeException("Le produit '{$product->name}' n'appartient pas à la boutique sélectionnée.");
                }

                $total        = (int) $item['quantity'] * (float) $item['unit_price'];
                $totalAmount += $total;

                SupplierOrderItem::create([
                    'supplier_order_id' => $order->id,
                    'product_id'        => $product->id,
                    'product_name'      => $product->name,
                    'quantity_ordered'  => (int) $item['quantity'],
                    'quantity_received' => 0,
                    'unit_price'        => (float) $item['unit_price'],
                    'total_price'       => $total,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            return $order;
        });
    }

    /**
     * Receive order items: update quantities/prices, recalculate CMP, create stock movements.
     *
     * @param  array<array{item_id:int,quantity_received:int,unit_price:float}> $items
     * @return bool true if any item's received qty differs from ordered qty
     */
    public function receiveOrder(
        SupplierOrder $order,
        array $items,
        ?string $receptionNotes,
        int $userId,
    ): bool {
        return DB::transaction(function () use ($order, $items, $receptionNotes, $userId): bool {
            $totalAmount    = 0.0;
            $hasDiscrepancy = false;

            foreach ($items as $itemData) {
                $item        = SupplierOrderItem::findOrFail($itemData['item_id']);
                $receivedQty = (int) $itemData['quantity_received'];
                $unitPrice   = (float) $itemData['unit_price'];

                if ($receivedQty !== $item->quantity_ordered) {
                    $hasDiscrepancy = true;
                }

                $item->update([
                    'quantity_received' => $receivedQty,
                    'unit_price'        => $unitPrice,
                    'total_price'       => $receivedQty * $unitPrice,
                ]);

                $totalAmount += $receivedQty * $unitPrice;

                if ($item->product && $receivedQty > 0) {
                    $stockBefore  = $item->product->quantity_in_stock;
                    $currentStock = $item->product->quantity_in_stock;
                    $currentPrice = (float) ($item->product->purchase_price ?? 0);
                    $newCmp       = ($currentStock + $receivedQty) > 0
                        ? (($currentStock * $currentPrice) + ($receivedQty * $unitPrice)) / ($currentStock + $receivedQty)
                        : $unitPrice;

                    $item->product->increment('quantity_in_stock', $receivedQty);
                    $item->product->update(['purchase_price' => round($newCmp, 2)]);

                    StockMovement::create([
                        'shop_id'        => $order->shop_id,
                        'product_id'     => $item->product_id,
                        'user_id'        => $userId,
                        'type'           => 'purchase',
                        'quantity'       => $receivedQty,
                        'quantity_before' => $stockBefore,
                        'quantity_after' => $stockBefore + $receivedQty,
                        'reference'      => $order->reference,
                        'reason'         => "Réapprovisionnement fournisseur: {$order->supplier->company_name}",
                        'moveable_type'  => SupplierOrder::class,
                        'moveable_id'    => $order->id,
                    ]);

                    $this->recordSupplierPrice($order->supplier_id, $item->product_id, $unitPrice, $order->id, $userId);
                }
            }

            $order->update([
                'total_amount'    => $totalAmount,
                'status'          => 'received',
                'received_date'   => now(),
                'reception_notes' => $receptionNotes,
            ]);

            return $hasDiscrepancy;
        });
    }

    /**
     * Bulk-mark an order as received with per-item qty/price overrides (no CMP, no stock movements).
     *
     * @param  array<int,int>   $receivedQtys  Map of item_id → quantity_received
     * @param  array<int,float> $prices         Map of item_id → unit_price
     */
    public function markOrderReceived(SupplierOrder $order, array $receivedQtys, array $prices, int $userId): void
    {
        DB::transaction(function () use ($order, $receivedQtys, $prices, $userId): void {
            foreach ($order->items as $item) {
                $receivedQty = $receivedQtys[$item->id] ?? $item->quantity_ordered;
                $unitPrice   = $prices[$item->id] ?? $item->unit_price;

                $item->update([
                    'quantity_received' => $receivedQty,
                    'unit_price'        => $unitPrice,
                    'total_price'       => $receivedQty * $unitPrice,
                ]);

                if ($item->product) {
                    $item->product->increment('quantity_in_stock', $receivedQty);
                    $this->recordSupplierPrice($order->supplier_id, $item->product_id, (float) $unitPrice, $order->id, $userId);
                }
            }

            $order->calculateTotal();
            $order->markAsReceived();
        });
    }

    /**
     * Create a new product with initial stock movement and optional supplier price linkage.
     *
     * @param  array{shop_id:int,name:string,sku?:string,category_id:int,purchase_price:float,normal_price:float,semi_wholesale_price?:float,wholesale_price?:float,quantity_in_stock:int,brand?:string,type:string,supplier_id?:int} $validated
     */
    public function quickCreateProduct(array $validated, int $userId): Product
    {
        return DB::transaction(function () use ($validated, $userId): Product {
            $product = Product::create([
                'shop_id'               => $validated['shop_id'],
                'name'                  => $validated['name'],
                'sku'                   => $validated['sku'] ?: null,
                'category_id'           => $validated['category_id'],
                'purchase_price'        => $validated['purchase_price'],
                'normal_price'          => $validated['normal_price'],
                'semi_wholesale_price'  => $validated['semi_wholesale_price'] ?: $validated['normal_price'],
                'wholesale_price'       => $validated['wholesale_price'] ?: $validated['normal_price'],
                'quantity_in_stock'     => $validated['quantity_in_stock'],
                'stock_alert_threshold' => 5,
                'brand'                 => $validated['brand'] ?? null,
                'type'                  => $validated['type'],
                'is_active'             => true,
            ]);

            if ($product->quantity_in_stock > 0) {
                StockMovement::create([
                    'shop_id'         => $product->shop_id,
                    'product_id'      => $product->id,
                    'user_id'         => $userId,
                    'type'            => StockMovement::TYPE_ENTRY,
                    'quantity'        => $product->quantity_in_stock,
                    'quantity_before' => 0,
                    'quantity_after'  => $product->quantity_in_stock,
                    'reason'          => 'Stock initial (création depuis facture fournisseur)',
                ]);
            }

            if (!empty($validated['supplier_id'])) {
                SupplierProductPrice::create([
                    'supplier_id'      => $validated['supplier_id'],
                    'product_id'       => $product->id,
                    'unit_price'       => $validated['purchase_price'],
                    'price_updated_at' => now(),
                ]);
            }

            return $product;
        });
    }

    /**
     * Upsert the supplier price for a product and append to price history.
     */
    public function recordSupplierPrice(int $supplierId, int $productId, float $price, ?int $orderId = null, ?int $userId = null): void
    {
        $existing = SupplierProductPrice::where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->updatePrice($price, $orderId, $userId);
        } else {
            SupplierProductPrice::create([
                'supplier_id'      => $supplierId,
                'product_id'       => $productId,
                'unit_price'       => $price,
                'price_updated_at' => now(),
            ]);

            SupplierPriceHistory::create([
                'supplier_id'       => $supplierId,
                'product_id'        => $productId,
                'unit_price'        => $price,
                'supplier_order_id' => $orderId,
                'recorded_by'       => $userId,
                'recorded_at'       => now(),
            ]);
        }
    }
}
