<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\SaleService;
use PHPUnit\Framework\TestCase;

final class SaleServiceTest extends TestCase
{
    private SaleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SaleService();
    }

    // ── calculateCorrectPrice ────────────────────────────────────────────────

    public function test_non_reseller_always_gets_normal_price(): void
    {
        $product = $this->makeProduct([
            'normal_price'     => 1000.0,
            'reseller_price'   => 800.0,
            'wholesale_price'  => 600.0,
        ]);

        $this->assertSame(1000.0, $this->service->calculateCorrectPrice($product, 1,  'customer'));
        $this->assertSame(1000.0, $this->service->calculateCorrectPrice($product, 50, 'customer'));
        $this->assertSame(1000.0, $this->service->calculateCorrectPrice($product, 1,  'walk-in'));
    }

    public function test_reseller_below_semi_wholesale_threshold_gets_reseller_price(): void
    {
        $product = $this->makeProduct([
            'normal_price'          => 1000.0,
            'reseller_price'        => 800.0,
            'semi_wholesale_price'  => 700.0,
            'wholesale_price'       => 600.0,
            'qty_semi_wholesale_min'=> 3,
            'qty_wholesale_min'     => 10,
        ]);

        $this->assertSame(800.0, $this->service->calculateCorrectPrice($product, 1, 'reseller'));
        $this->assertSame(800.0, $this->service->calculateCorrectPrice($product, 2, 'reseller'));
    }

    public function test_reseller_at_semi_wholesale_threshold_gets_semi_wholesale_price(): void
    {
        $product = $this->makeProduct([
            'normal_price'          => 1000.0,
            'reseller_price'        => 800.0,
            'semi_wholesale_price'  => 700.0,
            'wholesale_price'       => 600.0,
            'qty_semi_wholesale_min'=> 3,
            'qty_wholesale_min'     => 10,
        ]);

        $this->assertSame(700.0, $this->service->calculateCorrectPrice($product, 3, 'reseller'));
        $this->assertSame(700.0, $this->service->calculateCorrectPrice($product, 9, 'reseller'));
    }

    public function test_reseller_at_wholesale_threshold_gets_wholesale_price(): void
    {
        $product = $this->makeProduct([
            'normal_price'          => 1000.0,
            'reseller_price'        => 800.0,
            'semi_wholesale_price'  => 700.0,
            'wholesale_price'       => 600.0,
            'qty_semi_wholesale_min'=> 3,
            'qty_wholesale_min'     => 10,
        ]);

        $this->assertSame(600.0, $this->service->calculateCorrectPrice($product, 10, 'reseller'));
        $this->assertSame(600.0, $this->service->calculateCorrectPrice($product, 50, 'reseller'));
    }

    public function test_reseller_falls_back_when_semi_wholesale_price_is_null(): void
    {
        $product = $this->makeProduct([
            'normal_price'         => 1000.0,
            'reseller_price'       => 800.0,
            'semi_wholesale_price' => null,
            'wholesale_price'      => 600.0,
            'qty_semi_wholesale_min'=> 3,
            'qty_wholesale_min'    => 10,
        ]);

        // semi-wholesale threshold but no semi_wholesale_price → fall back to reseller_price
        $this->assertSame(800.0, $this->service->calculateCorrectPrice($product, 5, 'reseller'));
    }

    public function test_reseller_falls_back_to_semi_wholesale_when_wholesale_is_null(): void
    {
        $product = $this->makeProduct([
            'normal_price'          => 1000.0,
            'reseller_price'        => 800.0,
            'semi_wholesale_price'  => 700.0,
            'wholesale_price'       => null,
            'qty_semi_wholesale_min'=> 3,
            'qty_wholesale_min'     => 10,
        ]);

        // wholesale threshold but no wholesale_price → fall back to semi_wholesale_price
        $this->assertSame(700.0, $this->service->calculateCorrectPrice($product, 10, 'reseller'));
    }

    public function test_default_quantity_thresholds_are_used_when_not_set(): void
    {
        $product = $this->makeProduct([
            'normal_price'          => 1000.0,
            'reseller_price'        => 800.0,
            'semi_wholesale_price'  => 700.0,
            'wholesale_price'       => 600.0,
            'qty_semi_wholesale_min'=> null,  // should default to 3
            'qty_wholesale_min'     => null,  // should default to 10
        ]);

        $this->assertSame(800.0, $this->service->calculateCorrectPrice($product, 2,  'reseller'));
        $this->assertSame(700.0, $this->service->calculateCorrectPrice($product, 3,  'reseller'));
        $this->assertSame(600.0, $this->service->calculateCorrectPrice($product, 10, 'reseller'));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Creates a Product model instance without persisting to DB.
     * Sets attributes directly on the model so service logic can read them.
     *
     * @param array<string, mixed> $attributes
     */
    private function makeProduct(array $attributes): Product
    {
        $product = new Product();
        foreach ($attributes as $key => $value) {
            $product->{$key} = $value;
        }
        return $product;
    }
}
