<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProductRepositoryTest extends TestCase
{
    private ProductRepositoryInterface $repo;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // ProductObserver queries 'admin' on stock changes
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->repo = app(ProductRepositoryInterface::class);
        $this->shop = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
    }

    public function test_find_returns_product_by_id(): void
    {
        $product = Product::factory()->create(['shop_id' => $this->shop->id]);

        $found = $this->repo->find($product->id);

        $this->assertNotNull($found);
        $this->assertSame($product->id, $found->id);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->repo->find(999_999));
    }

    public function test_find_or_fail_returns_product(): void
    {
        $product = Product::factory()->create(['shop_id' => $this->shop->id]);

        $found = $this->repo->findOrFail($product->id);

        $this->assertSame($product->id, $found->id);
    }

    public function test_find_or_fail_throws_for_unknown_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repo->findOrFail(999_999);
    }

    public function test_all_for_shop_returns_only_active_products_of_that_shop(): void
    {
        $other = Shop::create(['name' => 'Other', 'code' => 'OTH', 'is_active' => true]);

        Product::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        Product::factory()->create(['shop_id' => $this->shop->id, 'is_active' => true]);
        Product::factory()->create(['shop_id' => $this->shop->id, 'is_active' => false]); // inactive
        Product::factory()->create(['shop_id' => $other->id,      'is_active' => true]);  // other shop

        $products = $this->repo->allForShop($this->shop->id);

        $this->assertCount(2, $products);
        $products->each(fn ($p) => $this->assertSame($this->shop->id, $p->shop_id));
    }

    public function test_low_stock_returns_products_at_or_below_threshold(): void
    {
        Product::factory()->create([
            'shop_id'               => $this->shop->id,
            'quantity_in_stock'     => 2,
            'stock_alert_threshold' => 5,
            'is_active'             => true,
        ]);
        Product::factory()->create([
            'shop_id'               => $this->shop->id,
            'quantity_in_stock'     => 10,
            'stock_alert_threshold' => 5,
            'is_active'             => true,
        ]);

        $lowStock = $this->repo->lowStock($this->shop->id);

        $this->assertCount(1, $lowStock);
        $this->assertSame(2, (int) $lowStock->first()->quantity_in_stock);
    }

    public function test_search_returns_matching_products(): void
    {
        Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Samsung Galaxy S21', 'is_active' => true]);
        Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'iPhone 14',          'is_active' => true]);

        $results = $this->repo->search($this->shop->id, 'Samsung');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Samsung', $results->first()->name);
    }

    public function test_search_does_not_return_products_from_other_shops(): void
    {
        $other = Shop::create(['name' => 'Other', 'code' => 'OT2', 'is_active' => true]);
        Product::factory()->create(['shop_id' => $other->id,      'name' => 'Samsung Note', 'is_active' => true]);
        Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Pixel 7',      'is_active' => true]);

        $results = $this->repo->search($this->shop->id, 'Samsung');

        $this->assertCount(0, $results);
    }

    public function test_decrement_stock_reduces_quantity(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
        ]);

        $this->repo->decrementStock($product, 3);

        $this->assertSame(7, (int) $product->fresh()->quantity_in_stock);
    }

    public function test_increment_stock_increases_quantity(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        $this->repo->incrementStock($product, 4);

        $this->assertSame(9, (int) $product->fresh()->quantity_in_stock);
    }

    public function test_has_stock_returns_true_when_sufficient(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
        ]);

        $this->assertTrue($this->repo->hasStock($product, 10));
        $this->assertFalse($this->repo->hasStock($product, 11));
    }
}
