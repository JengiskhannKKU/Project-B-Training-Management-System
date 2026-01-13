<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::withCount('courses')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'courses' => $category->courses_count ?? 0,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ];
            });

        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'icon' => 'required|in:' . implode(',', Category::ICONS),
            'color' => 'required|in:' . implode(',', Category::COLORS),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'color' => $request->color,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'courses' => 0,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        $category->loadCount('courses');

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'courses' => $category->courses_count ?? 0,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'icon' => 'required|in:' . implode(',', Category::ICONS),
            'color' => 'required|in:' . implode(',', Category::COLORS),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update([
            'name' => $request->name,
            'icon' => $request->icon,
            'color' => $request->color,
        ]);

        $category->loadCount('courses');

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'color' => $category->color,
                'courses' => $category->courses_count ?? 0,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Check if category is being used by any courses
        $coursesCount = $category->courses()->count();

        if ($coursesCount > 0) {
            return response()->json([
                'message' => 'Cannot delete category that is being used by courses',
                'courses_count' => $coursesCount
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
