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
        $query = Course::query()
            ->with('category')
            ->withCount('sessions');

        // Optional filtering can be added here
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $courses = $query->latest()->get();

        // Include category data in response
        $courses = $courses->map(function ($course) {
            return array_merge($course->toArray(), [
                'category_name' => $course->category?->name,
                'category_icon' => $course->category?->icon_name,
                'category_color' => $course->category?->color,
            ]);
        });

        return response()->json($courses);
    }

    /**
     * Get courses for the authenticated trainer.
     * Returns all published courses (trainers can view all courses in the system).
     */
    public function trainerCourses(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Get all published courses (trainers can see all courses)
        $courses = Course::where('status', 'published')
            ->with('category')
            ->withCount('sessions')
            ->latest()
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->title,
                    'description' => $course->description,
                    'category' => $course->category?->name,
                    'category_id' => $course->category_id,
                    'category_icon' => $course->category?->icon_name,
                    'category_color' => $course->category?->color,
                    'image_url' => $course->thumbnail_path,
                    'level' => $course->level,
                    'sessions_count' => $course->sessions_count,
                    'enrolled_count' => $course->enrolled_count,
                    'max_participants' => $course->max_participants,
                    'status' => $course->status,
                    'created_at' => $course->created_at,
                ];
            });

        return response()->json(['data' => $courses]);
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
            'category_id' => 'required|exists:categories,id',
            'level' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'thumbnail_path' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['draft', 'published'])], // Only draft and published allowed for new courses (archived only after creation)
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
    public function show(Course $course)
    {
        $course->load(['sessions', 'owner', 'category'])->loadCount('sessions');

        // Return data in the format expected by the Show page
        return response()->json([
            'data' => [
                'id' => $course->id,
                'name' => $course->title,
                'title' => $course->title,
                'code' => $course->code,
                'category' => $course->category?->name,
                'category_id' => $course->category_id,
                'level' => $course->level,
                'period' => '', // Not in new schema
                'time' => '', // Not applicable at course level
                'location' => '', // Not applicable at course level
                'trainer' => $course->owner?->name ?? '', // Owner is the creator
                'certificated' => '', // Not in new schema
                'status' => $course->status,
                'description' => $course->description,
                'image_url' => $course->thumbnail_path,
                'full_description' => $course->description,
                'learning_outcomes' => $course->learning_outcomes,
                'target_audience' => $course->target_audience,
                'prerequisites' => $course->prerequisites,
                'additional_info' => $course->additional_info,
                'duration_hours' => 0, // Not in new schema
                'sessions_count' => $course->sessions_count,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        if (!$request->user()->isRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'level' => 'nullable|string',
            'learning_outcomes' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'thumbnail_path' => 'nullable|string|max:255',
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])], // All statuses allowed when updating
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
    public function destroy(Request $request, Course $course)
    {
        if (!$request->user()->isRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
