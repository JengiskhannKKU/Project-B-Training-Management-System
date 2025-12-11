<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    /**
     * Return all programs.
     */
    public function index()
    {
        $programs = Program::latest()->get();

        return $this->successResponse($programs, 'Programs retrieved successfully');
    }

    /**
     * Create a new program.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:programs,code'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['created_by'] = $request->user()?->id;

        $program = Program::create($data);

        return $this->createdResponse($program, 'Program created successfully');
    }

    /**
     * Show a single program.
     */
    public function show(Program $program)
    {
        return $this->successResponse($program, 'Program retrieved successfully');
    }

    /**
     * Update a program.
     */
    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('programs', 'code')->ignore($program->id)],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'duration_hours' => ['sometimes', 'required', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'approval_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
            'approval_note' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ]);

        $program->update($data);

        return $this->successResponse($program->fresh(), 'Program updated successfully');
    }

    /**
     * Delete a program (hard delete).
     */
    public function destroy(Program $program)
    {
        $program->delete();

        return $this->successResponse(null, 'Program deleted successfully');
    }
}
