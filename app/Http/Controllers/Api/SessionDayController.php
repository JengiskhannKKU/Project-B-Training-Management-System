<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SessionDay;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SessionDayController extends Controller
{
    /**
     * Get all session days for a specific session.
     */
    public function index(Request $request, $sessionId): JsonResponse
    {
        $session = TrainingSession::with('sessionDays')->findOrFail($sessionId);

        return response()->json([
            'session_days' => $session->sessionDays->sortBy('day_number')->values(),
        ]);
    }

    /**
     * Store a new session day.
     */
    public function store(Request $request, $sessionId): JsonResponse
    {
        $session = TrainingSession::findOrFail($sessionId);

        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'status' => 'in:active,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate day_number
        $maxDayNumber = $session->sessionDays()->max('day_number') ?? 0;
        $dayNumber = $maxDayNumber + 1;

        $sessionDay = $session->sessionDays()->create([
            'date' => $request->date,
            'start_time' => $request->start_time ?? '09:00:00',
            'end_time' => $request->end_time ?? '16:00:00',
            'day_number' => $dayNumber,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Session day created successfully',
            'session_day' => $sessionDay,
        ], 201);
    }

    /**
     * Show a specific session day.
     */
    public function show($sessionId, $dayId): JsonResponse
    {
        $sessionDay = SessionDay::where('session_id', $sessionId)
            ->with('session', 'attendances')
            ->findOrFail($dayId);

        return response()->json([
            'session_day' => $sessionDay,
        ]);
    }

    /**
     * Update a session day.
     */
    public function update(Request $request, $sessionId, $dayId): JsonResponse
    {
        $sessionDay = SessionDay::where('session_id', $sessionId)
            ->findOrFail($dayId);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'status' => 'in:active,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sessionDay->update($request->only([
            'date',
            'start_time',
            'end_time',
            'status',
        ]));

        return response()->json([
            'message' => 'Session day updated successfully',
            'session_day' => $sessionDay->fresh(),
        ]);
    }

    /**
     * Delete a session day.
     */
    public function destroy($sessionId, $dayId): JsonResponse
    {
        $sessionDay = SessionDay::where('session_id', $sessionId)
            ->findOrFail($dayId);

        $deletedDayNumber = $sessionDay->day_number;
        $sessionDay->delete();

        // Reorder remaining days
        SessionDay::where('session_id', $sessionId)
            ->where('day_number', '>', $deletedDayNumber)
            ->decrement('day_number');

        return response()->json([
            'message' => 'Session day deleted successfully',
        ]);
    }

    /**
     * Reorder session days.
     */
    public function reorder(Request $request, $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'day_ids' => 'required|array',
            'day_ids.*' => 'exists:session_days,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->day_ids as $index => $dayId) {
            SessionDay::where('id', $dayId)
                ->where('session_id', $sessionId)
                ->update(['day_number' => $index + 1]);
        }

        return response()->json([
            'message' => 'Session days reordered successfully',
        ]);
    }

    /**
     * Update the status of a session day.
     */
    public function updateStatus(Request $request, $sessionId, $dayId): JsonResponse
    {
        $sessionDay = SessionDay::where('session_id', $sessionId)
            ->findOrFail($dayId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sessionDay->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Session day status updated successfully',
            'session_day' => $sessionDay->fresh(),
        ]);
    }
}
