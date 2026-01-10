<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Course::query()->withCount('sessions');

        // Optional filtering can be added here
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Admin check should typically be middleware, but explicit check here for safety
        if (!$request->user()->isRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'level' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'thumbnail_path' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'min_participants' => 'required|integer|min:1',
            'max_participants' => 'required|integer|gte:min_participants',
        ]);

        $course = Course::create([
            ...$validated,
            'owner_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Course created successfully',
            'data' => $course
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $course = Course::findOrFail($id);
        return response()->json(['data' => $course]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$request->user()->isRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:100',
            'level' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'thumbnail_path' => 'nullable|string|max:255',
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])],
            'min_participants' => 'sometimes|required|integer|min:1',
            'max_participants' => 'sometimes|required|integer|gte:min_participants',
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully',
            'data' => $course
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->isRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
