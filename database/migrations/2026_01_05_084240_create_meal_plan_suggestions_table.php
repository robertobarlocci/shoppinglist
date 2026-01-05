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
        Schema::create('meal_plan_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Kid who suggested
            $table->date('date');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner']);
            $table->string('title'); // Meal title from library
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Parent who approved
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('meal_plan_id')->nullable()->constrained()->onDelete('set null'); // If approved and converted
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['date', 'meal_type']);
            $table->index('status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_plan_suggestions');
    }
};
