<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\SupplierOrderService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class SupplierOrderServiceTest extends TestCase
{
    private SupplierOrderService $service;
    private Shop $shop;
    private User $user;
    private Supplier $supplier;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SupplierOrderService::class);

        // ProductObserver queries 'admin' role on stock changes
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->shop     = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
        $this->user     = User::factory()->create(['shop_id' => $this->shop->id]);
        $this->supplier = Supplier::create([
            'company_name' => 'TechParts SARL',
            'phone'        => '0700000001',
            'is_active'    => true,
        ]);
        $this->category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
    }

    // ── createOrder() ────────────────────────────────────────────────────────

    public function test_create_order_persists_header_and_items(): void
    {
        $p1 = Product::factory()->create(['shop_id' => $this->shop->id, 'quantity_in_stock' => 0]);
        $p2 = Product::factory()->create(['shop_id' => $this->shop->id, 'quantity_in_stock' => 0]);

        $order = $this->service->createOrder([
            'shop_id'     => $this->shop->id,
            'supplier_id' => $this->supplier->id,
            'order_date'  => today()->toDateString(),
            'items'       => [
                ['product_id' => $p1->id, 'quantity' => 10, 'unit_price' => 2_000.0],
                ['product_id' => $p2->id, 'quantity' => 5,  'unit_price' => 4_000.0],
            ],
        ], null, $this->user->id);

        $this->assertInstanceOf(SupplierOrder::class, $order);
        $this->assertNotEmpty($order->reference);
        $this->assertSame('draft', $order->status);
        $this->assertSame(40_000.0, (float) $order->total_amount); // 10*2000 + 5*4000

        $this->assertCount(2, $order->items);
    }

    public function test_create_order_accepts_explicit_invoice_number(): void
    {
        $product = Product::factory()->create(['shop_id' => $this->shop->id]);

        $order = $this->service->createOrder([
            'shop_id'     => $this->shop->id,
            'supplier_id' => $this->supplier->id,
            'order_date'  => today()->toDateString(),
            'items'       => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1_000.0],
            ],
        ], 'INV-2026-001', $this->user->id);

        $this->assertSame('INV-2026-001', $order->reference);
    }

    public function test_create_order_throws_when_product_belongs_to_different_shop(): void
    {
        $otherShop = Shop::create(['name' => 'Other Shop', 'code' => 'OTH', 'is_active' => true]);
        $foreign   = Product::factory()->create(['shop_id' => $otherShop->id]);

        $this->expectException(\RuntimeException::class);
        $this->service->createOrder([
            'shop_id'     => $this->shop->id,
            'supplier_id' => $this->supplier->id,
            'order_date'  => today()->toDateString(),
            'items'       => [
                ['product_id' => $foreign->id, 'quantity' => 1, 'unit_price' => 500.0],
            ],
        ], null, $this->user->id);
    }

    // ── receiveOrder() ───────────────────────────────────────────────────────

    public function test_receive_order_increments_stock_and_updates_purchase_price(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 0,
            'purchase_price'    => 0,
        ]);

        $order = SupplierOrder::create([
            'shop_id'      => $this->shop->id,
            'supplier_id'  => $this->supplier->id,
            'user_id'      => $this->user->id,
            'reference'    => 'TEST-ORDER-001',
            'status'       => 'draft',
            'order_date'   => today()->toDateString(),
            'total_amount' => 0,
        ]);
        $item = SupplierOrderItem::create([
            'supplier_order_id' => $order->id,
            'product_id'        => $product->id,
            'product_name'      => $product->name,
            'quantity_ordered'  => 10,
            'quantity_received' => 0,
            'unit_price'        => 3_000.0,
            'total_price'       => 30_000.0,
        ]);

        $hasDiscrepancy = $this->service->receiveOrder(
            $order,
            [['item_id' => $item->id, 'quantity_received' => 10, 'unit_price' => 3_000.0]],
            null,
            $this->user->id,
        );

        $this->assertFalse($hasDiscrepancy);
        $this->assertSame(10, (int) $product->fresh()->quantity_in_stock);
        $this->assertSame(3_000.0, (float) $product->fresh()->purchase_price);
        $this->assertSame('received', $order->fresh()->status);
    }

    public function test_receive_order_returns_true_when_quantity_differs(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 0,
        ]);

        $order = SupplierOrder::create([
            'shop_id'      => $this->shop->id,
            'supplier_id'  => $this->supplier->id,
            'user_id'      => $this->user->id,
            'reference'    => 'TEST-ORDER-002',
            'status'       => 'draft',
            'order_date'   => today()->toDateString(),
            'total_amount' => 0,
        ]);
        $item = SupplierOrderItem::create([
            'supplier_order_id' => $order->id,
            'product_id'        => $product->id,
            'product_name'      => $product->name,
            'quantity_ordered'  => 10,
            'quantity_received' => 0,
            'unit_price'        => 2_000.0,
            'total_price'       => 20_000.0,
        ]);

        $hasDiscrepancy = $this->service->receiveOrder(
            $order,
            [['item_id' => $item->id, 'quantity_received' => 8, 'unit_price' => 2_000.0]], // only 8 of 10 arrived
            'Partial delivery',
            $this->user->id,
        );

        $this->assertTrue($hasDiscrepancy);
        $this->assertSame(8, (int) $product->fresh()->quantity_in_stock);
    }

    public function test_receive_order_recalculates_cmp_correctly(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
            'purchase_price'    => 1_000.0, // existing 10 units at 1 000
        ]);

        $order = SupplierOrder::create([
            'shop_id'      => $this->shop->id,
            'supplier_id'  => $this->supplier->id,
            'user_id'      => $this->user->id,
            'reference'    => 'TEST-ORDER-003',
            'status'       => 'draft',
            'order_date'   => today()->toDateString(),
            'total_amount' => 0,
        ]);
        $item = SupplierOrderItem::create([
            'supplier_order_id' => $order->id,
            'product_id'        => $product->id,
            'product_name'      => $product->name,
            'quantity_ordered'  => 10,
            'quantity_received' => 0,
            'unit_price'        => 2_000.0,
            'total_price'       => 20_000.0,
        ]);

        $this->service->receiveOrder(
            $order,
            [['item_id' => $item->id, 'quantity_received' => 10, 'unit_price' => 2_000.0]],
            null,
            $this->user->id,
        );

        // CMP = (10 * 1000 + 10 * 2000) / 20 = 30000 / 20 = 1500
        $this->assertSame(1_500.0, (float) $product->fresh()->purchase_price);
        $this->assertSame(20, (int) $product->fresh()->quantity_in_stock);
    }

    // ── quickCreateProduct() ─────────────────────────────────────────────────

    public function test_quick_create_product_creates_product_and_initial_stock_movement(): void
    {
        $product = $this->service->quickCreateProduct([
            'shop_id'              => $this->shop->id,
            'name'                 => 'New Gadget',
            'sku'                  => 'NG-001',
            'category_id'          => $this->category->id,
            'purchase_price'       => 5_000.0,
            'normal_price'         => 8_000.0,
            'semi_wholesale_price' => 7_000.0,
            'wholesale_price'      => 6_000.0,
            'quantity_in_stock'    => 20,
            'type'                 => 'product',
        ], $this->user->id);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame(20, (int) $product->quantity_in_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type'       => 'entry',
            'quantity'   => 20,
        ]);
    }

    public function test_quick_create_product_links_supplier_price_when_supplier_given(): void
    {
        $product = $this->service->quickCreateProduct([
            'shop_id'              => $this->shop->id,
            'name'                 => 'Linked Product',
            'sku'                  => '',
            'category_id'          => $this->category->id,
            'purchase_price'       => 3_000.0,
            'normal_price'         => 5_000.0,
            'semi_wholesale_price' => 4_500.0,
            'wholesale_price'      => 4_000.0,
            'quantity_in_stock'    => 5,
            'type'                 => 'product',
            'supplier_id'          => $this->supplier->id,
        ], $this->user->id);

        $this->assertDatabaseHas('supplier_product_prices', [
            'supplier_id' => $this->supplier->id,
            'product_id'  => $product->id,
            'unit_price'  => 3_000.0,
        ]);
    }
}
