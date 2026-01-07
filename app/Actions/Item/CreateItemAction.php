<?php

declare(strict_types=1);

namespace App\Actions\Item;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

final class CreateItemAction
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Create a new item.
     *
     * @param array{name: string, quantity?: string|null, category_id?: int|null, list_type: string} $data
     */
    public function execute(array $data, User $user): Item
    {
        return DB::transaction(function () use ($data, $user): Item {
            // Get default category if none provided
            $categoryId = $data['category_id'] ?? $this->getDefaultCategoryId();

            $item = Item::create([
                'name' => $data['name'],
                'quantity' => $data['quantity'] ?? null,
                'category_id' => $categoryId,
                'list_type' => $data['list_type'],
                'created_by' => $user->id,
            ]);

            $this->logActivity($item, $user);

            return $item->load(['category', 'creator']);
        });
    }

    private function getDefaultCategoryId(): ?int
    {
        return Category::where('slug', 'other')->first()?->id;
    }

    private function logActivity(Item $item, User $user): void
    {
        if ($item->list_type === Item::LIST_TYPE_QUICK_BUY) {
            $this->activityLogger->quickBuyAdded($item, $user);
        } else {
            $this->activityLogger->itemAdded($item, $user);
        }
    }
}
