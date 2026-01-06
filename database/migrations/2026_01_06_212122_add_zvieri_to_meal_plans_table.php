<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old check constraint
        DB::statement("ALTER TABLE meal_plans DROP CONSTRAINT IF EXISTS meal_plans_meal_type_check");

        // Add new check constraint with zvieri
        DB::statement("ALTER TABLE meal_plans ADD CONSTRAINT meal_plans_meal_type_check CHECK (meal_type::text = ANY (ARRAY['breakfast'::character varying, 'lunch'::character varying, 'zvieri'::character varying, 'dinner'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new check constraint
        DB::statement("ALTER TABLE meal_plans DROP CONSTRAINT IF EXISTS meal_plans_meal_type_check");

        // Restore old check constraint without zvieri
        DB::statement("ALTER TABLE meal_plans ADD CONSTRAINT meal_plans_meal_type_check CHECK (meal_type::text = ANY (ARRAY['breakfast'::character varying, 'lunch'::character varying, 'dinner'::character varying]::text[]))");
    }
};
