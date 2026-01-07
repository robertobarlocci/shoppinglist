<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Item\CreateItemAction;
use App\Actions\Item\MoveItemAction;
use App\Enums\ListType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Item\MoveItemRequest;
use App\Http\Requests\Item\SetRecurringRequest;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use App\Services\ActivityLogger;
use App\Services\RecurringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ItemController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly RecurringService $recurringService,
        private readonly CreateItemAction $createItemAction,
        private readonly MoveItemAction $moveItemAction,
    ) {}

    /**
     * Display a listing of items.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $listType = $request->query('list_type');

        // Handle trash separately - query soft-deleted items
        if ($listType === ListType::TRASH->value) {
            $items = Item::onlyTrashed()
                ->with(['category', 'creator', 'recurringSchedule'])
                ->orderBy('deleted_at', 'desc')
                ->get();

            return ItemResource::collection($items);
        }

        // Regular query for non-trash items (automatically excludes soft-deleted)
        $items = Item::with(['category', 'creator', 'recurringSchedule'])
            ->when($listType, fn ($q) => $q->where('list_type', $listType))
            ->orderBy('created_at', 'desc')
            ->get();

        return ItemResource::collection($items);
    }

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request): ItemResource
    {
        $item = $this->createItemAction->execute(
            $request->validated(),
            $request->user()
        );

        return new ItemResource($item);
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item): ItemResource
    {
        $this->authorize('view', $item);

        return new ItemResource($item->load(['category', 'creator', 'recurringSchedule']));
    }

    /**
     * Update the specified item.
    /**
     * Update the specified item.
     */
    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        $this->activityLogger->itemEdited($item, $request->user(), $request->validated());

        return new ItemResource($item->load(['category', 'creator']));
    }

    /**
     * Move item to trash (soft delete).
     */
    public function destroy(Item $item): JsonResponse
    {
        $this->authorize('delete', $item);

        $item->moveToTrash();

        $this->activityLogger->itemDeleted($item, auth()->user());

        return $this->success(message: 'Item moved to trash');
    }

    /**
     * Move item between lists.
     */
    public function move(MoveItemRequest $request, Item $item): ItemResource|JsonResponse
    {
        $result = $this->moveItemAction->execute(
            $item,
            $request->toList(),
            $request->user()
        );

        if ($result['item'] === null) {
            return $this->success(message: $result['message']);
        }

        if ($result['deduplication']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new ItemResource($result['item']),
                'deduplication' => true,
            ]);
        }

        return new ItemResource($result['item']);
    }

    /**
     * Restore item from trash.
     */
    public function restore(int $id): ItemResource
    {
        $item = Item::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $item);

        $item->restoreFromTrash();

        $this->activityLogger->itemRestored($item, auth()->user());

        return new ItemResource($item->load(['category', 'creator']));
    }

    /**
     * Permanently delete item from trash.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $item = Item::onlyTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $item);

        $item->forceDelete();

        return $this->success(message: 'Item permanently deleted');
    }

    /**
     * Search items for autocomplete (Smart Input).
     * Returns unique item names from all lists for better suggestions.
     */
    public function suggest(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = (string) $request->query('q', '');
        $minLength = config('shoppinglist.suggestions.min_query_length', 2);
        $maxResults = config('shoppinglist.suggestions.max_results', 10);

        if (strlen($query) < $minLength) {
            return response()->json([]);
        }

        // Use DISTINCT at database level for better performance
        $items = Item::select('items.*')
            ->whereIn('id', function ($subquery) use ($query) {
                $subquery->selectRaw('MIN(id)')
                    ->from('items')
                    ->where('name', 'ILIKE', "%{$query}%")
                    ->whereNull('deleted_at')
                    ->groupBy('name');
            })
            ->with('category')
            ->orderByRaw("CASE
                WHEN list_type = ? THEN 1
                WHEN list_type = ? THEN 2
                WHEN list_type = ? THEN 3
                ELSE 4
            END", [
                ListType::INVENTORY->value,
                ListType::TO_BUY->value,
                ListType::QUICK_BUY->value,
            ])
            ->limit($maxResults)
            ->get();

        return ItemResource::collection($items);
    }

    /**
     * Set or update recurring schedule for an item.
     */
    public function setRecurring(SetRecurringRequest $request, Item $item): ItemResource
    {
        $this->recurringService->setRecurringSchedule($item, $request->scheduleData());

        return new ItemResource($item->load(['category', 'creator', 'recurringSchedule']));
    }

    /**
     * Remove recurring schedule from an item.
     */
    public function removeRecurring(Item $item): ItemResource
    {
        $this->authorize('update', $item);

        $this->recurringService->removeRecurringSchedule($item);

        return new ItemResource($item->load(['category', 'creator']));
    }
}
