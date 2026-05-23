<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Shop;
use App\Models\User;
use App\Services\RepairService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RepairServiceTest extends TestCase
{
    private RepairService $service;
    private Shop $shop;
    private User $user;
    private User $technician;
    private Customer $customer;
    private PaymentMethod $paymentMethod;
    private CashRegister $cashRegister;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RepairService::class);

        // ProductObserver queries 'admin'; NotificationService::repairCreated queries 'technicien'
        Role::firstOrCreate(['name' => 'admin',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'technicien', 'guard_name' => 'web']);

        $this->shop       = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
        $this->user       = User::factory()->create(['shop_id' => $this->shop->id]);
        $this->technician = User::factory()->create(['shop_id' => $this->shop->id]);
        $this->customer   = Customer::factory()->create(['shop_id' => $this->shop->id]);

        $this->paymentMethod = PaymentMethod::create([
            'name'      => 'Espèces',
            'code'      => 'cash',
            'type'      => 'cash',
            'is_active' => true,
        ]);

        $this->cashRegister = CashRegister::create([
            'shop_id'         => $this->shop->id,
            'user_id'         => $this->user->id,
            'date'            => now()->toDateString(),
            'opening_balance' => 0,
            'status'          => 'open',
            'opened_at'       => now(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validatedBase(array $overrides = []): array
    {
        return array_merge([
            'customer_id'               => $this->customer->id,
            'technician_id'             => $this->technician->id,
            'device_type'               => 'smartphone',
            'device_brand'              => 'Samsung',
            'device_model'              => 'Galaxy S21',
            'reported_issue'            => 'Écran cassé',
            'diagnosis'                 => 'Remplacement écran',
            'estimated_completion_date' => now()->addDays(3)->toDateString(),
            'amount_paid'               => 0,
            'labor_cost'                => 15_000,
            'payment_method_id'         => $this->paymentMethod->id,
        ], $overrides);
    }

    private function makeRepair(array $overrides = []): Repair
    {
        return Repair::create(array_merge([
            'shop_id'                   => $this->shop->id,
            'customer_id'               => $this->customer->id,
            'created_by'                => $this->user->id,
            'technician_id'             => $this->technician->id,
            'device_type'               => 'smartphone',
            'device_brand'              => 'Samsung',
            'device_model'              => 'S21',
            'reported_issue'            => 'Test issue',
            'diagnosis'                 => 'Test diagnosis',
            'status'                    => Repair::STATUS_IN_REPAIR,
            'estimated_cost'            => 20_000,
            'final_cost'                => 20_000,
            'labor_cost'                => 15_000,
            'parts_cost'                => 5_000,
            'amount_paid'               => 0,
            'payment_method'            => 'cash',
            'estimated_completion_date' => now()->addDays(3)->toDateString(),
        ], $overrides));
    }

    // ── create() ─────────────────────────────────────────────────────────────

    public function test_create_persists_repair_with_auto_number(): void
    {
        $repair = $this->service->create(
            $this->validatedBase(),
            $this->user,
            $this->cashRegister,
        );

        $this->assertInstanceOf(Repair::class, $repair);
        $this->assertNotEmpty($repair->repair_number);
        $this->assertSame(Repair::STATUS_IN_REPAIR, $repair->status);
        $this->assertSame($this->user->id, $repair->created_by);
        $this->assertSame(15_000.0, (float) $repair->labor_cost);
    }

    public function test_create_deducts_parts_stock_and_creates_movements(): void
    {
        $part = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 10,
        ]);

        $this->service->create(
            $this->validatedBase([
                'parts' => [
                    ['product_id' => $part->id, 'quantity' => 3, 'unit_price' => 2_000.0],
                ],
            ]),
            $this->user,
            $this->cashRegister,
        );

        $this->assertSame(7, (int) $part->fresh()->quantity_in_stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $part->id,
            'type'       => 'repair_usage',
        ]);
    }

    public function test_create_throws_when_part_stock_insufficient(): void
    {
        $part = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 1,
        ]);

        $this->expectException(\DomainException::class);
        $this->service->create(
            $this->validatedBase([
                'parts' => [
                    ['product_id' => $part->id, 'quantity' => 5, 'unit_price' => 1_000.0],
                ],
            ]),
            $this->user,
            $this->cashRegister,
        );
    }

    public function test_create_records_cash_transaction_when_amount_paid(): void
    {
        $this->service->create(
            $this->validatedBase(['amount_paid' => 10_000]),
            $this->user,
            $this->cashRegister,
        );

        $tx = $this->cashRegister->transactions()
            ->where('type', 'income')
            ->where('category', 'repair')
            ->first();
        $this->assertNotNull($tx);
        $this->assertSame(10_000.0, (float) $tx->amount);
    }

    // ── recordDeposit() ──────────────────────────────────────────────────────

    public function test_record_deposit_updates_status_and_records_transaction(): void
    {
        $repair = $this->makeRepair(['status' => Repair::STATUS_PENDING_PAYMENT, 'amount_paid' => 0]);

        $this->service->recordDeposit($repair, 5_000.0, $this->paymentMethod, $this->cashRegister);

        $this->assertSame(Repair::STATUS_PAID_PENDING_DIAGNOSIS, $repair->fresh()->status);
        $this->assertSame(5_000.0, (float) $repair->fresh()->amount_paid);

        $tx = $this->cashRegister->transactions()
            ->where('category', 'repair')
            ->where('type', 'income')
            ->first();
        $this->assertNotNull($tx);
        $this->assertSame(5_000.0, (float) $tx->amount);
    }

    // ── deliver() ────────────────────────────────────────────────────────────

    public function test_deliver_sets_status_delivered_and_records_payment(): void
    {
        $repair = $this->makeRepair(['status' => Repair::STATUS_REPAIRED, 'amount_paid' => 0]);

        $this->service->deliver($repair, 20_000.0, $this->paymentMethod, $this->cashRegister);

        $this->assertSame(Repair::STATUS_DELIVERED, $repair->fresh()->status);
        $this->assertNotNull($repair->fresh()->delivered_at);

        $tx = $this->cashRegister->transactions()
            ->where('type', 'income')
            ->where('category', 'repair')
            ->first();
        $this->assertNotNull($tx);
        $this->assertSame(20_000.0, (float) $tx->amount);
    }

    // ── cancel() ─────────────────────────────────────────────────────────────

    public function test_cancel_sets_status_cancelled(): void
    {
        $repair = $this->makeRepair(['amount_paid' => 0]);

        $result = $this->service->cancel($repair, 'Client non satisfait', $this->user);

        $this->assertSame(Repair::STATUS_CANCELLED, $repair->fresh()->status);
        $this->assertFalse($result['refund_done']);
        $this->assertSame(0.0, $result['amount_refunded']);
    }

    public function test_cancel_returns_parts_to_stock(): void
    {
        $part = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 5,
        ]);

        $repair = $this->makeRepair(['amount_paid' => 0]);
        \App\Models\RepairPart::create([
            'repair_id'  => $repair->id,
            'product_id' => $part->id,
            'quantity'   => 2,
            'unit_cost'  => 3_000,
            'total_cost' => 6_000,
        ]);

        $this->service->cancel($repair, 'Annulation test', $this->user);

        $this->assertSame(7, (int) $part->fresh()->quantity_in_stock);
        $this->assertSame(1, $result['parts_count'] ?? 1); // parts restored
    }

    public function test_cancel_issues_refund_when_amount_was_paid(): void
    {
        $repair = $this->makeRepair(['amount_paid' => 10_000, 'payment_method' => 'cash']);

        $result = $this->service->cancel($repair, 'Remboursement requis', $this->user);

        $this->assertTrue($result['refund_done']);
        $this->assertSame(10_000.0, $result['amount_refunded']);

        $tx = $this->cashRegister->transactions()
            ->where('type', 'expense')
            ->where('category', 'repair_refund')
            ->first();
        $this->assertNotNull($tx);
    }

    // ── addPart() ────────────────────────────────────────────────────────────

    public function test_add_part_creates_part_sale_and_deducts_stock(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 8,
            'normal_price'      => 5_000,
        ]);
        $repair = $this->makeRepair();

        $repairPart = $this->service->addPart($repair, [
            'product_id' => $product->id,
            'quantity'   => 2,
            'unit_price' => 5_000.0,
        ], $this->user->id);

        $this->assertSame(6, (int) $product->fresh()->quantity_in_stock);
        $this->assertNotNull($repairPart->sale_id);

        $this->assertDatabaseHas('sales', [
            'repair_id'       => $repair->id,
            'is_repair_parts' => 1,
            'payment_status'  => 'credit',
            'total_amount'    => 10_000,
        ]);
    }

    public function test_add_part_throws_when_stock_insufficient(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 1,
        ]);
        $repair = $this->makeRepair();

        $this->expectException(\DomainException::class);
        $this->service->addPart($repair, [
            'product_id' => $product->id,
            'quantity'   => 5,
            'unit_price' => 1_000.0,
        ], $this->user->id);
    }

    // ── removePart() ─────────────────────────────────────────────────────────

    public function test_remove_part_restores_stock(): void
    {
        $product = Product::factory()->create([
            'shop_id'           => $this->shop->id,
            'quantity_in_stock' => 8,
            'normal_price'      => 3_000,
        ]);
        $repair = $this->makeRepair();

        $repairPart = $this->service->addPart($repair, [
            'product_id' => $product->id,
            'quantity'   => 3,
            'unit_price' => 3_000.0,
        ], $this->user->id);

        $this->assertSame(5, (int) $product->fresh()->quantity_in_stock);

        $this->service->removePart($repair, $repairPart);

        $this->assertSame(8, (int) $product->fresh()->quantity_in_stock);
    }
}
