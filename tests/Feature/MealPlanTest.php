<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\MealPlanSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

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

    public function test_store_finds_image_despite_casing_difference(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        // Create a meal with an image
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-01-01',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Add "pizza" (lowercase) to another day
        $response = $this->actingAs($parent)->postJson('/api/meal-plans', [
            'date' => '2026-01-02',
            'meal_type' => 'lunch',
            'title' => 'pizza',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.image_url'));
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-01-02',
            'title' => 'pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);
    }

    public function test_update_changes_image_when_title_changes(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $mealPlan = MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-01-05',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-01-06',
            'meal_type' => 'lunch',
            'title' => 'Pasta',
            'image_path' => 'meal-images/pasta.jpg',
        ]);

        $response = $this->actingAs($parent)->putJson("/api/meal-plans/{$mealPlan->id}", [
            'title' => 'Pasta',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('pasta.jpg', $response->json('data.image_url'));
    }

    public function test_approve_suggestion_clears_old_image_on_mismatch(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-01-05',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        $suggestion = MealPlanSuggestion::create([
            'user_id' => $kid->id,
            'date' => '2026-01-05',
            'meal_type' => 'lunch',
            'title' => 'Pasta',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($parent)->postJson("/api/meal-suggestions/{$suggestion->id}/approve");

        $response->assertStatus(200);
        $this->assertNull($response->json('meal_plan.image_path'));
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-01-05',
            'title' => 'Pasta',
            'image_path' => null,
        ]);
    }

    public function test_store_preserves_image_when_same_title_resubmitted(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        // Create a meal with an image directly in the DB
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Re-submit the exact same slot with the same title
        $response = $this->actingAs($parent)->postJson('/api/meal-plans', [
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
        ]);

        $response->assertStatus(200); // updateOrCreate returns 200 on update
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);
    }

    public function test_store_replaces_image_when_title_changes_to_meal_with_image(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        // Slot has Pizza with image
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-01',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Pasta exists elsewhere with its own image
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-02',
            'meal_type' => 'lunch',
            'title' => 'Pasta',
            'image_path' => 'meal-images/pasta.jpg',
        ]);

        // Overwrite slot with Pasta
        $response = $this->actingAs($parent)->postJson('/api/meal-plans', [
            'date' => '2026-02-01',
            'meal_type' => 'lunch',
            'title' => 'Pasta',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-01',
            'meal_type' => 'lunch',
            'title' => 'Pasta',
            'image_path' => 'meal-images/pasta.jpg',
        ]);
    }

    public function test_store_clears_image_when_title_changes_to_unknown_meal(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        // Slot has Pizza with image
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Overwrite slot with a new meal that has no image anywhere
        $response = $this->actingAs($parent)->postJson('/api/meal-plans', [
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Tacos',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-01',
            'meal_type' => 'dinner',
            'title' => 'Tacos',
            'image_path' => null,
        ]);
    }

    public function test_approve_preserves_image_for_same_title(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        // Slot has Pizza with image
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-03',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Kid suggests same meal for same slot
        $suggestion = MealPlanSuggestion::create([
            'user_id' => $kid->id,
            'date' => '2026-02-03',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($parent)->postJson("/api/meal-suggestions/{$suggestion->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-03',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);
    }

    public function test_approve_carries_image_for_known_title(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        // Pizza exists with image on another day
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-01',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Kid suggests Pizza for an empty slot
        $suggestion = MealPlanSuggestion::create([
            'user_id' => $kid->id,
            'date' => '2026-02-04',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($parent)->postJson("/api/meal-suggestions/{$suggestion->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-04',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);
    }

    public function test_approve_carries_image_despite_case_difference(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        // Pizza exists with image (uppercase P)
        MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-01',
            'meal_type' => 'lunch',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Kid suggests "pizza" (lowercase)
        $suggestion = MealPlanSuggestion::create([
            'user_id' => $kid->id,
            'date' => '2026-02-05',
            'meal_type' => 'lunch',
            'title' => 'pizza',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($parent)->postJson("/api/meal-suggestions/{$suggestion->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('meal_plans', [
            'date' => '2026-02-05',
            'meal_type' => 'lunch',
            'title' => 'pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);
    }

    public function test_update_preserves_image_when_title_unchanged(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $mealPlan = MealPlan::create([
            'user_id' => $parent->id,
            'date' => '2026-02-07',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'image_path' => 'meal-images/pizza.jpg',
        ]);

        // Update only the date, not the title
        $response = $this->actingAs($parent)->putJson("/api/meal-plans/{$mealPlan->id}", [
            'date' => '2026-02-08',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('pizza.jpg', $response->json('data.image_url'));
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
