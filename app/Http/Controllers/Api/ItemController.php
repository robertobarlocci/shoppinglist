<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\RecurringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private RecurringService $recurringService
    ) {}

    /**
     * Display a listing of items.
     */
    public function index(Request $request)
    {
        $listType = $request->query('list_type');

        $query = Item::with(['category', 'creator', 'recurringSchedule'])
            ->when($listType, function ($q) use ($listType) {
                return $q->where('list_type', $listType);
            })
            ->when(!$listType, function ($q) {
                // Exclude trash by default
                return $q->whereNull('deleted_at');
            })
            ->orderBy('created_at', 'desc');

        // Handle trash separately with soft deletes
        if ($listType === 'trash') {
            $query->onlyTrashed();
        }

        $items = $query->get();

        // Always return consistent array format
        return ItemResource::collection($items);
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'list_type' => 'required|in:quick_buy,to_buy,inventory',
        ]);

        DB::beginTransaction();

        try {
            // Get default category if none provided
            if (!isset($validated['category_id'])) {
                $validated['category_id'] = Category::where('slug', 'other')->first()?->id;
            }

            $item = Item::create([
                ...$validated,
                'created_by' => auth()->id(),
            ]);

            // Log activity
            if ($item->list_type === Item::LIST_TYPE_QUICK_BUY) {
                $this->activityLogger->quickBuyAdded($item, auth()->user());
            } else {
                $this->activityLogger->itemAdded($item, auth()->user());
            }

            DB::commit();

            return new ItemResource($item->load(['category', 'creator']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item)
    {
        return new ItemResource($item->load(['category', 'creator', 'recurringSchedule']));
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'quantity' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $item->update($validated);

        $this->activityLogger->itemEdited($item, auth()->user(), $validated);

        return new ItemResource($item->load(['category', 'creator']));
    }

    /**
     * Move item to trash (soft delete).
     */
    public function destroy(Item $item)
    {
        $item->moveToTrash();

        $this->activityLogger->itemDeleted($item, auth()->user());

        return response()->json(['message' => 'Item moved to trash']);
    }

    /**
     * Move item between lists.
     */
    public function move(Request $request, Item $item)
    {
        $validated = $request->validate([
            'to_list' => 'required|in:quick_buy,to_buy,inventory,trash',
        ]);

        $fromList = $item->list_type;
        $toList = $validated['to_list'];

        // Handle recurring items
        if ($item->isRecurring() && $toList === Item::LIST_TYPE_INVENTORY) {
            // Recurring items should be deleted when checked, not moved
            $item->delete();
            $this->activityLogger->itemChecked($item, auth()->user());

            return response()->json(['message' => 'Recurring item completed']);
        }

        DB::beginTransaction();

        try {
            // Check for duplicates when moving to inventory
            if ($toList === Item::LIST_TYPE_INVENTORY) {
                $existingItem = Item::where('list_type', Item::LIST_TYPE_INVENTORY)
                    ->whereRaw('LOWER(name) = ?', [strtolower($item->name)])
                    ->where('id', '!=', $item->id)
                    ->first();

                if ($existingItem) {
                    // Duplicate found - delete the item being moved and return existing
                    $itemName = $item->name;
                    $item->forceDelete();
                    $this->activityLogger->itemChecked($existingItem, auth()->user());

                    DB::commit();

                    return response()->json([
                        'message' => "'{$itemName}' bereits im Inventar vorhanden",
                        'data' => new ItemResource($existingItem->fresh(['category', 'creator', 'recurringSchedule'])),
                        'deduplication' => true,
                    ]);
                }
            }

            $item->moveTo($toList);

            // Log activity
            if ($toList === Item::LIST_TYPE_INVENTORY) {
                $this->activityLogger->itemChecked($item, auth()->user());
            }

            DB::commit();

            return new ItemResource($item->fresh(['category', 'creator']));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore item from trash.
     */
    public function restore($id)
    {
        $item = Item::onlyTrashed()->findOrFail($id);

        $item->restoreFromTrash();

        $this->activityLogger->itemRestored($item, auth()->user());

        return new ItemResource($item->load(['category', 'creator']));
    }

    /**
     * Permanently delete item from trash.
     */
    public function forceDelete($id)
    {
        $item = Item::onlyTrashed()->findOrFail($id);

        $item->forceDelete();

        return response()->json(['message' => 'Item permanently deleted']);
    }

    /**
     * Search items for autocomplete (Smart Input).
     * Returns unique item names from all lists for better suggestions.
     */
    public function suggest(Request $request)
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Search across all item types (not just inventory) and get unique names
        $items = Item::where('name', 'ILIKE', "%{$query}%")
            ->with('category')
            ->orderByRaw("CASE
                WHEN list_type = 'inventory' THEN 1
                WHEN list_type = 'to_buy' THEN 2
                WHEN list_type = 'quick_buy' THEN 3
                ELSE 4
            END")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            // Group by name to get unique suggestions
            ->unique('name')
            ->take(5);

        return ItemResource::collection($items);
    }

    /**
     * Set or update recurring schedule for an item.
     */
    public function setRecurring(Request $request, Item $item)
    {
        $validated = $request->validate([
            'monday' => 'boolean',
            'tuesday' => 'boolean',
            'wednesday' => 'boolean',
            'thursday' => 'boolean',
            'friday' => 'boolean',
            'saturday' => 'boolean',
            'sunday' => 'boolean',
        ]);

        // Only allow setting recurring on inventory items
        if ($item->list_type !== Item::LIST_TYPE_INVENTORY) {
            return response()->json([
                'message' => 'Recurring schedules can only be set on inventory items'
            ], 422);
        }

        $schedule = $this->recurringService->setRecurringSchedule($item, $validated);

        return new ItemResource($item->load(['category', 'creator', 'recurringSchedule']));
    }

    /**
     * Remove recurring schedule from an item.
     */
    public function removeRecurring(Item $item)
    {
        $this->recurringService->removeRecurringSchedule($item);

        return new ItemResource($item->load(['category', 'creator']));
    }
}
