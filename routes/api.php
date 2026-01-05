<?php

use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\MealPlanController;
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
    Route::get('/meal-plans/{mealPlan}', [MealPlanController::class, 'show']);
    Route::put('/meal-plans/{mealPlan}', [MealPlanController::class, 'update']);
    Route::delete('/meal-plans/{mealPlan}', [MealPlanController::class, 'destroy']);

    // Meal Plan Ingredients
    Route::post('/meal-plans/{mealPlan}/ingredients', [MealPlanController::class, 'addIngredient']);
    Route::delete('/meal-plans/{mealPlan}/ingredients/{ingredient}', [MealPlanController::class, 'removeIngredient']);
    Route::post('/meal-plans/{mealPlan}/add-to-shopping-list', [MealPlanController::class, 'addIngredientsToShoppingList']);

    // Activities
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::get('/activities/unread', [ActivityController::class, 'unread']);
    Route::post('/activities/mark-read', [ActivityController::class, 'markAsRead']);

    // Offline sync
    Route::post('/sync', [SyncController::class, 'sync']);
});
