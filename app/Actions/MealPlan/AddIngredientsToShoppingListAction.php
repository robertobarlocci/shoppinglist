<?php

declare(strict_types=1);

namespace App\Actions\MealPlan;

use App\Models\Item;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\ItemCategoryResolver;
use Illuminate\Support\Facades\DB;

final class AddIngredientsToShoppingListAction
{
    public function __construct(
        private readonly ItemCategoryResolver $categoryResolver,
    ) {}

    /**
     * Add all ingredients from a meal plan to the shopping list.
     *
     * @return array{added_count: int, total_ingredients: int}
     */
    public function execute(MealPlan $mealPlan, User $user): array
    {
        return DB::transaction(function () use ($mealPlan, $user): array {
            $ingredients = $mealPlan->ingredients;
            $addedCount = 0;

            foreach ($ingredients as $ingredient) {
                if ($this->itemExistsInShoppingList($ingredient->name)) {
                    continue;
                }

                $this->createShoppingListItem($ingredient, $user);
                $addedCount++;
            }

            return [
                'added_count' => $addedCount,
                'total_ingredients' => $ingredients->count(),
            ];
        });
    }

    private function itemExistsInShoppingList(string $name): bool
    {
        // Issue #2: match the same way dedup and suggest do, so "Cola" and "cola" are one
        // item instead of two rows that later merge with an arbitrary survivor.
        return Item::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where('list_type', Item::LIST_TYPE_TO_BUY)
            ->whereNull('deleted_at')
            ->exists();
    }

    private function createShoppingListItem(object $ingredient, User $user): Item
    {
        $categoryId = $this->resolveCategoryId($ingredient);

        return Item::create([
            'name' => $ingredient->name,
            'quantity' => $ingredient->quantity,
            'category_id' => $categoryId,
            // Derived from the linked item / the name's history — nobody chose it here.
            'category_is_explicit' => false,
            'list_type' => Item::LIST_TYPE_TO_BUY,
            'created_by' => $user->id,
        ]);
    }

    private function resolveCategoryId(object $ingredient): ?int
    {
        if ($ingredient->item_id) {
            $item = $ingredient->item()->withTrashed()->first();
            if ($item?->category_id) {
                return $item->category_id;
            }
        }

        // Issue #2: an ingredient typed by hand (or added before the UI sent item_id) still
        // knows its category if that name has one — only then fall back to "Sonstiges".
        return $this->categoryResolver->inheritedCategoryIdForName($ingredient->name)
            ?? $this->categoryResolver->defaultCategoryId();
    }
}
