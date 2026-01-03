<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'color' => fake()->hexColor(),
            'icon' => 'box',
            'is_default' => false,
            'sort_order' => 0,
        ];
    }
}
