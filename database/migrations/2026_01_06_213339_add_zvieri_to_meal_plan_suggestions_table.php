<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old check constraint for meal_type enum
        DB::statement('ALTER TABLE meal_plan_suggestions DROP CONSTRAINT IF EXISTS meal_plan_suggestions_meal_type_check');

        // Add new check constraint with zvieri included
        DB::statement("ALTER TABLE meal_plan_suggestions ADD CONSTRAINT meal_plan_suggestions_meal_type_check CHECK (meal_type::text = ANY (ARRAY['breakfast'::character varying, 'lunch'::character varying, 'zvieri'::character varying, 'dinner'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new check constraint
        DB::statement('ALTER TABLE meal_plan_suggestions DROP CONSTRAINT IF EXISTS meal_plan_suggestions_meal_type_check');

        // Restore old check constraint without zvieri
        DB::statement("ALTER TABLE meal_plan_suggestions ADD CONSTRAINT meal_plan_suggestions_meal_type_check CHECK (meal_type::text = ANY (ARRAY['breakfast'::character varying, 'lunch'::character varying, 'dinner'::character varying]::text[]))");
    }
};
