<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MealPlan\AddIngredientsToShoppingListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\MealPlan\AddIngredientRequest;
use App\Http\Requests\MealPlan\StoreMealPlanRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Http\Traits\ApiResponse;
use App\Models\MealPlan;
use App\Models\MealPlanIngredient;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

final class MealPlanController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly AddIngredientsToShoppingListAction $addIngredientsAction,
    ) {}

    /**
     * Get meal plans for a specific week.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MealPlan::class);

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
        ]);

        // Get start of week (Monday) from provided date or current week
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfWeek()
            : Carbon::now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();

        $mealPlans = MealPlan::with('ingredients.item')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderByRaw("CASE
                WHEN meal_type = 'breakfast' THEN 1
                WHEN meal_type = 'lunch' THEN 2
                WHEN meal_type = 'zvieri' THEN 3
                WHEN meal_type = 'dinner' THEN 4
                END")
            ->get();

        return MealPlanResource::collection($mealPlans);
    }

    /**
     * Store a newly created meal plan (or update if exists for same date/meal_type).
     */
    public function store(StoreMealPlanRequest $request): MealPlanResource
    {
        $mealPlan = DB::transaction(function () use ($request): MealPlan {
            $validated = $request->validated();

            $mealPlan = MealPlan::updateOrCreate(
                [
                    'date' => $validated['date'],
                    'meal_type' => $validated['meal_type'],
                ],
                [
                    'user_id' => $request->user()->id,
                    'title' => $validated['title'],
                ],
            );

            $this->activityLogger->mealPlanCreated($mealPlan, $request->user());

            return $mealPlan;
        });

        return new MealPlanResource($mealPlan->load('ingredients'));
    }

    /**
     * Display the specified meal plan.
     */
    public function show(MealPlan $mealPlan): MealPlanResource
    {
        return new MealPlanResource($mealPlan->load('ingredients.item'));
    }

    /**
     * Update the specified meal plan.
     */
    public function update(UpdateMealPlanRequest $request, MealPlan $mealPlan): MealPlanResource
    {
        $mealPlan->update($request->validated());

        return new MealPlanResource($mealPlan->load('ingredients'));
    }

    /**
     * Remove the specified meal plan.
     */
    public function destroy(MealPlan $mealPlan): JsonResponse
    {
        $this->authorize('delete', $mealPlan);

        $mealPlan->delete();

        return $this->success(message: 'Meal plan deleted');
    }

    /**
     * Add an ingredient to a meal plan.
     */
    public function addIngredient(AddIngredientRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $ingredient = $mealPlan->ingredients()->create($request->validated());

        return $this->success(
            data: ['ingredient' => $ingredient->load('item')],
            message: 'Ingredient added',
        );
    }

    /**
     * Remove an ingredient from a meal plan.
     */
    public function removeIngredient(MealPlan $mealPlan, MealPlanIngredient $ingredient): JsonResponse
    {
        $this->authorize('update', $mealPlan);

        if ($ingredient->meal_plan_id !== $mealPlan->id) {
            return $this->forbidden('Ingredient does not belong to this meal plan');
        }

        $ingredient->delete();

        return $this->success(message: 'Ingredient removed');
    }

    /**
     * Add all ingredients from a meal plan to the shopping list.
     */
    public function addIngredientsToShoppingList(MealPlan $mealPlan): JsonResponse
    {
        $this->authorize('update', $mealPlan);

        $result = $this->addIngredientsAction->execute($mealPlan, auth()->user());

        return $this->success(
            data: $result,
            message: "{$result['added_count']} ingredients added to shopping list",
        );
    }

    /**
     * Get meal title suggestions for autocomplete.
     */
    public function suggestMeals(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $meals = MealPlan::where('title', 'ILIKE', "%{$query}%")
            ->select('title')
            ->groupBy('title')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->pluck('title');

        return response()->json($meals);
    }

    /**
     * Get all unique meals for the meals library.
     */
    public function getMealsLibrary(): JsonResponse
    {
        $meals = MealPlan::select('title')
            ->selectRaw('COUNT(*) as usage_count')
            ->selectRaw('MAX(created_at) as last_used')
            ->groupBy('title')
            ->orderBy('last_used', 'desc')
            ->get()
            ->map(fn ($meal) => [
                'title' => $meal->title,
                'usage_count' => $meal->usage_count,
                'last_used' => $meal->last_used,
            ]);

        return response()->json($meals);
    }
}
