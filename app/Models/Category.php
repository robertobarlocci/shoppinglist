<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_default
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Item> $items
 */
final class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

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
        'slug',
        'name',
        'color',
        'icon',
        'is_default',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the items for this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope a query to only include default categories.
     *
     * @param Builder<Category> $query
     * @return Builder<Category>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include custom categories.
     *
     * @param Builder<Category> $query
     * @return Builder<Category>
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_default', false);
    }

    /**
     * Scope a query to order by sort order.
     *
     * @param Builder<Category> $query
     * @return Builder<Category>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
