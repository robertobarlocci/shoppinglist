<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\LunchboxController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\MealPlanSuggestionController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/suggest', [ItemController::class, 'suggest']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);

    // Item actions
    Route::post('/items/{item}/move', [ItemController::class, 'move']);
    Route::post('/items/{id}/restore', [ItemController::class, 'restore']);
    Route::delete('/items/{id}/permanent', [ItemController::class, 'forceDelete']);
    Route::post('/items/{item}/recurring', [ItemController::class, 'setRecurring']);
    Route::delete('/items/{item}/recurring', [ItemController::class, 'removeRecurring']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Meal Plans
    Route::get('/meal-plans', [MealPlanController::class, 'index']);
    Route::post('/meal-plans', [MealPlanController::class, 'store']);
    Route::get('/meal-plans/suggest', [MealPlanController::class, 'suggestMeals']);
    Route::get('/meal-plans/library', [MealPlanController::class, 'getMealsLibrary']);
    Route::get('/meal-plans/{mealPlan}', [MealPlanController::class, 'show']);
    Route::put('/meal-plans/{mealPlan}', [MealPlanController::class, 'update']);
    Route::delete('/meal-plans/{mealPlan}', [MealPlanController::class, 'destroy']);

    // Meal Plan Ingredients
    Route::post('/meal-plans/{mealPlan}/ingredients', [MealPlanController::class, 'addIngredient']);
    Route::delete('/meal-plans/{mealPlan}/ingredients/{ingredient}', [MealPlanController::class, 'removeIngredient']);
    Route::post('/meal-plans/{mealPlan}/add-to-shopping-list', [MealPlanController::class, 'addIngredientsToShoppingList']);

    // Meal Plan Suggestions (Kids feature)
    Route::get('/meal-suggestions', [MealPlanSuggestionController::class, 'index']);
    Route::post('/meal-suggestions', [MealPlanSuggestionController::class, 'store']);
    Route::get('/meal-suggestions/{suggestion}', [MealPlanSuggestionController::class, 'show']);
    Route::delete('/meal-suggestions/{suggestion}', [MealPlanSuggestionController::class, 'destroy']);
    Route::post('/meal-suggestions/{suggestion}/approve', [MealPlanSuggestionController::class, 'approve']);
    Route::post('/meal-suggestions/{suggestion}/reject', [MealPlanSuggestionController::class, 'reject']);

    // Lunchbox Items (Kids feature)
    Route::get('/lunchbox', [LunchboxController::class, 'index']);
    Route::post('/lunchbox', [LunchboxController::class, 'store']);
    Route::delete('/lunchbox/{lunchboxItem}', [LunchboxController::class, 'destroy']);
    Route::get('/lunchbox/autocomplete', [LunchboxController::class, 'autocomplete']);

    // Activities
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::get('/activities/unread', [ActivityController::class, 'unread']);
    Route::post('/activities/mark-read', [ActivityController::class, 'markAsRead']);

    // Offline sync
    Route::post('/sync', [SyncController::class, 'sync']);
});
