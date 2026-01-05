<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use App\Models\MealPlanIngredient;
use App\Models\Item;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MealPlanController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Get meal plans for a specific week.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
        ]);

        // Get start of week (Monday) from provided date or current week
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfWeek()
            : Carbon::now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();

        $mealPlans = MealPlan::with('ingredients.item')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderByRaw("CASE
                WHEN meal_type = 'breakfast' THEN 1
                WHEN meal_type = 'lunch' THEN 2
                WHEN meal_type = 'dinner' THEN 3
                END")
            ->get();

        return MealPlanResource::collection($mealPlans);
    }

    /**
     * Store a newly created meal plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner',
            'title' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $mealPlan = MealPlan::create([
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'meal_type' => $validated['meal_type'],
                'title' => $validated['title'],
            ]);

            // Log activity
            $this->activityLogger->mealPlanCreated($mealPlan, auth()->user());

            DB::commit();

            return new MealPlanResource($mealPlan->load('ingredients'));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified meal plan.
     */
    public function show(MealPlan $mealPlan)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return new MealPlanResource($mealPlan->load('ingredients.item'));
    }

    /**
     * Update the specified meal plan.
     */
    public function update(Request $request, MealPlan $mealPlan)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'meal_type' => 'sometimes|required|in:breakfast,lunch,dinner',
            'title' => 'sometimes|required|string|max:255',
        ]);

        $mealPlan->update($validated);

        return new MealPlanResource($mealPlan->load('ingredients'));
    }

    /**
     * Remove the specified meal plan.
     */
    public function destroy(MealPlan $mealPlan)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $mealPlan->delete();

        return response()->json(['message' => 'Meal plan deleted']);
    }

    /**
     * Add an ingredient to a meal plan.
     */
    public function addIngredient(Request $request, MealPlan $mealPlan)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:100',
            'item_id' => 'nullable|exists:items,id',
        ]);

        $ingredient = $mealPlan->ingredients()->create($validated);

        return response()->json([
            'ingredient' => $ingredient->load('item'),
            'message' => 'Ingredient added',
        ]);
    }

    /**
     * Remove an ingredient from a meal plan.
     */
    public function removeIngredient(MealPlan $mealPlan, MealPlanIngredient $ingredient)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Ensure ingredient belongs to this meal plan
        if ($ingredient->meal_plan_id !== $mealPlan->id) {
            abort(403, 'Unauthorized');
        }

        $ingredient->delete();

        return response()->json(['message' => 'Ingredient removed']);
    }

    /**
     * Add all ingredients from a meal plan to the shopping list.
     */
    public function addIngredientsToShoppingList(MealPlan $mealPlan)
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();

        try {
            $ingredients = $mealPlan->ingredients;
            $addedCount = 0;

            foreach ($ingredients as $ingredient) {
                // Check if item already exists in to_buy list
                $existingItem = Item::where('name', $ingredient->name)
                    ->where('list_type', Item::LIST_TYPE_TO_BUY)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$existingItem) {
                    // Get default category if ingredient has no linked item
                    $categoryId = null;
                    if ($ingredient->item_id) {
                        $categoryId = $ingredient->item->category_id;
                    } else {
                        $categoryId = Category::where('slug', 'other')->first()?->id;
                    }

                    // Create new item in shopping list
                    Item::create([
                        'name' => $ingredient->name,
                        'quantity' => $ingredient->quantity,
                        'category_id' => $categoryId,
                        'list_type' => Item::LIST_TYPE_TO_BUY,
                        'created_by' => auth()->id(),
                    ]);

                    $addedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "$addedCount ingredients added to shopping list",
                'added_count' => $addedCount,
                'total_ingredients' => $ingredients->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
