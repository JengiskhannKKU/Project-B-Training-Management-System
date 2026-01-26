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

        try {
            $enrollment = DB::transaction(function () use ($user, $data) {
                // SECURITY FIX: Lock session row to prevent race condition
                $session = TrainingSession::lockForUpdate()->findOrFail($data['session_id']);

                // Check session status
                if ($session->status !== 'scheduled') {
                    throw new \Exception('Cannot enroll: Session is closed or not open for registration.');
                }

                // Check existing enrollment
                $existingEnrollment = Enrollment::where('user_id', $user->id)
                    ->where('session_id', $session->id)
                    ->first();

                if ($existingEnrollment && $existingEnrollment->status !== 'cancelled') {
                    throw new \Exception('You are already enrolled in this session.');
                }

                // Check capacity INSIDE transaction with lock
                $activeEnrollmentsCount = $session->enrollments()
                    ->where('status', '!=', 'cancelled')
                    ->count();

                if ($activeEnrollmentsCount >= $session->capacity) {
                    throw new \Exception('Cannot enroll: Session capacity is full.');
                }

                // Create or reactivate enrollment
                if ($existingEnrollment) {
                    $existingEnrollment->update([
                        'status' => 'pending',
                        'enrolled_at' => now(),
                    ]);

                    return ['enrollment' => $existingEnrollment, 'isReactivation' => true];
                }

                $newEnrollment = Enrollment::create([
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                    'status' => 'pending',
                    'enrolled_at' => now(),
                ]);

                return ['enrollment' => $newEnrollment, 'isReactivation' => false];
            });

            return response()->json([
                'message' => $enrollment['isReactivation'] ? 'Enrollment reactivated successfully.' : 'Enrollment created successfully.',
                'data' => $enrollment['enrollment'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
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
     * List current user's enrollments with session + course info.
     */
    public function myEnrollments(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = Enrollment::with([
            'session.course',
            'review',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($enrollments);
    }
}
