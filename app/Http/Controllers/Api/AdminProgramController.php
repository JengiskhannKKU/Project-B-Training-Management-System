<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProgramController extends Controller
{
    /**
     * Create a new program directly (admin bypass approval).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:programs,code'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        // Admin creates program directly with approved status
        $program = Program::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program created successfully',
            'data' => $program,
        ], 201);
    }

    /**
     * Update a program directly (admin bypass approval).
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('programs', 'code')->ignore($program->id)],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_hours' => ['sometimes', 'required', 'integer', 'min:1'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ]);

        // Update program directly
        $program->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Program updated successfully',
            'data' => $program->fresh(),
        ]);
    }
}
