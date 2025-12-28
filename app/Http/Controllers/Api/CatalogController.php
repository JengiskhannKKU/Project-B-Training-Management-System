<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Public catalog of approved + active programs.
     */
    public function programs(): JsonResponse
    {
        $programs = Program::query()
            ->where('approval_status', 'approved')
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json($programs);
    }

    /**
     * Public catalog of approved + open sessions for a program.
     */
    public function sessions(Program $program): JsonResponse
    {
        $sessions = TrainingSession::query()
            ->select([
                'id',
                'program_id',
                'title',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'capacity',
                'trainer_id',
                'trainer_name',
                'trainer_photo_url',
                'location',
                'status',
                'approval_status',
            ])
            ->with('trainer:id,name')
            ->where('program_id', $program->id)
            ->where('approval_status', 'approved')
            ->where('status', 'open')
            ->orderByDesc('start_date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json($sessions);
    }
}
