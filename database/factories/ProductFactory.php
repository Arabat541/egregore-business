<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'shop_id'              => null,
            'name'                 => ucwords($name),
            'sku'                  => strtoupper(Str::random(8)),
            'category_id'          => Category::factory(),
            'description'          => null,
            'purchase_price'       => fake()->randomFloat(2, 1000, 50000),
            'normal_price'         => fake()->randomFloat(2, 2000, 100000),
            'reseller_price'       => null,
            'semi_wholesale_price' => null,
            'wholesale_price'      => null,
            'quantity_in_stock'    => fake()->numberBetween(0, 100),
            'stock_alert_threshold' => 5,
            'brand'                => null,
            'model'                => null,
            'type'                 => 'accessory',
            'image'                => null,
            'characteristics'      => null,
            'is_active'            => true,
        ];
    }
}
