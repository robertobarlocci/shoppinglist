<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\ListType;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ItemQueryBuilder
{
    /**
     * @var Builder<Item>
     */
    private Builder $query;

    public function __construct()
    {
        $this->query = Item::query();
    }

    /**
     * Start a new query.
     */
    public static function query(): self
    {
        return new self();
    }

    /**
     * Include soft deleted items.
     */
    public function withTrashed(): self
    {
        $this->query->withTrashed();
        return $this;
    }

    /**
     * Filter by list type.
     */
    public function forListType(ListType $listType): self
    {
        $this->query->where('list_type', $listType);
        return $this;
    }

    /**
     * Filter for trash items.
     */
    public function inTrash(): self
    {
        $this->query->withTrashed()->where('list_type', ListType::TRASH);
        return $this;
    }

    /**
     * Filter for active (non-trash) items.
     */
    public function active(): self
    {
        $this->query->where('list_type', '!=', ListType::TRASH);
        return $this;
    }

    /**
     * Filter by category.
     */
    public function inCategory(?int $categoryId): self
    {
        if ($categoryId !== null) {
            $this->query->where('category_id', $categoryId);
        } else {
            $this->query->whereNull('category_id');
        }
        return $this;
    }

    /**
     * Search by name.
     */
    public function search(string $term): self
    {
        $this->query->where('name', 'ilike', "%{$term}%");
        return $this;
    }

    /**
     * Include category relationship.
     */
    public function withCategory(): self
    {
        $this->query->with('category');
        return $this;
    }

    /**
     * Include recurring schedule relationship.
     */
    public function withRecurringSchedule(): self
    {
        $this->query->with('recurringSchedule');
        return $this;
    }

    /**
     * Include all common relationships.
     */
    public function withRelations(): self
    {
        $this->query->with(['category', 'recurringSchedule', 'creator']);
        return $this;
    }

    /**
     * Order by category sort order.
     */
    public function orderByCategory(): self
    {
        $this->query->leftJoin('categories', 'items.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order')
            ->orderBy('items.name')
            ->select('items.*');
        return $this;
    }

    /**
     * Order by name.
     */
    public function orderByName(string $direction = 'asc'): self
    {
        $this->query->orderBy('name', $direction);
        return $this;
    }

    /**
     * Order by most recently moved.
     */
    public function orderByRecentlyMoved(): self
    {
        $this->query->orderByDesc('moved_at');
        return $this;
    }

    /**
     * Get items with recurring schedule.
     */
    public function hasRecurringSchedule(): self
    {
        $this->query->has('recurringSchedule');
        return $this;
    }

    /**
     * Get the query builder.
     *
     * @return Builder<Item>
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Get the results.
     *
     * @return Collection<int, Item>
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get paginated results.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<Item>
     */
    public function paginate(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->query->paginate($perPage);
    }

    /**
     * Get the first result.
     */
    public function first(): ?Item
    {
        return $this->query->first();
    }

    /**
     * Get the first result or fail.
     */
    public function firstOrFail(): Item
    {
        return $this->query->firstOrFail();
    }

    /**
     * Find by ID.
     */
    public function find(int $id): ?Item
    {
        return $this->query->find($id);
    }

    /**
     * Find by ID or fail.
     */
    public function findOrFail(int $id): Item
    {
        return $this->query->findOrFail($id);
    }

    /**
     * Check if any items exist.
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * Get count of items.
     */
    public function count(): int
    {
        return $this->query->count();
    }
}
