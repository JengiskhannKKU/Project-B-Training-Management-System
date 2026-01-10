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
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'trainer_photo_url' => ['nullable', 'string', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['upcoming', 'open', 'closed', 'completed', 'cancelled'])],
        ]);

        $session = TrainingSession::create([
            ...$validated,
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
                    if ($endDateInput && Carbon::parse($value)->gte(Carbon::parse($endDateInput))) {
                        $fail('The start date must be before the end date.');
                    }
                },
            ],
            'end_date' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request, $session) {
                    $startDateInput = $request->input('start_date') ?? optional($session->start_date)->toDateString();
                    if ($startDateInput && Carbon::parse($value)->lte(Carbon::parse($startDateInput))) {
                        $fail('The end date must be after the start date.');
                    }
                },
            ],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'trainer_photo_url' => ['nullable', 'string', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['upcoming', 'open', 'closed', 'completed', 'cancelled'])],
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'data' => $session->fresh(),
        ]);
    }
}
