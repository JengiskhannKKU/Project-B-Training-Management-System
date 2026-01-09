<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Review;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a new review for a completed enrollment.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $enrollment = Enrollment::with('session.program')->findOrFail($data['enrollment_id']);

        // Verify user owns this enrollment
        if ($enrollment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review your own enrollments.',
            ], 403);
        }

        // Verify enrollment is completed
        if ($enrollment->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'You can only review completed sessions.',
            ], 422);
        }

        // Check if review already exists
        if ($enrollment->review) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this session.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'enrollment_id' => $enrollment->id,
            'session_id' => $enrollment->session_id,
            'program_id' => $enrollment->session->program_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }

    /**
     * Update an existing review.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();

        // Verify user owns this review
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own reviews.',
            ], 403);
        }

        $data = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($data);
        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => $review,
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $user = request()->user();

        // Verify user owns this review
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own reviews.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
            'data' => null,
        ]);
    }

    /**
     * Get all reviews for a program.
     */
    public function programReviews(Program $program): JsonResponse
    {
        $user = request()->user();
        if ($user && $user->isRole('trainer') && $program->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view feedback for this program.',
            ], 403);
        }

        $reviews = Review::with('user:id,name')
            ->where('program_id', $program->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->avg('rating');
        $totalReviews = $reviews->count();

        return response()->json([
            'success' => true,
            'message' => 'Program reviews retrieved successfully.',
            'data' => [
                'reviews' => $reviews,
                'average_rating' => $averageRating ? round($averageRating, 1) : null,
                'total_reviews' => $totalReviews,
            ],
        ]);
    }

    /**
     * Get all reviews for a session.
     */
    public function sessionReviews(TrainingSession $session): JsonResponse
    {
        $user = request()->user();
        if ($user && $user->isRole('trainer') && $session->trainer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view feedback for this session.',
            ], 403);
        }

        $reviews = Review::with('user:id,name')
            ->where('session_id', $session->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->avg('rating');
        $totalReviews = $reviews->count();

        return response()->json([
            'success' => true,
            'message' => 'Session reviews retrieved successfully.',
            'data' => [
                'reviews' => $reviews,
                'average_rating' => $averageRating ? round($averageRating, 1) : null,
                'total_reviews' => $totalReviews,
            ],
        ]);
    }
}
