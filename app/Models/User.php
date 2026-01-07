<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $avatar_color
 * @property UserRole $role
 * @property int|null $parent_id
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Item> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Activity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MealPlan> $mealPlans
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MealPlanSuggestion> $mealPlanSuggestions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LunchboxItem> $lunchboxItems
 */
final class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * User role constants.
     *
     * @deprecated Use UserRole enum instead
     */
    public const ROLE_PARENT = 'parent';

    public const ROLE_KID = 'kid';

    /**
     * Generate a random avatar color for new users.
     *
     * @var array<int, string>
     */
    private const AVATAR_COLORS = [
        '#4ECDC4',
        '#FF6B6B',
        '#4CAF50',
        '#2196F3',
        '#FF9800',
        '#9C27B0',
        '#E91E63',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_color',
        'role',
        'parent_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the items created by this user.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'created_by');
    }

    /**
     * Get the activities performed by this user.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the meal plans created by this user.
     */
    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class);
    }

    /**
     * Get the parent user (for kids).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the children users (for parents).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get meal plan suggestions made by this kid.
     */
    public function mealPlanSuggestions(): HasMany
    {
        return $this->hasMany(MealPlanSuggestion::class);
    }

    /**
     * Get lunchbox items for this user.
     */
    public function lunchboxItems(): HasMany
    {
        return $this->hasMany(LunchboxItem::class);
    }

    /**
     * Check if user is a parent.
     */
    public function isParent(): bool
    {
        return $this->role === UserRole::PARENT;
    }

    /**
     * Check if user is a kid.
     */
    public function isKid(): bool
    {
        return $this->role === UserRole::KID;
    }

    /**
     * Check if user has full access.
     */
    public function hasFullAccess(): bool
    {
        return $this->role->hasFullAccess();
    }

    /**
     * Scope a query to only include parents.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeParents(Builder $query): Builder
    {
        return $query->where('role', UserRole::PARENT);
    }

    /**
     * Scope a query to only include kids.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeKids(Builder $query): Builder
    {
        return $query->where('role', UserRole::KID);
    }

    /**
     * Generate a random avatar color for new users.
     */
    public static function generateAvatarColor(): string
    {
        return self::AVATAR_COLORS[array_rand(self::AVATAR_COLORS)];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
