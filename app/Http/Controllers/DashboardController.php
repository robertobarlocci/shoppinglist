<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $categories = Category::ordered()->get();

        return Inertia::render('Dashboard', [
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'color' => $category->color,
                    'icon' => $category->icon,
                    'is_default' => $category->is_default,
                    'sort_order' => $category->sort_order,
                ];
            }),
        ]);
    }

    /**
     * Display the meal planner.
     */
    public function mealPlanner()
    {
        return Inertia::render('MealPlanner');
    }
}
