<?php

namespace App\Http\Controllers;

use App\Models\CertificateRequest;
use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TrainerDashboardController extends Controller
{
    public function index()
    {
        $trainerId = Auth::id();

        // Get trainer's sessions
        $trainerSessions = TrainingSession::where('trainer_id', $trainerId)->get();
        $sessionIds = $trainerSessions->pluck('id');

        // 1. Total Trainees - unique users enrolled in trainer's sessions
        $totalTrainees = Enrollment::whereIn('session_id', $sessionIds)
            ->distinct('user_id')
            ->count('user_id');

        // Calculate growth (compare with last period)
        $lastMonthSessionIds = TrainingSession::where('trainer_id', $trainerId)
            ->where('created_at', '<', Carbon::now()->subMonth())
            ->pluck('id');
        $lastMonthTrainees = Enrollment::whereIn('session_id', $lastMonthSessionIds)
            ->distinct('user_id')
            ->count('user_id');

        $traineesGrowth = $lastMonthTrainees > 0
            ? (($totalTrainees - $lastMonthTrainees) / $lastMonthTrainees) * 100
            : 0;

        // 2. Total Courses - count of distinct programs this trainer teaches
        $totalCourses = TrainingSession::where('trainer_id', $trainerId)
            ->distinct('program_id')
            ->count('program_id');

        $activeCourses = TrainingSession::where('trainer_id', $trainerId)
            ->where('status', 'active')
            ->distinct('program_id')
            ->count('program_id');

        $pendingCourses = TrainingSession::where('trainer_id', $trainerId)
            ->where('approval_status', 'pending')
            ->distinct('program_id')
            ->count('program_id');

        // 3. Pending Certifications
        $pendingCertifications = CertificateRequest::where('trainer_id', $trainerId)
            ->where('status', 'pending')
            ->count();

        // 4. Teaching Hours (last 7 days)
        $teachingHours = $this->getTeachingHours($trainerId);

        // 5. Top 3 Courses by enrollment
        $topCourses = $this->getTopCourses($trainerId);

        // 6. Trainee Engagement
        $engagement = $this->getTraineeEngagement($sessionIds);

        // 7. Department Distribution
        $departments = $this->getDepartmentDistribution($sessionIds);

        return Inertia::render('Trainer/Dashboard', [
            'stats' => [
                'trainees' => [
                    'value' => $totalTrainees,
                    'growth' => round($traineesGrowth, 1),
                    'trend' => $traineesGrowth >= 0 ? 'up' : 'down',
                ],
                'courses' => [
                    'total' => $totalCourses,
                    'active' => $activeCourses,
                    'pending' => $pendingCourses,
                ],
                'pendingCertifications' => [
                    'value' => $pendingCertifications,
                    'growth' => 0,
                    'trend' => 'neutral',
                ],
            ],
            'teachingHours' => $teachingHours,
            'topCourses' => $topCourses,
            'engagement' => $engagement,
            'departments' => $departments,
        ]);
    }

    private function getTeachingHours($trainerId)
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $hours = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');

            $dayHours = TrainingSession::where('trainer_id', $trainerId)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->get()
                ->sum(function ($session) {
                    if (!$session->start_time || !$session->end_time) {
                        return 0;
                    }

                    try {
                        $start = Carbon::parse($session->start_time);
                        $end = Carbon::parse($session->end_time);
                        return $start->diffInHours($end);
                    } catch (\Exception $e) {
                        return 0;
                    }
                });

            $hours[] = (int) $dayHours;
        }

        return [
            'days' => $days,
            'hours' => $hours,
        ];
    }

    private function getTopCourses($trainerId)
    {
        $topPrograms = DB::table('training_sessions')
            ->join('programs', 'training_sessions.program_id', '=', 'programs.id')
            ->leftJoin('enrollments', 'training_sessions.id', '=', 'enrollments.session_id')
            ->where('training_sessions.trainer_id', $trainerId)
            ->select(
                'programs.id',
                'programs.name',
                'programs.category',
                DB::raw('COUNT(DISTINCT enrollments.user_id) as enrollment_count')
            )
            ->groupBy('programs.id', 'programs.name', 'programs.category')
            ->orderBy('enrollment_count', 'desc')
            ->limit(3)
            ->get();

        $badgeColors = ['bg-amber-500', 'bg-gray-400', 'bg-orange-600'];

        return $topPrograms->map(function ($program, $index) use ($badgeColors) {
            return [
                'rank' => $index + 1,
                'name' => $program->name,
                'category' => $program->category ?? 'Uncategorized',
                'enrollments' => $program->enrollment_count,
                'badgeColor' => $badgeColors[$index] ?? 'bg-gray-400',
            ];
        })->values()->toArray();
    }

    private function getTraineeEngagement($sessionIds)
    {
        if ($sessionIds->isEmpty()) {
            return [
                'highlyEngaged' => 0,
                'moderate' => 0,
                'atRisk' => 0,
            ];
        }

        // Get all enrollments with attendance counts
        $enrollments = Enrollment::whereIn('session_id', $sessionIds)
            ->with(['attendances', 'session'])
            ->get();

        $highlyEngaged = 0;
        $moderate = 0;
        $atRisk = 0;

        foreach ($enrollments as $enrollment) {
            // Get total sessions for this enrollment's program
            $totalSessions = TrainingSession::where('program_id', $enrollment->session->program_id)
                ->where('start_date', '<=', Carbon::now())
                ->count();

            if ($totalSessions == 0) {
                continue;
            }

            // Count attended sessions
            $attendedSessions = $enrollment->attendances()
                ->where('status', 'present')
                ->count();

            $attendanceRate = ($attendedSessions / $totalSessions) * 100;

            if ($attendanceRate >= 80) {
                $highlyEngaged++;
            } elseif ($attendanceRate >= 50) {
                $moderate++;
            } else {
                $atRisk++;
            }
        }

        return [
            'highlyEngaged' => $highlyEngaged,
            'moderate' => $moderate,
            'atRisk' => $atRisk,
        ];
    }

    private function getDepartmentDistribution($sessionIds)
    {
        if ($sessionIds->isEmpty()) {
            return [
                'data' => [],
                'labels' => [],
            ];
        }

        $distribution = DB::table('enrollments')
            ->join('users', 'enrollments.user_id', '=', 'users.id')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->whereIn('enrollments.session_id', $sessionIds)
            ->select('profiles.department', DB::raw('COUNT(DISTINCT enrollments.user_id) as count'))
            ->groupBy('profiles.department')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $data = [];
        $labels = [];

        foreach ($distribution as $dept) {
            $labels[] = $dept->department ?? 'Unassigned';
            $data[] = (int) $dept->count;
        }

        return [
            'data' => $data,
            'labels' => $labels,
        ];
    }
}
