<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListType;
use App\Models\Category;
use App\Models\Item;

/**
 * Issue #2 — the category belongs to the item NAME, not to the row created last.
 *
 * Every path that creates an item (API, Quick Buy, offline sync, meal planner) resolves its
 * category here, so a name that already has a curated category keeps it instead of falling
 * back to "Sonstiges". This is also the single place that knows about the `other` slug.
 */
final class ItemCategoryResolver
{
    /**
     * Slug of the catch-all category ("Sonstiges") used as the last-resort fallback.
     */
    public const DEFAULT_SLUG = 'other';

    /**
     * The id of the catch-all category, or null when it was never seeded.
     */
    public function defaultCategoryId(): ?int
    {
        return Category::where('slug', self::DEFAULT_SLUG)->value('id');
    }

    /**
     * The category this item name has been given before, if any.
     *
     * Matching is case-insensitive so "Cola" and "cola" are one item name. The catch-all
     * category is deliberately ignored — inheriting "Sonstiges" is the same as having no
     * inherited category at all. Curated rows win: an explicitly chosen category first,
     * then an inventory row, then the most recent.
     */
    public function inheritedCategoryIdForName(string $name): ?int
    {
        $defaultId = $this->defaultCategoryId();

        return Item::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->whereNotNull('category_id')
            ->when($defaultId !== null, fn ($query) => $query->where('category_id', '!=', $defaultId))
            ->orderByDesc('category_is_explicit')
            ->orderByRaw('CASE WHEN list_type = ? THEN 0 ELSE 1 END', [ListType::INVENTORY->value])
            ->orderByDesc('id')
            ->value('category_id');
    }

    /**
     * Resolve the category for a newly created item.
     *
     * A category is treated as EXPLICIT only when the caller supplied one that is not the
     * catch-all default: the default is indistinguishable from the fallback the UI shows
     * before the user has chosen anything, which is precisely the race in issue #2. A user
     * who genuinely wants "Sonstiges" sets it in the edit modal, which is the explicit path.
     *
     * @return array{0: int|null, 1: bool} [category id, was it explicitly chosen]
     */
    public function resolveForNewItem(?int $categoryId, string $name): array
    {
        $defaultId = $this->defaultCategoryId();

        if ($categoryId !== null) {
            return [$categoryId, $categoryId !== $defaultId];
        }

        return [$this->inheritedCategoryIdForName($name) ?? $defaultId, false];
    }
}
