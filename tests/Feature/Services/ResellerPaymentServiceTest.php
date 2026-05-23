<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Services\ResellerPaymentService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ResellerPaymentServiceTest extends TestCase
{
    private ResellerPaymentService $service;
    private Shop $shop;
    private User $user;
    private CashRegister $cashRegister;
    private PaymentMethod $paymentMethod;
    private Reseller $reseller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ResellerPaymentService::class);

        // ProductObserver may query users by 'admin' role when stock changes.
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->shop = Shop::create(['name' => 'Test Shop', 'code' => 'TST', 'is_active' => true]);
        $this->user = User::factory()->create(['shop_id' => $this->shop->id]);

        $this->cashRegister = CashRegister::create([
            'shop_id'         => $this->shop->id,
            'user_id'         => $this->user->id,
            'date'            => now()->toDateString(),
            'opening_balance' => 100000,
            'status'          => 'open',
            'opened_at'       => now(),
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name'      => 'Espèces',
            'code'      => 'cash',
            'type'      => 'cash',
            'is_active' => true,
        ]);

        $this->reseller = Reseller::create([
            'shop_id'      => $this->shop->id,
            'company_name' => 'ACME Distrib',
            'contact_name' => 'Konan Yves',
            'phone'        => '0700000000',
            'credit_limit' => 500_000,
            'current_debt' => 200_000,
        ]);
    }

    // ── processPayment() ─────────────────────────────────────────────────────

    public function test_process_payment_reduces_reseller_debt(): void
    {
        $payment = $this->service->processPayment(
            $this->reseller,
            ['cash_amount' => 50_000],
            $this->paymentMethod,
            $this->cashRegister,
            $this->user->id,
            $this->shop->id,
        );

        $this->assertInstanceOf(ResellerPayment::class, $payment);
        $this->assertSame(150_000.0, (float) $this->reseller->fresh()->current_debt);
        $this->assertSame(200_000.0, (float) $payment->debt_before);
        $this->assertSame(150_000.0, (float) $payment->debt_after);
    }

    public function test_process_payment_records_cash_transaction(): void
    {
        $this->service->processPayment(
            $this->reseller,
            ['cash_amount' => 30_000],
            $this->paymentMethod,
            $this->cashRegister,
            $this->user->id,
            $this->shop->id,
        );

        $tx = $this->cashRegister->transactions()
            ->where('type', 'income')
            ->where('category', 'debt_payment')
            ->first();

        $this->assertNotNull($tx);
        $this->assertSame(30_000.0, (float) $tx->amount);
    }

    public function test_process_payment_cash_amount_zero_skips_cash_transaction(): void
    {
        // cash_amount = 0 with a valid payment method → ResellerPayment created but no cash tx
        $this->service->processPayment(
            $this->reseller,
            ['cash_amount' => 0],
            $this->paymentMethod,
            $this->cashRegister,
            $this->user->id,
            $this->shop->id,
        );

        $tx = $this->cashRegister->transactions()
            ->where('type', 'income')
            ->where('category', 'debt_payment')
            ->first();

        $this->assertNull($tx);
    }

    // ── processInvoicePartialPayment() ────────────────────────────────────────

    public function test_invoice_partial_payment_updates_sale_and_debt(): void
    {
        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'user_id'        => $this->user->id,
            'reseller_id'    => $this->reseller->id,
            'client_type'    => 'reseller',
            'subtotal'       => 80_000,
            'discount_amount'=> 0,
            'tax_amount'     => 0,
            'total_amount'   => 80_000,
            'amount_paid'    => 30_000,
            'amount_given'   => 30_000,
            'amount_due'     => 50_000,
            'payment_status' => 'credit',
            'payment_method' => 'cash',
        ]);

        $payment = $this->service->processInvoicePartialPayment(
            $this->reseller,
            $sale,
            20_000.0,
            $this->paymentMethod,
            $this->cashRegister,
            $this->user->id,
        );

        $this->assertInstanceOf(ResellerPayment::class, $payment);

        $freshSale = $sale->fresh();
        $this->assertSame(50_000.0,  (float) $freshSale->amount_paid);
        $this->assertSame(30_000.0,  (float) $freshSale->amount_due);
        $this->assertSame('credit',  $freshSale->payment_status); // not fully paid

        $this->assertSame(180_000.0, (float) $this->reseller->fresh()->current_debt);
    }

    public function test_invoice_partial_payment_marks_paid_when_fully_settled(): void
    {
        $sale = Sale::create([
            'shop_id'        => $this->shop->id,
            'user_id'        => $this->user->id,
            'reseller_id'    => $this->reseller->id,
            'client_type'    => 'reseller',
            'subtotal'       => 50_000,
            'discount_amount'=> 0,
            'tax_amount'     => 0,
            'total_amount'   => 50_000,
            'amount_paid'    => 0,
            'amount_given'   => 0,
            'amount_due'     => 50_000,
            'payment_status' => 'credit',
            'payment_method' => 'cash',
        ]);

        $this->service->processInvoicePartialPayment(
            $this->reseller,
            $sale,
            50_000.0,
            $this->paymentMethod,
            $this->cashRegister,
            $this->user->id,
        );

        $this->assertSame('paid', $sale->fresh()->payment_status);
    }
}
