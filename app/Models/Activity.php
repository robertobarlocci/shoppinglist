<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property ActivityAction $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $subject_name
 * @property array<string, mixed>|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property-read User|null $user
 * @property-read string $icon
 * @property-read string $description
 */
final class Activity extends Model
{
    use HasFactory;

    /**
     * Activity action constants.
     *
     * @deprecated Use ActivityAction enum instead
     */
    public const ACTION_ITEM_ADDED = 'item_added';

    public const ACTION_QUICK_BUY_ADDED = 'quick_buy_added';

    public const ACTION_ITEM_CHECKED = 'item_checked';

    public const ACTION_ITEM_DELETED = 'item_deleted';

    public const ACTION_ITEM_RESTORED = 'item_restored';

    public const ACTION_ITEM_EDITED = 'item_edited';

    public const ACTION_RECURRING_TRIGGERED = 'recurring_triggered';

    public const ACTION_CATEGORY_CREATED = 'category_created';

    public const ACTION_USER_LOGIN = 'user_login';

    public const ACTION_MEAL_PLAN_CREATED = 'meal_plan_created';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'metadata',
    ];

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include recent activities.
     *
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the icon for this activity action.
     */
    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->action->icon(),
        );
    }

    /**
     * Get a human-readable description of the activity.
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->action->description(
                $this->user?->name ?? 'System',
                $this->subject_name,
            ),
        );
    }
}
