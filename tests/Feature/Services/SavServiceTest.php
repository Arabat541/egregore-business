<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\SavTicket;
use App\Models\Shop;
use App\Models\User;
use App\Services\SavService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class SavServiceTest extends TestCase
{
    private SavService $service;
    private Shop $shop;
    private User $user;
    private CashRegister $cashRegister;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SavService::class);

        // ProductObserver and NotificationService both query roles
        Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'caissiere', 'guard_name' => 'web']);

        $this->shop = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
        $this->user = User::factory()->create(['shop_id' => $this->shop->id]);

        $this->cashRegister = CashRegister::create([
            'shop_id'         => $this->shop->id,
            'user_id'         => $this->user->id,
            'date'            => now()->toDateString(),
            'opening_balance' => 0,
            'status'          => 'open',
            'opened_at'       => now(),
        ]);
    }

    // ── createTicket() ───────────────────────────────────────────────────────

    public function test_create_ticket_persists_ticket_with_generated_number(): void
    {
        $ticket = $this->service->createTicket([
            'shop_id'           => $this->shop->id,
            'type'              => 'return',
            'issue_description' => 'Screen broken',
            'priority'          => 'medium',
        ], $this->user->id);

        $this->assertInstanceOf(SavTicket::class, $ticket);
        $this->assertNotEmpty($ticket->ticket_number);
        $this->assertSame('open',   $ticket->status);
        $this->assertSame('medium', $ticket->priority);
        $this->assertSame($this->user->id, $ticket->created_by);

        $this->assertDatabaseHas('sav_tickets', [
            'shop_id' => $this->shop->id,
            'type'    => 'return',
            'status'  => 'open',
        ]);
    }

    public function test_create_ticket_generates_unique_ticket_numbers(): void
    {
        $t1 = $this->service->createTicket([
            'shop_id'           => $this->shop->id,
            'type'              => 'return',
            'issue_description' => 'Issue 1',
            'priority'          => 'low',
        ], $this->user->id);

        $t2 = $this->service->createTicket([
            'shop_id'           => $this->shop->id,
            'type'              => 'warranty',
            'issue_description' => 'Issue 2',
            'priority'          => 'low',
        ], $this->user->id);

        $this->assertNotSame($t1->ticket_number, $t2->ticket_number);
    }

    // ── processStockReturn() ─────────────────────────────────────────────────

    public function test_process_stock_return_increments_stock_for_good_condition(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-001',
            'type'              => 'return',
            'issue_description' => 'Defective screen',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
        ]);

        $result = $this->service->processStockReturn($ticket, [
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3, 'condition' => 'good'],
            ],
        ], $this->user->id);

        $this->assertSame(3, $result['total_returned']);
        $this->assertSame(8, (int) $product->fresh()->quantity_in_stock);
        $this->assertTrue((bool) $ticket->fresh()->stock_returned);
        $this->assertSame(3, (int) $ticket->fresh()->quantity_returned);
    }

    public function test_process_stock_return_skips_stock_for_damaged_condition(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-002',
            'type'              => 'return',
            'issue_description' => 'Damaged item',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
        ]);

        $result = $this->service->processStockReturn($ticket, [
            'products'       => [
                ['product_id' => $product->id, 'quantity' => 2, 'condition' => 'damaged'],
            ],
            'refund_damaged' => false,
        ], $this->user->id);

        // Stock unchanged — damaged items not re-shelved
        $this->assertSame(0, $result['total_returned']);
        $this->assertSame(0.0, $result['total_refund']);
        $this->assertSame(5, (int) $product->fresh()->quantity_in_stock);
    }

    public function test_process_stock_return_creates_cash_transaction_when_refund_positive(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
            'normal_price'      => 5_000,
        ]);

        // Link a sale so calculateRefundAmount can price the item
        $sale = \App\Models\Sale::create([
            'shop_id'         => $this->shop->id,
            'user_id'         => $this->user->id,
            'client_type'     => 'customer',
            'subtotal'        => 10_000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total_amount'    => 10_000,
            'amount_paid'     => 10_000,
            'amount_given'    => 10_000,
            'amount_due'      => 0,
            'payment_status'  => 'paid',
            'payment_method'  => 'cash',
        ]);
        \App\Models\SaleItem::create([
            'sale_id'     => $sale->id,
            'product_id'  => $product->id,
            'quantity'    => 2,
            'unit_price'  => 5_000,
            'discount'    => 0,
            'total_price' => 10_000,
        ]);

        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-003',
            'sale_id'           => $sale->id,
            'type'              => 'return',
            'issue_description' => 'Return with refund',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
        ]);

        $result = $this->service->processStockReturn($ticket, [
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2, 'condition' => 'good'],
            ],
        ], $this->user->id);

        $this->assertSame(10_000.0, $result['total_refund']);

        $tx = $this->cashRegister->transactions()
            ->where('type', 'expense')
            ->where('category', 'sav_refund')
            ->first();
        $this->assertNotNull($tx);
        $this->assertSame(10_000.0, (float) $tx->amount);
    }

    public function test_process_stock_return_throws_when_already_returned(): void
    {
        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-004',
            'type'              => 'return',
            'issue_description' => 'Already returned',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
            'stock_returned'    => true,
        ]);

        $this->expectException(\LogicException::class);
        $this->service->processStockReturn($ticket, [
            'products' => [],
        ], $this->user->id);
    }

    // ── cancelStockReturn() ──────────────────────────────────────────────────

    public function test_cancel_stock_return_reverses_stock_increment(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-005',
            'type'              => 'return',
            'issue_description' => 'Cancel test',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
        ]);

        // First: perform a stock return
        $this->service->processStockReturn($ticket, [
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3, 'condition' => 'new'],
            ],
        ], $this->user->id);

        $this->assertSame(8, (int) $product->fresh()->quantity_in_stock);

        // Then: cancel it
        $this->service->cancelStockReturn($ticket->fresh(), $this->user->id);

        $this->assertSame(5, (int) $product->fresh()->quantity_in_stock);
        $this->assertFalse((bool) $ticket->fresh()->stock_returned);
    }

    public function test_cancel_stock_return_throws_when_not_returned(): void
    {
        $ticket = SavTicket::create([
            'shop_id'           => $this->shop->id,
            'ticket_number'     => 'SAV-TEST-006',
            'type'              => 'return',
            'issue_description' => 'Nothing to cancel',
            'priority'          => 'medium',
            'status'            => 'open',
            'created_by'        => $this->user->id,
            'stock_returned'    => false,
        ]);

        $this->expectException(\LogicException::class);
        $this->service->cancelStockReturn($ticket, $this->user->id);
    }
}
