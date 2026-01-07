<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategoryController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::ordered()->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
        ]);

        // Check category limit
        $categoryCount = Category::count();
        if ($categoryCount >= 20) {
            return response()->json([
                'message' => 'Maximale Anzahl von 20 Kategorien erreicht',
            ], 422);
        }

        $category = Category::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
            'is_default' => false,
            'sort_order' => $categoryCount,
        ]);

        $this->activityLogger->categoryCreated($category, auth()->user());

        return new CategoryResource($category);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        // Prevent updating default categories
        if ($category->is_default) {
            return response()->json([
                'message' => 'Standard-Kategorien können nicht bearbeitet werden',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:categories,name,' . $category->id,
            'color' => 'sometimes|required|string|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return new CategoryResource($category);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Prevent deleting default categories
        if ($category->is_default) {
            return response()->json([
                'message' => 'Standard-Kategorien können nicht gelöscht werden',
            ], 422);
        }

        // Move items to "Sonstiges" category before deleting
        $otherCategory = Category::where('slug', 'other')->first();

        if ($otherCategory && $category->items()->count() > 0) {
            $category->items()->update(['category_id' => $otherCategory->id]);
        }

        $category->delete();

        return response()->json(['message' => 'Kategorie gelöscht']);
    }
}
