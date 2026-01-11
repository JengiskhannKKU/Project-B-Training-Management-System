<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
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
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'], // removed after:start_time because dates might differ
            'min_participants' => ['required', 'integer', 'min:1'],
            'max_participants' => ['required', 'integer', 'gte:min_participants'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'mode' => ['required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'trainer_photo_url' => ['nullable', 'string', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        // Determine status based on dates
        $start = Carbon::parse($validated['start_date'] . ' ' . ($validated['start_time'] ?? '00:00'));
        $end = Carbon::parse($validated['end_date'] . ' ' . ($validated['end_time'] ?? '23:59'));
        $now = now();

        $status = 'upcoming';
        if ($now->gt($end)) {
            $status = 'completed';
        } elseif ($now->gte($start)) {
            $status = 'open';
        }

        $session = TrainingSession::create([
            ...$validated,
            'status' => $status,
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session created successfully',
            'data' => $session,
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request, $session) {
                    $endDateInput = $request->input('end_date') ?? optional($session->end_date)->toDateString();
                    if ($endDateInput && Carbon::parse($value)->gt(Carbon::parse($endDateInput))) {
                        $fail('The start date must be before or equal to the end date.');
                    }
                },
            ],
            'end_date' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request, $session) {
                    $startDateInput = $request->input('start_date') ?? optional($session->start_date)->toDateString();
                    if ($startDateInput && Carbon::parse($value)->lt(Carbon::parse($startDateInput))) {
                        $fail('The end date must be after or equal to the start date.');
                    }
                },
            ],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'min_participants' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_participants' => ['sometimes', 'required', 'integer', 'gte:min_participants'],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date', 'after_or_equal:registration_start'],
            'mode' => ['sometimes', 'required', Rule::in(['onsite', 'online', 'hybrid'])],
            'online_link' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'trainer_photo_url' => ['nullable', 'string', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->fresh(),
        ]);
    }
}
