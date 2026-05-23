<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\StockTransferService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StockTransferServiceTest extends TestCase
{
    private StockTransferService $service;
    private Shop $shopA;
    private Shop $shopB;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockTransferService::class);

        // ProductObserver::stockLow() queries users by 'admin' role — must exist.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->shopA = Shop::create(['name' => 'Boutique A', 'code' => 'SPA', 'is_active' => true]);
        $this->shopB = Shop::create(['name' => 'Boutique B', 'code' => 'SPB', 'is_active' => true]);
        $this->user  = User::factory()->create(['shop_id' => $this->shopA->id]);
    }

    // ── create() ────────────────────────────────────────────────────────────

    public function test_create_returns_transfer_with_items(): void
    {
        $product = Product::factory()->create([
            'shop_id'          => $this->shopA->id,
            'quantity_in_stock' => 20,
        ]);

        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 5]],
            'Test notes',
        );

        $this->assertInstanceOf(StockTransfer::class, $transfer);
        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame($this->shopA->id, $transfer->from_shop_id);
        $this->assertSame($this->shopB->id, $transfer->to_shop_id);
        $this->assertStringStartsWith('TRF-', $transfer->reference);
        $this->assertCount(1, $transfer->items);
        $this->assertSame(5, (int) $transfer->items->first()->quantity);
    }

    public function test_create_does_not_touch_stock(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'quantity_in_stock' => 20,
        ]);

        $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 5]],
            null,
        );

        // Stock must remain unchanged until ship() is called
        $this->assertSame(20, (int) $product->fresh()->quantity_in_stock);
    }

    public function test_create_stores_multiple_items(): void
    {
        $p1 = Product::factory()->create(['shop_id' => $this->shopA->id, 'quantity_in_stock' => 10]);
        $p2 = Product::factory()->create(['shop_id' => $this->shopA->id, 'quantity_in_stock' => 8]);

        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [
                ['product_id' => $p1->id, 'quantity' => 3],
                ['product_id' => $p2->id, 'quantity' => 2],
            ],
            null,
        );

        $this->assertCount(2, $transfer->items);
    }

    // ── ship() ───────────────────────────────────────────────────────────────

    public function test_ship_deducts_source_stock_and_creates_movement(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'quantity_in_stock' => 20,
        ]);

        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 7]],
            null,
        );

        $this->service->ship($transfer, $this->user->id);

        $this->assertSame(13, (int) $product->fresh()->quantity_in_stock);
        $this->assertSame(StockTransfer::STATUS_IN_TRANSIT, $transfer->fresh()->status);

        $movement = StockMovement::where('product_id', $product->id)
            ->where('type', 'transfer_out')
            ->first();
        $this->assertNotNull($movement);
        $this->assertSame(-7, (int) $movement->quantity);
    }

    public function test_ship_throws_when_not_pending(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'quantity_in_stock' => 10,
        ]);
        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 2]],
            null,
        );

        $this->service->ship($transfer, $this->user->id);

        // ship() a second time must throw
        $this->expectException(\LogicException::class);
        $this->service->ship($transfer->fresh(), $this->user->id);
    }

    public function test_ship_throws_when_stock_insufficient(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'quantity_in_stock' => 3,
        ]);
        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 10]],
            null,
        );

        $this->expectException(\DomainException::class);
        $this->service->ship($transfer, $this->user->id);
    }

    // ── confirmReception() ───────────────────────────────────────────────────

    public function test_confirm_reception_adds_stock_to_destination(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'name'              => 'iPhone 14',
            'sku'               => 'IPH14',
            'quantity_in_stock' => 10,
        ]);
        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 5]],
            null,
        );
        $this->service->ship($transfer, $this->user->id);

        $item = $transfer->fresh()->items->first();

        $hasDiscrepancy = $this->service->confirmReception(
            $transfer->fresh(),
            [['item_id' => $item->id, 'quantity_received' => 5]],
            null,
            $this->user->id,
        );

        $this->assertFalse($hasDiscrepancy);

        $destProduct = Product::withoutGlobalScope('shop')
            ->where('shop_id', $this->shopB->id)
            ->where('name', 'iPhone 14')
            ->first();

        $this->assertNotNull($destProduct);
        $this->assertSame(5, (int) $destProduct->quantity_in_stock);
    }

    public function test_confirm_reception_detects_discrepancy_and_restores_source(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'name'              => 'Galaxy S24',
            'quantity_in_stock' => 10,
        ]);
        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 5]],
            null,
        );
        $this->service->ship($transfer, $this->user->id);

        // Source now at 5. We shipped 5 but only 3 arrive.
        $item = $transfer->fresh()->items->first();

        $hasDiscrepancy = $this->service->confirmReception(
            $transfer->fresh(),
            [['item_id' => $item->id, 'quantity_received' => 3]],
            'One box damaged',
            $this->user->id,
        );

        $this->assertTrue($hasDiscrepancy);

        // 2 missing units restored to source
        $this->assertSame(7, (int) $product->fresh()->quantity_in_stock);

        // Destination received 3
        $destProduct = Product::withoutGlobalScope('shop')
            ->where('shop_id', $this->shopB->id)
            ->where('name', 'Galaxy S24')
            ->first();
        $this->assertSame(3, (int) $destProduct->quantity_in_stock);
    }

    public function test_confirm_reception_throws_when_not_in_transit(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shopA->id,
            'quantity_in_stock' => 10,
        ]);
        $transfer = $this->service->create(
            $this->shopA->id,
            $this->shopB->id,
            $this->user->id,
            [['product_id' => $product->id, 'quantity' => 2]],
            null,
        );

        // Transfer is still pending — confirmReception should throw
        $this->expectException(\LogicException::class);
        $this->service->confirmReception($transfer, [], null, $this->user->id);
    }
}
