<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property int $quantity
 * @property int|null $category_id
 * @property ListType $list_type
 * @property int|null $recurring_source_id
 * @property string|null $deleted_from
 * @property int|null $created_by
 * @property \Carbon\Carbon|null $moved_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Category|null $category
 * @property-read User|null $creator
 * @property-read RecurringSchedule|null $recurringSchedule
 * @property-read Item|null $recurringSource
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Item> $recurringInstances
 */
final class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * List type constants.
     *
     * @deprecated Use ListType enum instead
     */
    public const LIST_TYPE_QUICK_BUY = 'quick_buy';

    public const LIST_TYPE_TO_BUY = 'to_buy';

    public const LIST_TYPE_INVENTORY = 'inventory';

    public const LIST_TYPE_TRASH = 'trash';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'quantity',
        'category_id',
        'list_type',
        'recurring_source_id',
        'deleted_from',
        'created_by',
        'moved_at',
    ];

    /**
     * Get the category that owns the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the recurring schedule for this item.
     */
    public function recurringSchedule(): HasOne
    {
        return $this->hasOne(RecurringSchedule::class);
    }

    /**
     * Get the source item if this is a recurring item.
     */
    public function recurringSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurring_source_id');
    }

    /**
     * Get items that were created from this recurring source.
     */
    public function recurringInstances(): HasMany
    {
        return $this->hasMany(self::class, 'recurring_source_id');
    }

    /**
     * Scope a query to only include items in the shopping list.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeToBuy(Builder $query): Builder
    {
        return $query->where('list_type', ListType::TO_BUY);
    }

    /**
     * Scope a query to only include quick buy items.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeQuickBuy(Builder $query): Builder
    {
        return $query->where('list_type', ListType::QUICK_BUY);
    }

    /**
     * Scope a query to only include inventory items.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeInventory(Builder $query): Builder
    {
        return $query->where('list_type', ListType::INVENTORY);
    }

    /**
     * Scope a query to only include trashed items.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeTrash(Builder $query): Builder
    {
        return $query->withTrashed()->where('list_type', ListType::TRASH);
    }

    /**
     * Check if this item is a recurring item.
     */
    public function isRecurring(): bool
    {
        return $this->recurring_source_id !== null;
    }

    /**
     * Check if this item has a recurring schedule.
     */
    public function hasRecurringSchedule(): bool
    {
        return $this->recurringSchedule()->exists();
    }

    /**
     * Check if item is in trash.
     */
    public function isInTrash(): bool
    {
        return $this->list_type === ListType::TRASH;
    }

    /**
     * Move item to a different list.
     */
    public function moveTo(ListType $listType): self
    {
        $this->list_type = $listType;
        $this->moved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Move item to trash.
     * Saves the original list_type, sets list_type to 'trash', and soft deletes.
     */
    public function moveToTrash(): void
    {
        $this->deleted_from = $this->list_type->value;
        $this->list_type = ListType::TRASH;
        $this->save();
        $this->delete();
    }

    /**
     * Restore item from trash.
     */
    public function restoreFromTrash(): void
    {
        $this->restore();
        $restoredType = $this->deleted_from !== null
            ? (ListType::tryFrom($this->deleted_from) ?? ListType::TO_BUY)
            : ListType::TO_BUY;
        $this->list_type = $restoredType;
        $this->deleted_from = null;
        $this->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'list_type' => ListType::class,
            'moved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
