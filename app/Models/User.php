<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
     * Generate a random avatar color for new users.
     */
    public static function generateAvatarColor(): string
    {
        $colors = ['#4ECDC4', '#FF6B6B', '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#E91E63'];
        return $colors[array_rand($colors)];
    }
}
