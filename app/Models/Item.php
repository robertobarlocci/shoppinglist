<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * List type constants.
     */
    const LIST_TYPE_QUICK_BUY = 'quick_buy';
    const LIST_TYPE_TO_BUY = 'to_buy';
    const LIST_TYPE_INVENTORY = 'inventory';
    const LIST_TYPE_TRASH = 'trash';

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
        return $this->belongsTo(Item::class, 'recurring_source_id');
    }

    /**
     * Get items that were created from this recurring source.
     */
    public function recurringInstances(): HasMany
    {
        return $this->hasMany(Item::class, 'recurring_source_id');
    }

    /**
     * Scope a query to only include items in the shopping list.
     */
    public function scopeToBuy($query)
    {
        return $query->where('list_type', self::LIST_TYPE_TO_BUY);
    }

    /**
     * Scope a query to only include quick buy items.
     */
    public function scopeQuickBuy($query)
    {
        return $query->where('list_type', self::LIST_TYPE_QUICK_BUY);
    }

    /**
     * Scope a query to only include inventory items.
     */
    public function scopeInventory($query)
    {
        return $query->where('list_type', self::LIST_TYPE_INVENTORY);
    }

    /**
     * Check if this item is a recurring item.
     */
    public function isRecurring(): bool
    {
        return !is_null($this->recurring_source_id);
    }

    /**
     * Check if this item has a recurring schedule.
     */
    public function hasRecurringSchedule(): bool
    {
        return $this->recurringSchedule()->exists();
    }

    /**
     * Move item to a different list.
     */
    public function moveTo(string $listType): self
    {
        $this->list_type = $listType;
        $this->moved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Move item to trash.
     */
    public function moveToTrash(): void
    {
        $this->deleted_from = $this->list_type;
        $this->delete();
    }

    /**
     * Restore item from trash.
     */
    public function restoreFromTrash(): void
    {
        $this->restore();
        $this->list_type = $this->deleted_from ?? self::LIST_TYPE_TO_BUY;
        $this->deleted_from = null;
        $this->save();
    }
}
