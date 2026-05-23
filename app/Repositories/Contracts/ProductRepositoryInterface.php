<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function find(int $id): ?Product;

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    public function findOrFail(int $id): Product;

    /** @return Collection<int, Product> */
    public function allForShop(int $shopId): Collection;

    /** @return Collection<int, Product> */
    public function lowStock(int $shopId): Collection;

    /**
     * @return Collection<int, Product>
     */
    public function search(int $shopId, string $term): Collection;

    public function decrementStock(Product $product, int $quantity): void;

    public function incrementStock(Product $product, int $quantity): void;

    public function hasStock(Product $product, int $quantity): bool;
}
