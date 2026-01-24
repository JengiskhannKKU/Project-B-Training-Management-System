<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationRequest;
use App\Models\Evaluation;
use App\Models\Session;
use App\Models\TrainingSession;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EvaluationController extends Controller
{
    /**
     * Display sessions available for feedback
     */
    public function index()
    {
        $user = Auth::user();

        // Get sessions where:
        // 1. User has a certificate issued
        // 2. User has >= 80% attendance
        // 3. Feedback not yet submitted
        $sessions = TrainingSession::with(['course', 'trainer'])
            ->whereHas('certificates', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['approved', 'completed']);
            })
            ->where('status', 'completed')
            ->get()
            ->map(function ($session) use ($user) {
                // Calculate attendance rate
                $enrollment = $session->enrollments()->where('user_id', $user->id)->first();
                $attendanceRate = $this->calculateAttendanceRate($session->id, $user->id);

                // Check if feedback already submitted
                $feedbackSubmitted = Evaluation::where('session_id', $session->id)
                    ->where('user_id', $user->id)
                    ->exists();

                return [
                    'id' => $session->id,
                    'course_name' => $session->course->title ?? 'Unknown Course',
                    'trainer_name' => $session->trainer->name ?? 'Unknown Trainer',
                    'completed_date' => $session->end_at ? $session->end_at->format('F d, Y') : 'N/A',
                    'attendance_rate' => $attendanceRate,
                    'can_submit_feedback' => $attendanceRate >= 80 && !$feedbackSubmitted,
                    'feedback_submitted' => $feedbackSubmitted,
                ];
            })
            ->filter(function ($session) {
                // Only show sessions with >= 80% attendance
                return $session['attendance_rate'] >= 80;
            })
            ->values();

        return Inertia::render('Trainee/Feedback/Index', [
            'sessions' => $sessions
        ]);
    }

    /**
     * Calculate attendance rate for a user in a session
     * Uses the attendance_percent field from enrollment (calculated by multi-day attendance system)
     */
    private function calculateAttendanceRate($sessionId, $userId)
    {
        // Get the enrollment for this user and session
        $enrollment = DB::table('enrollments')
            ->where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$enrollment) {
            return 0;
        }

        // Return the attendance percentage calculated by the multi-day attendance system
        // This is automatically updated when attendance is marked for each session day
        return $enrollment->attendance_percent ?? 0;
    }

    /**
     * Store a new evaluation (POST /sessions/{id}/evaluation)
     */
    public function store(StoreEvaluationRequest $request, $id)
    {
        $session = TrainingSession::findOrFail($id);

        // Authorize using Policy
        $this->authorize('submitEvaluation', $session);

        // Get validated data from Form Request
        $validated = $request->validated();

        // Create evaluation with submitted_at automatically set
        $evaluation = Evaluation::create([
            'session_id' => $session->id,
            'user_id' => Auth::id(),
            'overall_rating' => $validated['overall_rating'],
            'content_quality' => $validated['content_quality'],
            'trainer_quality' => $validated['trainer_quality'],
            'material_quality' => $validated['material_quality'],
            'organization' => $validated['organization'],
            'would_recommend' => $validated['would_recommend'],
            'difficulty_level' => $validated['difficulty_level'],
            'strengths' => $validated['strengths'],
            'improvements' => $validated['improvements'],
            'comments' => $validated['comments'],
            'submitted_at' => now(),
        ]);

        return redirect()->route('trainee.feedback.index')
            ->with('success', 'Thank you for your feedback!');
    }

    /**
     * Show all evaluations for a session (GET /api/sessions/{id}/evaluation)
     * Only accessible by Admin and Trainer
     * Returns JSON response
     */
    public function show($id)
    {
        $session = TrainingSession::with(['course', 'trainer'])->findOrFail($id);

        // Debug logging
        \Log::info('EvaluationController@show', [
            'session_id' => $id,
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role->name ?? 'unknown',
            'session_trainer_id' => $session->trainer_id,
        ]);

        // Authorize using Policy
        $this->authorize('viewSessionEvaluations', $session);

        // Get all evaluations for this session with user info
        $evaluations = Evaluation::where('session_id', $id)
            ->with('user')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'trainee_name' => $evaluation->user->name ?? 'Unknown',
                    'overall_rating' => $evaluation->overall_rating,
                    'content_quality' => $evaluation->content_quality,
                    'trainer_quality' => $evaluation->trainer_quality,
                    'material_quality' => $evaluation->material_quality,
                    'organization' => $evaluation->organization,
                    'would_recommend' => $evaluation->would_recommend,
                    'difficulty_level' => $evaluation->difficulty_level,
                    'strengths' => $evaluation->strengths,
                    'improvements' => $evaluation->improvements,
                    'comments' => $evaluation->comments,
                    'submitted_at' => $evaluation->submitted_at->format('F d, Y H:i'),
                ];
            });

        // Calculate averages
        $averages = [
            'overall_rating' => round($evaluations->avg('overall_rating'), 2),
            'content_quality' => round($evaluations->avg('content_quality'), 2),
            'trainer_quality' => round($evaluations->avg('trainer_quality'), 2),
            'material_quality' => round($evaluations->avg('material_quality'), 2),
            'organization' => round($evaluations->avg('organization'), 2),
            'would_recommend_percentage' => round($evaluations->where('would_recommend', true)->count() / max($evaluations->count(), 1) * 100, 2),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'course_name' => $session->course->title ?? 'Unknown',
                    'trainer_name' => $session->trainer->name ?? 'Unknown',
                ],
                'evaluations' => $evaluations,
                'averages' => $averages,
                'total_evaluations' => $evaluations->count(),
            ]
        ]);
    }

    /**
     * Get evaluation statistics for dashboard
     * GET /api/evaluations/statistics
     */
    public function statistics(Request $request, EvaluationService $evaluationService)
    {
        $user = Auth::user();
        $trainerId = null;

        // If trainer, filter by their ID
        if ($user->role->name === 'trainer') {
            $trainerId = $user->id;
        }

        $statistics = $evaluationService->getDashboardStatistics($trainerId);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get overall evaluation statistics (Admin only)
     * GET /api/evaluations/overall-statistics
     */
    public function overallStatistics(EvaluationService $evaluationService)
    {
        $statistics = $evaluationService->getOverallStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
