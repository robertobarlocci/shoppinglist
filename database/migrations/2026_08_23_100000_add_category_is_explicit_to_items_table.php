<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #2 — records whether an item's category was CHOSEN by a user or merely defaulted.
 *
 * Without this signal MoveItemAction cannot tell "the user deliberately re-categorised this"
 * from "this is the Sonstiges fallback nobody chose", so every check-off overwrote the
 * curated inventory category with whatever the last row happened to carry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('category_is_explicit')->default(false)->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('category_is_explicit');
        });
    }
};
