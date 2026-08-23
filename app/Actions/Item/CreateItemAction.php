<?php

declare(strict_types=1);

namespace App\Actions\Item;

use App\Models\Item;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ItemCategoryResolver;
use Illuminate\Support\Facades\DB;

final class CreateItemAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly ItemCategoryResolver $categoryResolver,
    ) {}

    /**
     * Create a new item.
     *
     * @param  array{name: string, quantity?: string|null, category_id?: int|null, list_type: string}  $data
     */
    public function execute(array $data, User $user): Item
    {
        return DB::transaction(function () use ($data, $user): Item {
            // Issue #2: a name that already has a curated category inherits it instead of
            // falling back to "Sonstiges". Only a category the caller actually supplied — and
            // that is not the catch-all default — counts as an explicit user choice.
            [$categoryId, $categoryIsExplicit] = $this->categoryResolver->resolveForNewItem(
                $data['category_id'] ?? null,
                $data['name'],
            );

            $item = Item::create([
                'name' => $data['name'],
                'quantity' => $data['quantity'] ?? null,
                'category_id' => $categoryId,
                'category_is_explicit' => $categoryIsExplicit,
                'list_type' => $data['list_type'],
                'created_by' => $user->id,
            ]);

            $this->logActivity($item, $user);

            return $item->load(['category', 'creator']);
        });
    }

    private function logActivity(Item $item, User $user): void
    {
        if ($item->list_type === \App\Enums\ListType::QUICK_BUY) {
            $this->activityLogger->quickBuyAdded($item, $user);
        } else {
            $this->activityLogger->itemAdded($item, $user);
        }
    }
}
