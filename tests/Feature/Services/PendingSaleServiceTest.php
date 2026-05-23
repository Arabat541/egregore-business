<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\PendingSale;
use App\Models\PendingSaleItem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Shop;
use App\Models\User;
use App\Services\PendingSaleService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PendingSaleServiceTest extends TestCase
{
    private PendingSaleService $service;
    private Shop $shop;
    private User $user;
    private Reseller $reseller;
    private CashRegister $cashRegister;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PendingSaleService::class);

        // ProductObserver queries 'admin' role on stock changes
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->shop = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
        $this->user = User::factory()->create(['shop_id' => $this->shop->id]);

        $this->reseller = Reseller::create([
            'shop_id'        => $this->shop->id,
            'company_name'   => 'Distrib Test',
            'contact_name'   => 'Ali Konan',
            'phone'          => '0700000001',
            'credit_limit'   => 1_000_000,
            'current_debt'   => 0,
            'credit_allowed' => true,
        ]);

        $this->cashRegister = CashRegister::create([
            'shop_id'         => $this->shop->id,
            'user_id'         => $this->user->id,
            'date'            => now()->toDateString(),
            'opening_balance' => 0,
            'status'          => 'open',
            'opened_at'       => now(),
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name'      => 'Espèces',
            'code'      => 'cash',
            'type'      => 'cash',
            'is_active' => true,
        ]);
    }

    // ── addItem() ────────────────────────────────────────────────────────────

    public function test_add_item_creates_pending_sale_and_item(): void
    {
        $product = Product::factory()->create([
            'shop_id'          => $this->shop->id,
            'quantity_in_stock' => 10,
        ]);

        $item = $this->service->addItem([
            'reseller_id' => $this->reseller->id,
            'product_id'  => $product->id,
            'quantity'    => 3,
            'unit_price'  => 5_000.0,
        ], $this->user);

        $this->assertInstanceOf(PendingSaleItem::class, $item);
        $this->assertSame(3, (int) $item->quantity);
        $this->assertSame(5_000.0, (float) $item->unit_price);
        $this->assertSame(15_000.0, (float) $item->total_price);

        // PendingSale created
        $this->assertDatabaseHas('pending_sales', [
            'reseller_id' => $this->reseller->id,
            'shop_id'     => $this->shop->id,
            'status'      => 'pending',
        ]);
    }

    public function test_add_item_merges_quantity_for_existing_product(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 20,
        ]);

        $this->service->addItem([
            'reseller_id' => $this->reseller->id,
            'product_id'  => $product->id,
            'quantity'    => 4,
            'unit_price'  => 5_000.0,
        ], $this->user);

        $item = $this->service->addItem([
            'reseller_id' => $this->reseller->id,
            'product_id'  => $product->id,
            'quantity'    => 3,
            'unit_price'  => 5_000.0,
        ], $this->user);

        // Should be merged into a single item with quantity 7
        $this->assertSame(7, (int) $item->quantity);
        $this->assertCount(1, PendingSaleItem::all());
    }

    public function test_add_item_throws_when_merged_quantity_exceeds_stock(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        // First add: 4 — OK
        $this->service->addItem([
            'reseller_id' => $this->reseller->id,
            'product_id'  => $product->id,
            'quantity'    => 4,
            'unit_price'  => 1_000.0,
        ], $this->user);

        // Second add: 3 more → total 7 > 5 available
        $this->expectException(\DomainException::class);
        $this->service->addItem([
            'reseller_id' => $this->reseller->id,
            'product_id'  => $product->id,
            'quantity'    => 3,
            'unit_price'  => 1_000.0,
        ], $this->user);
    }

    public function test_add_item_creates_separate_items_for_different_products(): void
    {
        $p1 = Product::factory()->create(['shop_id' => $this->shop->id, 'quantity_in_stock' => 10]);
        $p2 = Product::factory()->create(['shop_id' => $this->shop->id, 'quantity_in_stock' => 10]);

        $this->service->addItem(['reseller_id' => $this->reseller->id, 'product_id' => $p1->id, 'quantity' => 2, 'unit_price' => 1_000.0], $this->user);
        $this->service->addItem(['reseller_id' => $this->reseller->id, 'product_id' => $p2->id, 'quantity' => 3, 'unit_price' => 2_000.0], $this->user);

        $this->assertCount(2, PendingSaleItem::all());
    }

    // ── validate() ───────────────────────────────────────────────────────────

    public function test_validate_creates_sale_and_deducts_stock(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
            'normal_price'      => 5_000,
        ]);

        $pendingSale = PendingSale::create([
            'shop_id'     => $this->shop->id,
            'reseller_id' => $this->reseller->id,
            'user_id'     => $this->user->id,
            'total_amount'=> 15_000,
            'sale_date'   => today(),
            'status'      => 'pending',
        ]);
        PendingSaleItem::create([
            'pending_sale_id' => $pendingSale->id,
            'product_id'      => $product->id,
            'quantity'        => 3,
            'unit_price'      => 5_000,
            'discount'        => 0,
            'total_price'     => 15_000,
        ]);

        $sale = $this->service->validate(
            $pendingSale,
            $this->paymentMethod,
            15_000.0,
            null,
            $this->cashRegister,
            $this->user,
        );

        // Sale created
        $this->assertNotNull($sale->id);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame(15_000.0, (float) $sale->total_amount);

        // Stock deducted
        $this->assertSame(7, (int) $product->fresh()->quantity_in_stock);

        // Pending sale marked validated
        $this->assertSame('validated', $pendingSale->fresh()->status);
        $this->assertSame($sale->id, $pendingSale->fresh()->sale_id);
    }

    public function test_validate_records_cash_transaction_when_amount_paid(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
            'normal_price'      => 10_000,
        ]);
        $pendingSale = PendingSale::create([
            'shop_id'     => $this->shop->id,
            'reseller_id' => $this->reseller->id,
            'user_id'     => $this->user->id,
            'total_amount'=> 10_000,
            'sale_date'   => today(),
            'status'      => 'pending',
        ]);
        PendingSaleItem::create([
            'pending_sale_id' => $pendingSale->id,
            'product_id'      => $product->id,
            'quantity'        => 1,
            'unit_price'      => 10_000,
            'discount'        => 0,
            'total_price'     => 10_000,
        ]);

        $this->service->validate(
            $pendingSale,
            $this->paymentMethod,
            10_000.0,
            null,
            $this->cashRegister,
            $this->user,
        );

        $tx = $this->cashRegister->transactions()
            ->where('type', 'income')
            ->where('category', 'sale')
            ->first();
        $this->assertNotNull($tx);
        $this->assertSame(10_000.0, (float) $tx->amount);
    }

    public function test_validate_creates_credit_sale_when_reseller_underpays(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
        ]);
        $pendingSale = PendingSale::create([
            'shop_id'     => $this->shop->id,
            'reseller_id' => $this->reseller->id,
            'user_id'     => $this->user->id,
            'total_amount'=> 50_000,
            'sale_date'   => today(),
            'status'      => 'pending',
        ]);
        PendingSaleItem::create([
            'pending_sale_id' => $pendingSale->id,
            'product_id'      => $product->id,
            'quantity'        => 5,
            'unit_price'      => 10_000,
            'discount'        => 0,
            'total_price'     => 50_000,
        ]);

        $sale = $this->service->validate(
            $pendingSale,
            $this->paymentMethod,
            20_000.0, // under-pays by 30 000
            null,
            $this->cashRegister,
            $this->user,
        );

        $this->assertSame('credit', $sale->payment_status);
        $this->assertSame(30_000.0, (float) $sale->amount_due);
        $this->assertSame(30_000.0, (float) $this->reseller->fresh()->current_debt);
    }

    public function test_validate_throws_when_stock_insufficient(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 2, // only 2 available
        ]);
        $pendingSale = PendingSale::create([
            'shop_id'     => $this->shop->id,
            'reseller_id' => $this->reseller->id,
            'user_id'     => $this->user->id,
            'total_amount'=> 30_000,
            'sale_date'   => today(),
            'status'      => 'pending',
        ]);
        PendingSaleItem::create([
            'pending_sale_id' => $pendingSale->id,
            'product_id'      => $product->id,
            'quantity'        => 3, // more than available
            'unit_price'      => 10_000,
            'discount'        => 0,
            'total_price'     => 30_000,
        ]);

        $this->expectException(\DomainException::class);
        $this->service->validate(
            $pendingSale,
            $this->paymentMethod,
            30_000.0,
            null,
            $this->cashRegister,
            $this->user,
        );
    }

    public function test_validate_throws_when_reseller_credit_is_exhausted(): void
    {
        $broke = Reseller::create([
            'shop_id'        => $this->shop->id,
            'company_name'   => 'Broke Distrib',
            'contact_name'   => 'Jean Pauvre',
            'phone'          => '0700000002',
            'credit_limit'   => 10_000,
            'current_debt'   => 10_000, // limit fully used
            'credit_allowed' => true,
        ]);
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);
        $pendingSale = PendingSale::create([
            'shop_id'      => $this->shop->id,
            'reseller_id'  => $broke->id,
            'user_id'      => $this->user->id,
            'total_amount' => 20_000,
            'sale_date'    => today(),
            'status'       => 'pending',
        ]);
        PendingSaleItem::create([
            'pending_sale_id' => $pendingSale->id,
            'product_id'      => $product->id,
            'quantity'        => 2,
            'unit_price'      => 10_000,
            'discount'        => 0,
            'total_price'     => 20_000,
        ]);

        $this->expectException(\DomainException::class);
        $this->service->validate(
            $pendingSale,
            $this->paymentMethod,
            5_000.0, // under-pays by 15 000 but credit is maxed
            null,
            $this->cashRegister,
            $this->user,
        );
    }
}
