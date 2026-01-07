<?php

declare(strict_types=1);

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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('quantity', 100)->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('list_type', 20); // quick_buy, to_buy, inventory, trash
            $table->foreignId('recurring_source_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('deleted_from', 20)->nullable(); // Welche Liste vor dem Trash
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->timestamp('moved_at')->nullable();
            $table->softDeletes(); // deleted_at für trash

            // Indexes for performance
            $table->index('list_type');
            $table->index('name');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
