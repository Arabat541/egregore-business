<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id'    => null,
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'phone'      => fake()->unique()->numerify('+2250########'),
            'email'      => fake()->unique()->safeEmail(),
            'address'    => null,
            'notes'      => null,
            'is_active'  => true,
        ];
    }
}
