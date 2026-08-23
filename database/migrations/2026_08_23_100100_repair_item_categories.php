<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #2 — one-off, reversible repair of the categories the old code destroyed.
 *
 * Every row that currently sits on the catch-all category (or on no category at all) is
 * re-pointed at the last real category that name was given, taken from any other row with
 * the same name — including soft-deleted ones, which is often where the curated value
 * survived. Rows with no such donor are left alone.
 *
 * Reversibility: every previous value is written to `item_category_repairs` before the
 * update, and `down()` restores them and drops the table. This is deliberately a log table
 * rather than a blind heuristic reversal — the previous value cannot be recomputed.
 *
 * The query builder is used rather than the Eloquent models on purpose: a migration must keep
 * working when the models change.
 */
return new class extends Migration
{
    private const DEFAULT_SLUG = 'other';

    public function up(): void
    {
        Schema::create('item_category_repairs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('previous_category_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('item_id');
        });

        $defaultCategoryId = DB::table('categories')
            ->where('slug', self::DEFAULT_SLUG)
            ->value('id');

        $now = now();

        DB::table('items')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($defaultCategoryId) {
                $query->whereNull('category_id');

                if ($defaultCategoryId !== null) {
                    $query->orWhere('category_id', $defaultCategoryId);
                }
            })
            ->orderBy('id')
            ->chunkById(500, function ($items) use ($defaultCategoryId, $now) {
                foreach ($items as $item) {
                    $donorCategoryId = $this->findDonorCategoryId($item, $defaultCategoryId);

                    if ($donorCategoryId === null) {
                        continue;
                    }

                    DB::table('item_category_repairs')->insert([
                        'item_id' => $item->id,
                        'previous_category_id' => $item->category_id,
                        'created_at' => $now,
                    ]);

                    DB::table('items')
                        ->where('id', $item->id)
                        ->update(['category_id' => $donorCategoryId]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_category_repairs')) {
            return;
        }

        DB::table('item_category_repairs')
            ->orderBy('id')
            ->chunkById(500, function ($repairs) {
                foreach ($repairs as $repair) {
                    DB::table('items')
                        ->where('id', $repair->item_id)
                        ->update(['category_id' => $repair->previous_category_id]);
                }
            });

        Schema::drop('item_category_repairs');
    }

    /**
     * The last real category this item NAME was given, from any other row of the same name.
     *
     * Curated rows win: an explicitly chosen category first, then an inventory row, then the
     * most recent. Soft-deleted rows are included on purpose — the good value often only
     * survives on a row that has since been deleted.
     */
    private function findDonorCategoryId(object $item, ?int $defaultCategoryId): ?int
    {
        $query = DB::table('items')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $item->name)])
            ->where('id', '!=', $item->id)
            ->whereNotNull('category_id');

        if ($defaultCategoryId !== null) {
            $query->where('category_id', '!=', $defaultCategoryId);
        }

        return $query
            ->orderByDesc('category_is_explicit')
            ->orderByRaw("CASE WHEN list_type = 'inventory' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->value('category_id');
    }
};
