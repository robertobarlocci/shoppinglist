<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'quantity' => fake()->optional()->randomElement(['1kg', '500g', '2L', '6 Stück']),
            'category_id' => Category::factory(),
            'list_type' => fake()->randomElement(['quick_buy', 'to_buy', 'inventory']),
            'created_by' => User::factory(),
        ];
    }
}
