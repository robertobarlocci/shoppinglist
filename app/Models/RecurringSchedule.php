<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RecurringSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'last_triggered_at',
    ];

    /**
     * Get the item that owns the recurring schedule.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if schedule is active for today.
     */
    public function isActiveToday(): bool
    {
        $dayOfWeek = strtolower(now()->format('l'));

        return $this->$dayOfWeek ?? false;
    }

    /**
     * Check if schedule should trigger today.
     */
    public function shouldTriggerToday(): bool
    {
        if (! $this->isActiveToday()) {
            return false;
        }

        // Check if already triggered today
        if ($this->last_triggered_at && $this->last_triggered_at->isToday()) {
            return false;
        }

        return true;
    }

    /**
     * Mark as triggered today.
     */
    public function markAsTriggered(): void
    {
        $this->last_triggered_at = now()->toDateString();
        $this->save();
    }

    /**
     * Get the active days as an array.
     */
    public function getActiveDays(): array
    {
        $days = [];
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($weekdays as $day) {
            if ($this->$day) {
                $days[] = $day;
            }
        }

        return $days;
    }

    /**
     * Get a human-readable schedule description in German.
     */
    public function getScheduleDescription(): string
    {
        $days = $this->getActiveDays();

        if (empty($days)) {
            return 'Keine Wiederholung';
        }

        if (count($days) === 7) {
            return 'Täglich';
        }

        $germanDays = [
            'monday' => 'Mo',
            'tuesday' => 'Di',
            'wednesday' => 'Mi',
            'thursday' => 'Do',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'So',
        ];

        return implode(', ', array_map(fn ($day) => $germanDays[$day] ?? $day, $days));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monday' => 'boolean',
            'tuesday' => 'boolean',
            'wednesday' => 'boolean',
            'thursday' => 'boolean',
            'friday' => 'boolean',
            'saturday' => 'boolean',
            'sunday' => 'boolean',
            'last_triggered_at' => 'date',
        ];
    }
}
