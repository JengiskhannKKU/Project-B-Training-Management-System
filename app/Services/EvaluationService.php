<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    /**
     * Calculate average ratings for a session
     *
     * @param int $sessionId
     * @return array
     */
    public function getSessionAverages(int $sessionId): array
    {
        $evaluations = Evaluation::where('session_id', $sessionId)->get();

        if ($evaluations->isEmpty()) {
            return [
                'total_evaluations' => 0,
                'overall_rating' => null,
                'content_quality' => null,
                'trainer_quality' => null,
                'material_quality' => null,
                'organization' => null,
                'would_recommend_percentage' => null,
            ];
        }

        return [
            'total_evaluations' => $evaluations->count(),
            'overall_rating' => round($evaluations->avg('overall_rating'), 2),
            'content_quality' => round($evaluations->avg('content_quality'), 2),
            'trainer_quality' => round($evaluations->avg('trainer_quality'), 2),
            'material_quality' => round($evaluations->avg('material_quality'), 2),
            'organization' => round($evaluations->avg('organization'), 2),
            'would_recommend_percentage' => round(
                ($evaluations->where('would_recommend', true)->count() / $evaluations->count()) * 100,
                2
            ),
        ];
    }

    /**
     * Get evaluation statistics for dashboard (optimized query)
     *
     * @param int|null $trainerId - Filter by trainer ID (null for all)
     * @return array
     */
    public function getDashboardStatistics(?int $trainerId = null): array
    {
        $query = DB::table('evaluations')
            ->join('training_sessions', 'evaluations.session_id', '=', 'training_sessions.id')
            ->select(
                'training_sessions.id as session_id',
                'training_sessions.title as session_title',
                DB::raw('COUNT(evaluations.id) as total_evaluations'),
                DB::raw('ROUND(AVG(evaluations.overall_rating), 2) as avg_overall_rating'),
                DB::raw('ROUND(AVG(evaluations.content_quality), 2) as avg_content_quality'),
                DB::raw('ROUND(AVG(evaluations.trainer_quality), 2) as avg_trainer_quality'),
                DB::raw('ROUND(AVG(evaluations.material_quality), 2) as avg_material_quality'),
                DB::raw('ROUND(AVG(evaluations.organization), 2) as avg_organization'),
                DB::raw('ROUND(SUM(CASE WHEN evaluations.would_recommend = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(evaluations.id), 2) as recommend_percentage')
            )
            ->groupBy('training_sessions.id', 'training_sessions.title');

        // Filter by trainer if specified
        if ($trainerId !== null) {
            $query->where('training_sessions.trainer_id', $trainerId);
        }

        return $query->get()->map(function ($item) {
            return [
                'session_id' => $item->session_id,
                'session_title' => $item->session_title,
                'total_evaluations' => (int) $item->total_evaluations,
                'averages' => [
                    'overall_rating' => (float) $item->avg_overall_rating,
                    'content_quality' => (float) $item->avg_content_quality,
                    'trainer_quality' => (float) $item->avg_trainer_quality,
                    'material_quality' => (float) $item->avg_material_quality,
                    'organization' => (float) $item->avg_organization,
                    'recommend_percentage' => (float) $item->recommend_percentage,
                ],
            ];
        })->toArray();
    }

    /**
     * Get evaluation data for CSV export
     *
     * @param int $sessionId
     * @return array
     */
    public function getExportData(int $sessionId): array
    {
        return Evaluation::where('session_id', $sessionId)
            ->with(['user', 'session.course', 'session.trainer'])
            ->get()
            ->map(function ($evaluation) {
                return [
                    'session_title' => $evaluation->session->title,
                    'course_name' => $evaluation->session->course->title ?? 'N/A',
                    'trainer_name' => $evaluation->session->trainer->name ?? 'N/A',
                    'trainee_name' => $evaluation->user->name ?? 'Unknown',
                    'overall_rating' => $evaluation->overall_rating,
                    'content_quality' => $evaluation->content_quality,
                    'trainer_quality' => $evaluation->trainer_quality,
                    'material_quality' => $evaluation->material_quality,
                    'organization' => $evaluation->organization,
                    'would_recommend' => $evaluation->would_recommend ? 'Yes' : 'No',
                    'difficulty_level' => ucfirst($evaluation->difficulty_level),
                    'strengths' => $evaluation->strengths,
                    'improvements' => $evaluation->improvements,
                    'comments' => $evaluation->comments ?? '',
                    'submitted_at' => $evaluation->submitted_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get overall statistics (for admin dashboard)
     *
     * @return array
     */
    public function getOverallStatistics(): array
    {
        $totalEvaluations = Evaluation::count();

        if ($totalEvaluations === 0) {
            return [
                'total_evaluations' => 0,
                'total_sessions_evaluated' => 0,
                'average_rating' => null,
                'recommend_percentage' => null,
            ];
        }

        $totalSessionsEvaluated = Evaluation::distinct('session_id')->count('session_id');
        $averageRating = Evaluation::avg('overall_rating');
        $recommendCount = Evaluation::where('would_recommend', true)->count();

        return [
            'total_evaluations' => $totalEvaluations,
            'total_sessions_evaluated' => $totalSessionsEvaluated,
            'average_rating' => round($averageRating, 2),
            'recommend_percentage' => round(($recommendCount / $totalEvaluations) * 100, 2),
        ];
    }
}
