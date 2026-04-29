<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->words(3, true),
            'category_id' => Category::factory(),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 1, 999),
            'stock'       => $this->faker->numberBetween(0, 200),
            'enabled'     => $this->faker->boolean(80),
        ];
    }
}
