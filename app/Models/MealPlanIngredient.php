<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MealPlanIngredient extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'meal_plan_id',
        'name',
        'quantity',
        'item_id',
    ];

    /**
     * Get the meal plan that owns the ingredient.
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    /**
     * Get the linked shopping list item (optional).
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
