<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Issue #2 — the one-off data repair, and its rollback.
 *
 * The migration under test has already run by the time the test body starts (RefreshDatabase
 * migrates a fresh schema), so it is re-run explicitly against seeded corruption.
 */
final class ItemCategoryRepairTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_08_23_100100_repair_item_categories.php';

    private User $user;

    private Category $other;

    private Category $beverages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);

        $this->user = User::factory()->create(['role' => 'parent']);
        $this->other = Category::where('slug', 'other')->firstOrFail();
        $this->beverages = Category::where('slug', 'beverages')->firstOrFail();
    }

    /** Test 19 — a corrupted row is re-pointed at the category its name really has. */
    public function test_repair_restores_the_category_from_a_sibling_row_of_the_same_name(): void
    {
        $donor = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
            'category_is_explicit' => true,
        ]);
        $donor->delete(); // the good value often only survives on a deleted row

        $corrupted = $this->makeItem([
            'name' => 'cola',
            'category_id' => $this->other->id,
            'list_type' => 'inventory',
        ]);

        $this->runRepair();

        $this->assertSame($this->beverages->id, $corrupted->fresh()->category_id);
        $this->assertDatabaseHas('item_category_repairs', [
            'item_id' => $corrupted->id,
            'previous_category_id' => $this->other->id,
        ]);
    }

    /** Test 19b — the repair is reversible. */
    public function test_repair_rollback_restores_the_previous_categories(): void
    {
        $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $corrupted = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ]);

        $this->runRepair();
        $this->assertSame($this->beverages->id, $corrupted->fresh()->category_id);

        $this->migration()->down();

        $this->assertSame($this->other->id, $corrupted->fresh()->category_id);
        $this->assertFalse(
            Schema::hasTable('item_category_repairs'),
            'down() must drop its own log table.',
        );
    }

    /** A row with no donor of the same name is left untouched and nothing is logged. */
    public function test_repair_leaves_rows_without_a_donor_alone(): void
    {
        $lonely = $this->makeItem([
            'name' => 'Völlig Neues Ding',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ]);

        $this->runRepair();

        $this->assertSame($this->other->id, $lonely->fresh()->category_id);
        $this->assertDatabaseMissing('item_category_repairs', ['item_id' => $lonely->id]);
    }

    /** Running the repair twice changes nothing the second time. */
    public function test_repair_is_idempotent(): void
    {
        $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->beverages->id,
            'list_type' => 'inventory',
        ]);

        $corrupted = $this->makeItem([
            'name' => 'Cola',
            'category_id' => $this->other->id,
            'list_type' => 'to_buy',
        ]);

        $this->runRepair();
        $this->assertSame(1, DB::table('item_category_repairs')->count());

        // Re-run from scratch: the row is no longer on the default category, so there is
        // nothing left to repair and nothing new is logged.
        $this->runRepair();

        $this->assertSame($this->beverages->id, $corrupted->fresh()->category_id);
        $this->assertSame(
            0,
            DB::table('item_category_repairs')->count(),
            'A second run must find nothing left to repair.',
        );
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function runRepair(): void
    {
        DB::statement('DROP TABLE IF EXISTS item_category_repairs');
        $this->migration()->up();
    }

    private function makeItem(array $attributes): Item
    {
        return Item::create(array_merge(['created_by' => $this->user->id], $attributes));
    }
}
