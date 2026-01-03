<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\RecurringSchedule;
use App\Services\RecurringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_can_set_recurring_schedule_on_inventory_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_INVENTORY,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/items/{$item->id}/recurring", [
            'monday' => true,
            'wednesday' => true,
            'friday' => true,
            'tuesday' => false,
            'thursday' => false,
            'saturday' => false,
            'sunday' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('recurring_schedules', [
            'item_id' => $item->id,
            'monday' => true,
            'wednesday' => true,
            'friday' => true,
        ]);
    }

    public function test_cannot_set_recurring_on_non_inventory_item()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/items/{$item->id}/recurring", [
            'monday' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_recurring_service_creates_items_for_today()
    {
        $item = Item::factory()->create([
            'name' => 'Test Recurring Item',
            'list_type' => Item::LIST_TYPE_INVENTORY,
        ]);

        $dayOfWeek = strtolower(now()->format('l'));

        RecurringSchedule::create([
            'item_id' => $item->id,
            $dayOfWeek => true,
        ]);

        $service = app(RecurringService::class);
        $result = $service->checkRecurringItems();

        $this->assertEquals(1, $result['created_count']);
        $this->assertDatabaseHas('items', [
            'name' => 'Test Recurring Item',
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'recurring_source_id' => $item->id,
        ]);
    }

    public function test_recurring_service_does_not_create_duplicate_items()
    {
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_INVENTORY,
        ]);

        $dayOfWeek = strtolower(now()->format('l'));

        $schedule = RecurringSchedule::create([
            'item_id' => $item->id,
            $dayOfWeek => true,
        ]);

        $service = app(RecurringService::class);

        // First run
        $result1 = $service->checkRecurringItems();
        $this->assertEquals(1, $result1['created_count']);

        // Second run - should not create duplicate
        $result2 = $service->checkRecurringItems();
        $this->assertEquals(0, $result2['created_count']);
    }

    public function test_can_remove_recurring_schedule()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_INVENTORY,
            'created_by' => $user->id,
        ]);

        RecurringSchedule::create([
            'item_id' => $item->id,
            'monday' => true,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/items/{$item->id}/recurring");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('recurring_schedules', [
            'item_id' => $item->id,
        ]);
    }

    public function test_recurring_item_is_deleted_when_checked()
    {
        $user = User::factory()->create();
        $sourceItem = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_INVENTORY,
        ]);

        $recurringItem = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'recurring_source_id' => $sourceItem->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/items/{$recurringItem->id}/move", [
            'to_list' => 'inventory',
        ]);

        $response->assertStatus(200);

        // Recurring item should be deleted
        $this->assertSoftDeleted('items', [
            'id' => $recurringItem->id,
        ]);

        // Source item should still exist
        $this->assertDatabaseHas('items', [
            'id' => $sourceItem->id,
            'deleted_at' => null,
        ]);
    }
}
