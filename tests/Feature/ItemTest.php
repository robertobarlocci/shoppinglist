<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_user_can_create_item()
    {
        $user = User::factory()->create();
        $category = Category::first();

        $response = $this->actingAs($user)->postJson('/api/items', [
            'name' => 'Test Item',
            'quantity' => '1kg',
            'category_id' => $category->id,
            'list_type' => 'to_buy',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'quantity', 'list_type', 'category']
            ]);

        $this->assertDatabaseHas('items', [
            'name' => 'Test Item',
            'list_type' => 'to_buy',
        ]);
    }

    public function test_user_can_move_item_between_lists()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/items/{$item->id}/move", [
            'to_list' => 'inventory',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'list_type' => 'inventory',
        ]);
    }

    public function test_user_can_delete_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('items', [
            'id' => $item->id,
        ]);
    }

    public function test_user_can_restore_item_from_trash()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'created_by' => $user->id,
            'deleted_from' => 'to_buy',
        ]);
        $item->delete();

        $response = $this->actingAs($user)->postJson("/api/items/{$item->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_search_suggests_items_from_inventory()
    {
        $user = User::factory()->create();
        Item::factory()->create([
            'name' => 'Milch',
            'list_type' => Item::LIST_TYPE_INVENTORY,
        ]);

        $response = $this->actingAs($user)->getJson('/api/items/suggest?q=Mil');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Milch']);
    }

    public function test_items_are_grouped_by_category()
    {
        $user = User::factory()->create();
        $dairy = Category::where('slug', 'dairy')->first();
        $bakery = Category::where('slug', 'bakery')->first();

        Item::factory()->create([
            'name' => 'Milch',
            'category_id' => $dairy->id,
            'list_type' => Item::LIST_TYPE_TO_BUY,
        ]);

        Item::factory()->create([
            'name' => 'Brot',
            'category_id' => $bakery->id,
            'list_type' => Item::LIST_TYPE_TO_BUY,
        ]);

        $response = $this->actingAs($user)->getJson('/api/items?list_type=to_buy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'grouped',
                'total',
            ]);
    }

    public function test_quick_buy_item_can_be_created()
    {
        $user = User::factory()->create();
        $category = Category::first();

        $response = $this->actingAs($user)->postJson('/api/items', [
            'name' => 'Red Bull',
            'list_type' => 'quick_buy',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('items', [
            'name' => 'Red Bull',
            'list_type' => 'quick_buy',
        ]);
    }

    public function test_guest_cannot_access_items()
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(401);
    }
}
