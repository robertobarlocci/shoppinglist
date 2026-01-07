<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LunchboxItemResource;
use App\Models\LunchboxItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class LunchboxController extends Controller
{
    /**
     * Get lunchbox items for a specific week.
     * Kids see only their own items, parents see all their children's items.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', LunchboxItem::class);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
        ]);

        // Get start of week (Monday) from provided date or current week
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfWeek()
            : Carbon::now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();

        $user = auth()->user();
        $query = LunchboxItem::with('user')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($user->isKid()) {
            // Kids see only their own lunchbox items
            $query->where('user_id', $user->id);
        } elseif ($user->isParent()) {
            // Parents see all their children's lunchbox items
            $childIds = $user->children()->pluck('id')->toArray();
            $query->whereIn('user_id', $childIds);
        }

        $lunchboxItems = $query
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        return LunchboxItemResource::collection($lunchboxItems);
    }

    /**
     * Store a new lunchbox item.
     * Only kids can create lunchbox items for themselves.
     */
    public function store(Request $request)
    {
        $this->authorize('create', LunchboxItem::class);

        $validated = $request->validate([
            'date' => 'required|date',
            'item_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $lunchboxItem = LunchboxItem::create([
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'item_name' => trim($validated['item_name']),
            ]);

            DB::commit();

            return new LunchboxItemResource($lunchboxItem->load('user'));
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Remove a lunchbox item.
     * Only the owner kid can delete their own items.
     */
    public function destroy(LunchboxItem $lunchboxItem)
    {
        $this->authorize('delete', $lunchboxItem);

        DB::beginTransaction();

        try {
            $lunchboxItem->delete();
            DB::commit();

            return response()->json([
                'message' => 'Lunchbox item deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Get autocomplete suggestions for lunchbox items.
     * Returns distinct item names from all kids in the family.
     */
    public function autocomplete(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:100',
        ]);

        $query = $validated['query'] ?? '';
        $user = auth()->user();

        // Build the base query for all family members
        $itemQuery = LunchboxItem::select('item_name')
            ->distinct();

        if ($user->isKid()) {
            // For kids, get suggestions from all siblings (including themselves)
            $parentId = $user->parent_id;
            if ($parentId) {
                // Get all kids under the same parent
                $siblingIds = DB::table('users')
                    ->where('parent_id', $parentId)
                    ->where('role', 'kid')
                    ->pluck('id')
                    ->toArray();
                $itemQuery->whereIn('user_id', $siblingIds);
            } else {
                // If kid has no parent, only show their own items
                $itemQuery->where('user_id', $user->id);
            }
        } elseif ($user->isParent()) {
            // For parents, get suggestions from all their children
            $childIds = $user->children()->pluck('id')->toArray();
            if (! empty($childIds)) {
                $itemQuery->whereIn('user_id', $childIds);
            } else {
                // If parent has no children, return empty
                return response()->json([]);
            }
        }

        // Apply search filter if query provided
        if (! empty($query)) {
            $itemQuery->where('item_name', 'like', $query . '%');
        }

        $suggestions = $itemQuery
            ->orderBy('item_name')
            ->limit(10)
            ->pluck('item_name');

        return response()->json($suggestions);
    }
}
