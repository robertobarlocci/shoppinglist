<?php

declare(strict_types=1);

namespace App\Actions\Item;

use App\Enums\ListType;
use App\Models\Item;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

final readonly class MoveItemAction
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * Move an item to a different list.
     *
     * @return array{item: Item|null, message: string, deduplication: bool}
     */
    public function execute(Item $item, ListType $toList, User $user): array
    {
        // Handle moving to trash
        if ($toList === ListType::TRASH) {
            return $this->moveToTrash($item, $user);
        }

        // Handle recurring items
        if ($item->isRecurring() && $toList === ListType::INVENTORY) {
            return $this->completeRecurringItem($item, $user);
        }

        return DB::transaction(fn () => $this->moveToList($item, $toList, $user));
    }

    /**
     * @return array{item: null, message: string, deduplication: bool}
     */
    private function moveToTrash(Item $item, User $user): array
    {
        $item->moveToTrash();
        $this->activityLogger->itemDeleted($item, $user);

        return [
            'item' => null,
            'message' => 'Item moved to trash',
            'deduplication' => false,
        ];
    }

    /**
     * @return array{item: null, message: string, deduplication: bool}
     */
    private function completeRecurringItem(Item $item, User $user): array
    {
        $item->delete();
        $this->activityLogger->itemChecked($item, $user);

        return [
            'item' => null,
            'message' => 'Recurring item completed',
            'deduplication' => false,
        ];
    }

    /**
     * @return array{item: Item|null, message: string, deduplication: bool}
     */
    private function moveToList(Item $item, ListType $toList, User $user): array
    {
        // Check for duplicates when moving to inventory
        if ($toList === ListType::INVENTORY) {
            $existingItem = $this->findDuplicateInInventory($item);

            if ($existingItem !== null) {
                return $this->handleDuplicate($item, $existingItem, $user);
            }
        }

        $item->moveTo($toList);

        if ($toList === ListType::INVENTORY) {
            $this->activityLogger->itemChecked($item, $user);
        }

        return [
            'item' => $item->fresh(['category', 'creator', 'recurringSchedule']),
            'message' => 'Item moved successfully',
            'deduplication' => false,
        ];
    }

    private function findDuplicateInInventory(Item $item): ?Item
    {
        return Item::where('list_type', ListType::INVENTORY)
            ->whereRaw('LOWER(name) = ?', [strtolower($item->name)])
            ->where('id', '!=', $item->id)
            ->first();
    }

    /**
     * @return array{item: Item|null, message: string, deduplication: bool}
     */
    private function handleDuplicate(Item $item, Item $existingItem, User $user): array
    {
        $itemName = $item->name;
        $item->forceDelete();
        $this->activityLogger->itemChecked($existingItem, $user);

        return [
            'item' => $existingItem->fresh(['category', 'creator', 'recurringSchedule']),
            'message' => "'{$itemName}' bereits im Inventar vorhanden",
            'deduplication' => true,
        ];
    }
}
