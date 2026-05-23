<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Collection;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    /** Cross-shop lookup — callers must verify ownership when shop isolation matters. */
    public function find(int $id): ?Product
    {
        return Product::withoutGlobalScope('shop')->find($id);
    }

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException — Cross-shop lookup, see find(). */
    public function findOrFail(int $id): Product
    {
        return Product::withoutGlobalScope('shop')->findOrFail($id);
    }

    /** @return Collection<int, Product> */
    public function allForShop(int $shopId): Collection
    {
        return Product::withoutGlobalScope('shop')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Product> */
    public function lowStock(int $shopId): Collection
    {
        return Product::withoutGlobalScope('shop')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->lowStock()
            ->orderBy('quantity_in_stock')
            ->get();
    }

    /** @return Collection<int, Product> */
    public function search(int $shopId, string $term): Collection
    {
        return Product::withoutGlobalScope('shop')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->search($term)
            ->limit(20)
            ->get();
    }

    public function decrementStock(Product $product, int $quantity): void
    {
        $product->decrement('quantity_in_stock', $quantity);
    }

    public function incrementStock(Product $product, int $quantity): void
    {
        $product->increment('quantity_in_stock', $quantity);
    }

    public function hasStock(Product $product, int $quantity): bool
    {
        return $product->hasStock($quantity);
    }
}
