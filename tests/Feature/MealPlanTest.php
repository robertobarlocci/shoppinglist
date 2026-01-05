<?php

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_parents_can_create_meal_plans(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->postJson('/api/meal-plans', [
            'date' => '2026-01-01',
            'meal_type' => 'dinner',
            'title' => 'Spaghetti',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('meal_plans', [
            'title' => 'Spaghetti',
            'meal_type' => 'dinner',
        ]);
    }

    public function test_kids_cannot_create_meal_plans(): void
    {
        $kid = User::factory()->create(['role' => 'kid']);

        $response = $this->actingAs($kid)->postJson('/api/meal-plans', [
            'date' => '2026-01-01',
            'meal_type' => 'dinner',
            'title' => 'Candy',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('meal_plans', [
            'title' => 'Candy',
        ]);
    }

    public function test_kids_cannot_delete_meal_plans(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $mealPlan = MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-01-01',
            'meal_type' => 'dinner',
            'title' => 'Healthy Food',
        ]);

        $kid = User::factory()->create(['role' => 'kid']);

        $response = $this->actingAs($kid)->deleteJson("/api/meal-plans/{$mealPlan->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('meal_plans', [
            'id' => $mealPlan->id,
        ]);
    }
}
