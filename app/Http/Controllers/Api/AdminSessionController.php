<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\SessionDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class AdminSessionController extends Controller
{
    /**
     * Create a new session directly (admin bypass approval).
     */
    public function store(Request $request)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can use this endpoint.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date', 'before:end_at'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'min_participants' => ['required', 'integer', 'min:1'],
            'capacity' => ['required', 'integer', 'gte:min_participants'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'mode' => ['required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
        ]);

        // Determine status based on dates if not provided
        $status = $validated['status'] ?? null;
        if (!$status) {
            $start = Carbon::parse($validated['start_at']);
            $end = Carbon::parse($validated['end_at']);
            $now = now();

            $status = 'scheduled';
            if ($now->gt($end)) {
                $status = 'completed';
            } elseif ($now->gte($start)) {
                $status = 'ongoing';
            }
        }

        $session = TrainingSession::create([
            ...$validated,
            'status' => $status,
        ]);

        // Generate session days for multi-day attendance tracking
        app(SessionDayService::class)->generateSessionDays($session);

        return response()->json([
            'success' => true,
            'message' => 'Session created successfully',
            'data' => $session->load('sessionDays'),
        ], 201);
    }

    /**
     * Update a session directly (admin bypass approval).
     */
    public function update(Request $request, TrainingSession $session)
    {
        if (!$request->user()->isRole('admin')) {
            return $this->forbiddenResponse('Only admin can use this endpoint.');
        }

        $validated = $request->validate([
            'course_id' => ['sometimes', 'required', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'start_at' => ['sometimes', 'required', 'date', 'before:end_at'],
            'end_at' => ['sometimes', 'required', 'date', 'after:start_at'],
            'min_participants' => ['sometimes', 'required', 'integer', 'min:1'],
            'capacity' => ['sometimes', 'required', 'integer', 'gte:min_participants'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'mode' => ['sometimes', 'required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])],
        ]);

        $session->update($validated);

        // Regenerate session days if dates changed
        if ($request->has(['start_at']) || $request->has(['end_at'])) {
            try {
                app(SessionDayService::class)->updateSessionDays($session);
            } catch (\RuntimeException $e) {
                // Attendance exists, cannot auto-regenerate
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => 'Cannot regenerate session days with existing attendance',
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->fresh()->load('sessionDays'),
        ]);
    }
}
