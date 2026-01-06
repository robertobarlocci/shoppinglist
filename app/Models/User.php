<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * User role constants.
     */
    const ROLE_PARENT = 'parent';
    const ROLE_KID = 'kid';

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the children users (for parents).
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
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
        return $this->role === self::ROLE_PARENT;
    }

    /**
     * Check if user is a kid.
     */
    public function isKid(): bool
    {
        return $this->role === self::ROLE_KID;
    }

    /**
     * Scope a query to only include parents.
     */
    public function scopeParents($query)
    {
        return $query->where('role', self::ROLE_PARENT);
    }

    /**
     * Scope a query to only include kids.
     */
    public function scopeKids($query)
    {
        return $query->where('role', self::ROLE_KID);
    }

    /**
     * Generate a random avatar color for new users.
     */
    public static function generateAvatarColor(): string
    {
        $colors = ['#4ECDC4', '#FF6B6B', '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#E91E63'];
        return $colors[array_rand($colors)];
    }
}
