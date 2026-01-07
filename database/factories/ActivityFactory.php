<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                Activity::ACTION_ITEM_ADDED,
                Activity::ACTION_ITEM_CHECKED,
                Activity::ACTION_ITEM_DELETED,
            ]),
            'subject_type' => 'Item',
            'subject_id' => fake()->numberBetween(1, 100),
            'subject_name' => fake()->words(2, true),
            'metadata' => null,
        ];
    }
}
