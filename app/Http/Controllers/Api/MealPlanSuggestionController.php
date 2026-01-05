<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MealPlanSuggestionResource;
use App\Models\MealPlanSuggestion;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MealPlanSuggestionController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Get suggestions for a specific week (parents see all kids' suggestions, kids see their own).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfWeek()
            : Carbon::now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();

        $user = auth()->user();

        // Parents see all suggestions from their kids
        // Kids see only their own suggestions
        $query = MealPlanSuggestion::with(['user', 'approver'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($user->isKid()) {
            $query->where('user_id', $user->id);
        } else {
            // Parents see suggestions from all their children
            $childrenIds = $user->children()->pluck('id');
            $query->whereIn('user_id', $childrenIds);
        }

        $suggestions = $query->orderBy('date')
            ->orderByRaw("CASE
                WHEN meal_type = 'breakfast' THEN 1
                WHEN meal_type = 'lunch' THEN 2
                WHEN meal_type = 'dinner' THEN 3
                END")
            ->get();

        return MealPlanSuggestionResource::collection($suggestions);
    }

    /**
     * Store a new meal suggestion (kids only).
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Only kids can create suggestions
        if (!$user->isKid()) {
            return response()->json(['message' => 'Only kids can create meal suggestions'], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner',
            'title' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $suggestion = MealPlanSuggestion::create([
                'user_id' => $user->id,
                'date' => $validated['date'],
                'meal_type' => $validated['meal_type'],
                'title' => $validated['title'],
                'status' => MealPlanSuggestion::STATUS_PENDING,
            ]);

            DB::commit();

            return new MealPlanSuggestionResource($suggestion->load('user'));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display a specific suggestion.
     */
    public function show(MealPlanSuggestion $suggestion)
    {
        $user = auth()->user();

        // Kids can only see their own suggestions
        // Parents can see suggestions from their kids
        if ($user->isKid() && $suggestion->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if ($user->isParent()) {
            $childrenIds = $user->children()->pluck('id');
            if (!$childrenIds->contains($suggestion->user_id)) {
                abort(403, 'Unauthorized');
            }
        }

        return new MealPlanSuggestionResource($suggestion->load(['user', 'approver']));
    }

    /**
     * Delete a suggestion (kids can delete their own pending suggestions).
     */
    public function destroy(MealPlanSuggestion $suggestion)
    {
        $user = auth()->user();

        // Kids can only delete their own pending suggestions
        if ($user->isKid()) {
            if ($suggestion->user_id !== $user->id || !$suggestion->isPending()) {
                abort(403, 'Unauthorized');
            }
        } else {
            // Parents can delete any suggestion from their kids
            $childrenIds = $user->children()->pluck('id');
            if (!$childrenIds->contains($suggestion->user_id)) {
                abort(403, 'Unauthorized');
            }
        }

        $suggestion->delete();

        return response()->json(['message' => 'Suggestion deleted']);
    }

    /**
     * Approve a suggestion and create a meal plan (parents only).
     */
    public function approve(Request $request, MealPlanSuggestion $suggestion)
    {
        $user = auth()->user();

        // Only parents can approve suggestions
        if (!$user->isParent()) {
            return response()->json(['message' => 'Only parents can approve suggestions'], 403);
        }

        // Verify this suggestion is from one of their kids
        $childrenIds = $user->children()->pluck('id');
        if (!$childrenIds->contains($suggestion->user_id)) {
            abort(403, 'Unauthorized');
        }

        // Can only approve pending suggestions
        if (!$suggestion->isPending()) {
            return response()->json(['message' => 'Suggestion is not pending'], 400);
        }

        DB::beginTransaction();

        try {
            // Create the actual meal plan
            $mealPlan = MealPlan::create([
                'user_id' => $user->id, // Parent creates the meal plan
                'date' => $suggestion->date,
                'meal_type' => $suggestion->meal_type,
                'title' => $suggestion->title,
            ]);

            // Update suggestion status
            $suggestion->update([
                'status' => MealPlanSuggestion::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'meal_plan_id' => $mealPlan->id,
            ]);

            // Log activity
            $this->activityLogger->mealPlanCreated($mealPlan, $user);

            DB::commit();

            return response()->json([
                'message' => 'Suggestion approved and meal plan created',
                'suggestion' => new MealPlanSuggestionResource($suggestion->load(['user', 'approver'])),
                'meal_plan' => $mealPlan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a suggestion (parents only).
     */
    public function reject(MealPlanSuggestion $suggestion)
    {
        $user = auth()->user();

        // Only parents can reject suggestions
        if (!$user->isParent()) {
            return response()->json(['message' => 'Only parents can reject suggestions'], 403);
        }

        // Verify this suggestion is from one of their kids
        $childrenIds = $user->children()->pluck('id');
        if (!$childrenIds->contains($suggestion->user_id)) {
            abort(403, 'Unauthorized');
        }

        // Can only reject pending suggestions
        if (!$suggestion->isPending()) {
            return response()->json(['message' => 'Suggestion is not pending'], 400);
        }

        $suggestion->update([
            'status' => MealPlanSuggestion::STATUS_REJECTED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Suggestion rejected',
            'suggestion' => new MealPlanSuggestionResource($suggestion->load(['user', 'approver'])),
        ]);
    }
}
