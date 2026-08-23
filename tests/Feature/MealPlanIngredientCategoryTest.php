<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #2 (M5) — meal-plan ingredients must land on the shopping list with the category
 * their name already has, instead of always defaulting to "Sonstiges".
 */
final class MealPlanIngredientCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Category $other;

    private Category $beverages;

    private MealPlan $mealPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->parent = User::factory()->create(['role' => 'parent']);
        $this->other = Category::where('slug', 'other')->firstOrFail();
        $this->beverages = Category::where('slug', 'beverages')->firstOrFail();

        $this->mealPlan = MealPlan::create([
            'date' => '2026-01-01',
            'meal_type' => 'dinner',
            'title' => 'Pizza',
            'user_id' => $this->parent->id,
        ]);
    }

    /** Test 14 — an ingredient linked through item_id inherits that item's category. */
    public function test_linked_ingredient_inherits_the_linked_items_category(): void
    {
        $known = Item::create([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
            'created_by' => $this->parent->id,
        ]);

        $this->actingAs($this->parent)->postJson("/api/meal-plans/{$this->mealPlan->id}/ingredients", [
            'name' => 'Cola',
            'quantity' => '1L',
            'item_id' => $known->id,
        ])->assertSuccessful();

        $this->addToShoppingList();

        $created = Item::where('list_type', 'to_buy')->firstOrFail();
        $this->assertSame($this->beverages->id, $created->category_id);
    }

    /** Test 15 — an unlinked ingredient whose name is known still inherits the category. */
    public function test_unlinked_ingredient_inherits_the_category_of_its_name(): void
    {
        Item::create([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
            'created_by' => $this->parent->id,
        ]);

        $this->actingAs($this->parent)->postJson("/api/meal-plans/{$this->mealPlan->id}/ingredients", [
            'name' => 'cola',
            'quantity' => '1L',
        ])->assertSuccessful();

        $this->addToShoppingList();

        $created = Item::where('list_type', 'to_buy')->firstOrFail();
        $this->assertSame(
            $this->beverages->id,
            $created->category_id,
            'A hand-typed ingredient must not reset the category of a known name.',
        );
    }

    /** Test 15b — an unknown ingredient name still falls back to the default. */
    public function test_unknown_ingredient_falls_back_to_the_default_category(): void
    {
        $this->actingAs($this->parent)->postJson("/api/meal-plans/{$this->mealPlan->id}/ingredients", [
            'name' => 'Völlig Neues Ding',
        ])->assertSuccessful();

        $this->addToShoppingList();

        $created = Item::where('list_type', 'to_buy')->firstOrFail();
        $this->assertSame($this->other->id, $created->category_id);
    }

    /** Test 16 — an existing shopping-list row is matched case-insensitively. */
    public function test_existing_shopping_list_row_is_matched_case_insensitively(): void
    {
        Item::create([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'to_buy',
            'created_by' => $this->parent->id,
        ]);

        $this->actingAs($this->parent)->postJson("/api/meal-plans/{$this->mealPlan->id}/ingredients", [
            'name' => 'cola',
        ])->assertSuccessful();

        $this->addToShoppingList();

        $this->assertSame(
            1,
            Item::where('list_type', 'to_buy')->count(),
            '"cola" must not create a second shopping-list row next to "Cola".',
        );
    }

    private function addToShoppingList(): void
    {
        $this->actingAs($this->parent)
            ->postJson("/api/meal-plans/{$this->mealPlan->id}/add-to-shopping-list")
            ->assertOk();
    }
}
