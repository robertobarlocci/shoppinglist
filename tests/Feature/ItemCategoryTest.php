<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Item\CreateItemAction;
use App\Actions\Item\MoveItemAction;
use App\Enums\ListType;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue #2 — the category belongs to the item NAME, not to the row created last.
 * It may only be changed by an explicit user action, never by a default.
 */
final class ItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $other;

    private Category $beverages;

    private Category $sugar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->user = User::factory()->create(['role' => 'parent']);
        $this->other = Category::where('slug', 'other')->firstOrFail();
        $this->beverages = Category::where('slug', 'beverages')->firstOrFail();
        $this->sugar = Category::where('slug', 'pantry')->firstOrFail();
    }

    // ---------------------------------------------------------------- MoveItemAction

    /** Test 1 — the headline defect: a defaulted category must never clobber a curated one. */
    public function test_dedup_keeps_the_curated_category_when_the_incoming_one_was_not_chosen(): void
    {
        $inventory = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $toBuy = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
            'category_is_explicit' => false,
        ]);

        app(MoveItemAction::class)->execute($toBuy, ListType::INVENTORY, $this->user);

        $this->assertSame(
            $this->beverages->id,
            $inventory->fresh()->category_id,
            'Checking off a defaulted duplicate must not rewrite the curated inventory category.',
        );
    }

    /** Test 2 — an explicit user choice still wins. */
    public function test_dedup_adopts_the_incoming_category_when_the_user_explicitly_chose_it(): void
    {
        $inventory = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $toBuy = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->sugar->id,
            'list_type' => 'to_buy',
            'category_is_explicit' => true,
        ]);

        app(MoveItemAction::class)->execute($toBuy, ListType::INVENTORY, $this->user);

        $survivor = $inventory->fresh();
        $this->assertSame($this->sugar->id, $survivor->category_id);
        $this->assertTrue($survivor->category_is_explicit);
    }

    /** Test 3 — "not the default" is not enough on its own; only explicitness wins. */
    public function test_dedup_keeps_the_curated_category_for_a_non_default_but_non_explicit_incoming(): void
    {
        $inventory = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $toBuy = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->sugar->id,
            'list_type' => 'to_buy',
            'category_is_explicit' => false,
        ]);

        app(MoveItemAction::class)->execute($toBuy, ListType::INVENTORY, $this->user);

        $this->assertSame($this->beverages->id, $inventory->fresh()->category_id);
    }

    /** Test 4 — with more than one inventory row of a name, the survivor is deterministic. */
    public function test_duplicate_lookup_prefers_the_explicit_row_and_is_deterministic(): void
    {
        $plain = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->other->id,
            'list_type' => 'inventory',
            'category_is_explicit' => false,
        ]);

        $curated = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
            'category_is_explicit' => true,
        ]);

        $toBuy = $this->makeItem([
            'name' => 'cola',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ]);

        $result = app(MoveItemAction::class)->execute($toBuy, ListType::INVENTORY, $this->user);

        $this->assertTrue($result['deduplication']);
        $this->assertSame($curated->id, $result['item']->id, 'The curated row must survive.');
        $this->assertNotNull($plain->fresh(), 'The other inventory row is left alone.');
    }

    // -------------------------------------------------------------- CreateItemAction

    /** Test 5 — a name that is already known inherits its category. */
    public function test_creating_a_known_name_without_a_category_inherits_it(): void
    {
        $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $item = app(CreateItemAction::class)->execute([
            'name' => 'cola',
            'list_type' => 'to_buy',
        ], $this->user);

        $this->assertSame($this->beverages->id, $item->category_id);
        $this->assertFalse($item->category_is_explicit, 'An inherited category was not chosen.');
    }

    /** Test 6 — an unknown name still falls back to the default. */
    public function test_creating_an_unknown_name_without_a_category_falls_back_to_other(): void
    {
        $item = app(CreateItemAction::class)->execute([
            'name' => 'Völlig Neues Ding',
            'list_type' => 'to_buy',
        ], $this->user);

        $this->assertSame($this->other->id, $item->category_id);
        $this->assertFalse($item->category_is_explicit);
    }

    /** Test 7 — a non-default category on create can only have come from the dropdown. */
    public function test_creating_with_a_non_default_category_marks_it_explicit(): void
    {
        $item = app(CreateItemAction::class)->execute([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'to_buy',
        ], $this->user);

        $this->assertSame($this->beverages->id, $item->category_id);
        $this->assertTrue($item->category_is_explicit);
    }

    /** Test 8 — the default category on create is indistinguishable from the fallback. */
    public function test_creating_with_the_default_category_is_not_explicit(): void
    {
        $item = app(CreateItemAction::class)->execute([
            'name' => 'Völlig Neues Ding',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ], $this->user);

        $this->assertSame($this->other->id, $item->category_id);
        $this->assertFalse($item->category_is_explicit);
    }

    /** Test 9 — a client can never forge the explicitness flag. */
    public function test_the_explicit_flag_cannot_be_set_from_the_request(): void
    {
        $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/items', [
            'name' => 'Cola',
            'list_type' => 'to_buy',
            'category_id' => $this->other->id,
            'category_is_explicit' => true,
        ]);

        $response->assertCreated();

        $created = Item::where('list_type', 'to_buy')->firstOrFail();
        $this->assertFalse(
            $created->category_is_explicit,
            'category_is_explicit must be derived server-side, never mass-assigned.',
        );
    }

    /** Test 10 — the edit modal is the unambiguous explicit path. */
    public function test_updating_the_category_marks_it_explicit(): void
    {
        $item = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/items/{$item->id}", ['category_id' => $this->beverages->id])
            ->assertOk();

        $fresh = $item->fresh();
        $this->assertSame($this->beverages->id, $fresh->category_id);
        $this->assertTrue($fresh->category_is_explicit);
    }

    // ------------------------------------------------------------------------ suggest

    /** Test 11 — Cola and cola are one suggestion. */
    public function test_suggest_collapses_names_case_insensitively(): void
    {
        $this->makeItem(['name' => 'Cola', 'category_id' => $this->beverages->id, 'list_type' => 'inventory']);
        $this->makeItem(['name' => 'cola', 'category_id' => $this->other->id, 'list_type' => 'to_buy']);

        $response = $this->actingAs($this->user)->getJson('/api/items/suggest?q=col');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** Test 12 — the badge must show the curated inventory category, not the newest row's. */
    public function test_suggest_returns_the_curated_inventory_category(): void
    {
        $this->makeItem(['name' => 'Cola', 'category_id' => $this->beverages->id, 'list_type' => 'inventory']);
        // Created later, so MAX(id) would pick this one.
        $this->makeItem(['name' => 'Cola', 'category_id' => $this->other->id, 'list_type' => 'to_buy']);

        $response = $this->actingAs($this->user)->getJson('/api/items/suggest?q=cola');

        $response->assertOk();
        $this->assertSame($this->beverages->name, $response->json('data.0.category.name'));
    }

    /** Test 13 — the query string is bound, so LIKE metacharacters are literal. */
    public function test_suggest_treats_like_metacharacters_literally(): void
    {
        $this->makeItem(['name' => 'Cola', 'category_id' => $this->beverages->id, 'list_type' => 'inventory']);

        $response = $this->actingAs($this->user)->getJson('/api/items/suggest?q=' . urlencode('c%a'));

        $response->assertOk();
        $this->assertSame([], $response->json('data'), 'A literal "c%a" must not match "Cola".');
    }

    // ------------------------------------------------------------------ offline sync

    /** Test 17 — the offline path resolves categories the same way. */
    public function test_offline_created_item_inherits_the_category_for_its_name(): void
    {
        $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        app(OfflineSyncService::class)->processOfflineActions([[
            'id' => 'offline-1',
            'type' => 'item:create',
            'data' => ['name' => 'Cola', 'list_type' => 'to_buy'],
            'timestamp' => now()->toIso8601String(),
        ]], $this->user);

        $created = Item::where('list_type', 'to_buy')->firstOrFail();
        $this->assertSame($this->beverages->id, $created->category_id);
    }

    // ----------------------------------------------------------- end-to-end for #2

    /** Test 18 — the exact reproduction from issue #2, through the HTTP API. */
    public function test_issue_2_end_to_end_the_curated_category_survives_the_debounce_race(): void
    {
        $inventory = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        // The race: the user clicks "add new" before suggestions land, so the dropdown
        // still holds the default "Sonstiges".
        $created = $this->actingAs($this->user)->postJson('/api/items', [
            'name' => 'Cola',
            'list_type' => 'to_buy',
            'category_id' => $this->other->id,
        ])->assertCreated()->json('data.id');

        // The user buys it and checks it off.
        $this->actingAs($this->user)
            ->postJson("/api/items/{$created}/move", ['to_list' => 'inventory'])
            ->assertOk();

        $this->assertSame(
            $this->beverages->id,
            $inventory->fresh()->category_id,
            'Issue #2: the curated category must survive the check-off.',
        );

        $suggest = $this->actingAs($this->user)->getJson('/api/items/suggest?q=cola')->assertOk();
        $this->assertSame($this->beverages->name, $suggest->json('data.0.category.name'));
    }

    private function makeItem(array $attributes): Item
    {
        return Item::create(array_merge([
            'created_by' => $this->user->id,
        ], $attributes));
    }
}
