<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Activity action constants.
     */
    const ACTION_ITEM_ADDED = 'item_added';
    const ACTION_QUICK_BUY_ADDED = 'quick_buy_added';
    const ACTION_ITEM_CHECKED = 'item_checked';
    const ACTION_ITEM_DELETED = 'item_deleted';
    const ACTION_ITEM_RESTORED = 'item_restored';
    const ACTION_ITEM_EDITED = 'item_edited';
    const ACTION_RECURRING_TRIGGERED = 'recurring_triggered';
    const ACTION_CATEGORY_CREATED = 'category_created';
    const ACTION_USER_LOGIN = 'user_login';
    const ACTION_MEAL_PLAN_CREATED = 'meal_plan_created';

    /**
     * Get the user who performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the icon for this activity action.
     */
    public function getIconAttribute(): string
    {
        return match($this->action) {
            self::ACTION_ITEM_ADDED => '🛒',
            self::ACTION_QUICK_BUY_ADDED => '🔥',
            self::ACTION_ITEM_CHECKED => '✅',
            self::ACTION_ITEM_DELETED => '🗑️',
            self::ACTION_ITEM_RESTORED => '♻️',
            self::ACTION_ITEM_EDITED => '✏️',
            self::ACTION_RECURRING_TRIGGERED => '🔄',
            self::ACTION_CATEGORY_CREATED => '🏷️',
            self::ACTION_USER_LOGIN => '👤',
            self::ACTION_MEAL_PLAN_CREATED => '📅',
            default => '📋',
        };
    }

    /**
     * Get a human-readable description of the activity.
     */
    public function getDescriptionAttribute(): string
    {
        $userName = $this->user ? $this->user->name : 'System';

        return match($this->action) {
            self::ACTION_ITEM_ADDED => "{$userName} hat \"{$this->subject_name}\" zur Einkaufsliste hinzugefügt",
            self::ACTION_QUICK_BUY_ADDED => "{$userName} hat \"{$this->subject_name}\" als Quick Buy hinzugefügt",
            self::ACTION_ITEM_CHECKED => "{$userName} hat \"{$this->subject_name}\" abgehakt",
            self::ACTION_ITEM_DELETED => "{$userName} hat \"{$this->subject_name}\" gelöscht",
            self::ACTION_ITEM_RESTORED => "{$userName} hat \"{$this->subject_name}\" wiederhergestellt",
            self::ACTION_ITEM_EDITED => "{$userName} hat \"{$this->subject_name}\" bearbeitet",
            self::ACTION_RECURRING_TRIGGERED => "Wiederkehrende Artikel automatisch hinzugefügt",
            self::ACTION_CATEGORY_CREATED => "{$userName} hat die Kategorie \"{$this->subject_name}\" erstellt",
            self::ACTION_USER_LOGIN => "{$userName} hat sich eingeloggt",
            self::ACTION_MEAL_PLAN_CREATED => "{$userName} hat \"{$this->subject_name}\" zum Essensplan hinzugefügt",
            default => $this->action,
        };
    }

    /**
     * Scope a query to only include recent activities.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
