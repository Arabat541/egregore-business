<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'description' => null,
            'parent_id'   => null,
            'is_active'   => true,
            'sort_order'  => 0,
        ];
    }
}
