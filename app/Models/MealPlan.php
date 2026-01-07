<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property \Carbon\Carbon $date
 * @property MealType $meal_type
 * @property string $title
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MealPlanIngredient> $ingredients
 */
final class MealPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'meal_type',
        'title',
    ];

    /**
     * Get the user that owns the meal plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ingredients for the meal plan.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(MealPlanIngredient::class);
    }

    /**
     * Check if meal plan belongs to user.
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal_type' => MealType::class,
        ];
    }
}
