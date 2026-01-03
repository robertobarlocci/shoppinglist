<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Activity;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_activity_is_logged_when_item_is_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/items', [
            'name' => 'Test Item',
            'list_type' => 'to_buy',
        ]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action' => Activity::ACTION_ITEM_ADDED,
            'subject_name' => 'Test Item',
        ]);
    }

    public function test_activity_is_logged_when_quick_buy_is_created()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/items', [
            'name' => 'Quick Item',
            'list_type' => 'quick_buy',
        ]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action' => Activity::ACTION_QUICK_BUY_ADDED,
            'subject_name' => 'Quick Item',
        ]);
    }

    public function test_activity_is_logged_when_item_is_checked()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->postJson("/api/items/{$item->id}/move", [
            'to_list' => 'inventory',
        ]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action' => Activity::ACTION_ITEM_CHECKED,
            'subject_id' => $item->id,
        ]);
    }

    public function test_activity_is_logged_when_item_is_deleted()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->deleteJson("/api/items/{$item->id}");

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action' => Activity::ACTION_ITEM_DELETED,
            'subject_id' => $item->id,
        ]);
    }

    public function test_user_can_fetch_activities()
    {
        $user = User::factory()->create();

        Activity::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/activities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'action', 'icon', 'description', 'created_at'],
                ],
            ]);
    }

    public function test_activity_logger_service_logs_activities()
    {
        $logger = app(ActivityLogger::class);
        $user = User::factory()->create();
        $item = Item::factory()->create();

        $activity = $logger->itemAdded($item, $user);

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'user_id' => $user->id,
            'action' => Activity::ACTION_ITEM_ADDED,
        ]);
    }

    public function test_recurring_triggered_activity_includes_metadata()
    {
        $logger = app(ActivityLogger::class);

        $activity = $logger->recurringTriggered(['Milch', 'Brot', 'Eier']);

        $this->assertDatabaseHas('activities', [
            'action' => Activity::ACTION_RECURRING_TRIGGERED,
        ]);

        $activity = Activity::where('action', Activity::ACTION_RECURRING_TRIGGERED)->first();
        $this->assertArrayHasKey('items', $activity->metadata);
        $this->assertArrayHasKey('count', $activity->metadata);
        $this->assertEquals(3, $activity->metadata['count']);
    }
}
