<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50); // item_added, quick_buy_added, item_checked, etc.
            $table->string('subject_type', 50)->nullable(); // Item, Category, etc.
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->jsonb('metadata')->nullable(); // Zusätzliche Daten
            $table->timestamp('created_at')->useCurrent();

            // Index für schnelle Abfragen
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
