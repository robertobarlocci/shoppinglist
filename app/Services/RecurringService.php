<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use App\Models\RecurringSchedule;
use Illuminate\Support\Collection;

final class RecurringService
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * Check and process all recurring items for today.
     */
    public function checkRecurringItems(): array
    {
        $createdItems = [];
        $itemNames = [];

        $schedules = RecurringSchedule::with('item.category')
            ->get()
            ->filter(fn ($schedule) => $schedule->shouldTriggerToday());

        foreach ($schedules as $schedule) {
            $sourceItem = $schedule->item;

            // Create new item in shopping list
            $newItem = Item::create([
                'name' => $sourceItem->name,
                'quantity' => $sourceItem->quantity,
                'category_id' => $sourceItem->category_id,
                'list_type' => Item::LIST_TYPE_TO_BUY,
                'recurring_source_id' => $sourceItem->id,
                'created_by' => $sourceItem->created_by,
            ]);

            $createdItems[] = $newItem;
            $itemNames[] = $newItem->name;

            // Mark schedule as triggered
            $schedule->markAsTriggered();
        }

        // Log activity if items were created
        if (count($itemNames) > 0) {
            $this->activityLogger->recurringTriggered($itemNames);
        }

        return [
            'created_count' => count($createdItems),
            'items' => $createdItems,
            'item_names' => $itemNames,
        ];
    }

    /**
     * Create or update recurring schedule for an item.
     */
    public function setRecurringSchedule(Item $item, array $days): RecurringSchedule
    {
        $schedule = $item->recurringSchedule ?? new RecurringSchedule(['item_id' => $item->id]);

        $schedule->fill([
            'monday' => $days['monday'] ?? false,
            'tuesday' => $days['tuesday'] ?? false,
            'wednesday' => $days['wednesday'] ?? false,
            'thursday' => $days['thursday'] ?? false,
            'friday' => $days['friday'] ?? false,
            'saturday' => $days['saturday'] ?? false,
            'sunday' => $days['sunday'] ?? false,
        ]);

        $schedule->save();

        return $schedule;
    }

    /**
     * Remove recurring schedule from an item.
     */
    public function removeRecurringSchedule(Item $item): bool
    {
        if ($schedule = $item->recurringSchedule) {
            return $schedule->delete();
        }

        return false;
    }

    /**
     * Get all items with recurring schedules.
     */
    public function getRecurringItems(): Collection
    {
        return Item::whereHas('recurringSchedule')
            ->with(['recurringSchedule', 'category'])
            ->get();
    }
}
