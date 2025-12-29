<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Create a new enrollment for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
        ]);

        $user = $request->user();
        $session = TrainingSession::withCount([
            'enrollments as active_enrollments_count' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            },
        ])->findOrFail($data['session_id']);

        if ($session->approval_status !== 'approved' || $session->status !== 'open') {
            return response()->json(['message' => 'Cannot enroll: Session is closed or not open for registration.'], 422);
        }

        if ($session->status === 'completed') {
            return response()->json(['message' => 'Cannot enroll: Session has already been completed.'], 422);
        }

        if ($session->active_enrollments_count >= $session->capacity) {
            return response()->json(['message' => 'Cannot enroll: Session capacity is full.'], 422);
        }

        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('session_id', $session->id)
            ->first();

        if ($existingEnrollment && $existingEnrollment->status !== 'cancelled') {
            return response()->json(['message' => 'You are already enrolled in this session.'], 422);
        }

        $enrollment = DB::transaction(function () use ($user, $session, $existingEnrollment) {
            if ($existingEnrollment) {
                $existingEnrollment->update([
                    'status' => 'pending',
                    'enrolled_at' => now(),
                ]);

                return $existingEnrollment;
            }

            return Enrollment::create([
                'user_id' => $user->id,
                'session_id' => $session->id,
                'status' => 'pending',
                'enrolled_at' => now(),
            ]);
        });

        return response()->json([
            'message' => $existingEnrollment ? 'Enrollment reactivated successfully.' : 'Enrollment created successfully.',
            'data' => $enrollment,
        ], 201);
    }

    /**
     * Cancel an enrollment (before session start).
     */
    public function cancel(Request $request, Enrollment $enrollment): JsonResponse
    {
        $user = $request->user();

        if ($enrollment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = $enrollment->session()->firstOrFail();

        if (Carbon::parse($session->start_date)->lte(Carbon::today())) {
            return response()->json(['message' => 'Cannot cancel on or after the start date.'], 422);
        }

        if ($enrollment->status === 'cancelled') {
            return response()->json(['message' => 'Enrollment already cancelled.'], 200);
        }

        $enrollment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Enrollment cancelled successfully.',
            'data' => $enrollment,
        ]);
    }

    /**
     * List current user's enrollments with session + program info.
     */
    public function myEnrollments(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = Enrollment::with([
            'session.program',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($enrollments);
    }
}
