<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class TrainingSessionController extends Controller
{
    /**
     * Return all sessions, optionally filtered by program.
     */
    public function index(Request $request)
    {
        $query = TrainingSession::query()->latest();

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }

        $sessions = $query->get();

        return response()->json($sessions);
    }

    /**
     * Create a new session for a program.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1'],
            'trainer_id' => ['required', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['upcoming', 'open', 'closed', 'completed', 'cancelled'])],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $session = TrainingSession::create($data);

        return response()->json([
            'message' => 'Session created successfully.',
            'data' => $session,
        ], 201);
    }

    /**
     * Show a single session.
     */
    public function show(TrainingSession $session)
    {
        return response()->json($session);
    }

    /**
     * Update a session.
     */
    public function update(Request $request, TrainingSession $session)
    {
        $data = $request->validate([
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
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
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['upcoming', 'open', 'closed', 'completed', 'cancelled'])],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
        ]);

        $session->update($data);

        return response()->json([
            'message' => 'Session updated successfully.',
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Delete a session (hard delete).
     */
    public function destroy(TrainingSession $session)
    {
        $session->delete();

        return response()->json([
            'message' => 'Session deleted successfully.',
        ]);
    }
}
