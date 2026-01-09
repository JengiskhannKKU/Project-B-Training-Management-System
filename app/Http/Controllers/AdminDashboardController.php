<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Review;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/AdminDashboard', [
            'stats' => $this->getStats(),
            'registrationData' => $this->getRegistrationTrend(),
            'topCategories' => $this->getTopCategories(),
            'topCourses' => $this->getTopCourses(),
            'topTrainers' => $this->getTopTrainers(),
        ]);
    }

    private function getStats()
    {
        // Get role IDs
        $trainerRoleId = \App\Models\Role::where('name', 'trainer')->first()?->id;
        $studentRoleId = \App\Models\Role::where('name', 'student')->first()?->id;

        // Total Trainers
        $totalTrainers = User::where('role_id', $trainerRoleId)->count();
        $lastMonthTrainers = User::where('role_id', $trainerRoleId)
            ->where('created_at', '<', Carbon::now()->subMonth())
            ->count();
        $trainersGrowth = $lastMonthTrainers > 0
            ? (($totalTrainers - $lastMonthTrainers) / $lastMonthTrainers) * 100
            : 0;

        // Generate sparkline data for trainers (last 12 months)
        $trainerSparkline = [];
        for ($i = 11; $i >= 0; $i--) {
            $count = User::where('role_id', $trainerRoleId)
                ->whereDate('created_at', '<=', Carbon::now()->subMonths($i)->endOfMonth())
                ->count();
            $trainerSparkline[] = $count;
        }

        // Total Trainees (students)
        $totalTrainees = User::where('role_id', $studentRoleId)->count();
        $lastMonthTrainees = User::where('role_id', $studentRoleId)
            ->where('created_at', '<', Carbon::now()->subMonth())
            ->count();
        $traineesGrowth = $lastMonthTrainees > 0
            ? (($totalTrainees - $lastMonthTrainees) / $lastMonthTrainees) * 100
            : 0;

        // Courses
        $totalCourses = Program::count();
        $activeCourses = Program::where('status', 'active')->count();
        $pendingCourses = Program::where('approval_status', 'pending')->count();

        // Completion Rate
        $totalEnrollments = Enrollment::count();
        $completedEnrollments = Enrollment::whereNotNull('completed_at')->count();
        $completionPercentage = $totalEnrollments > 0
            ? ($completedEnrollments / $totalEnrollments) * 100
            : 0;

        // Previous period for growth
        $lastMonthCompleted = Enrollment::whereNotNull('completed_at')
            ->where('completed_at', '<', Carbon::now()->subMonth())
            ->count();
        $lastMonthTotal = Enrollment::where('created_at', '<', Carbon::now()->subMonth())->count();
        $lastMonthCompletion = $lastMonthTotal > 0 ? ($lastMonthCompleted / $lastMonthTotal) * 100 : 0;
        $completionGrowth = $lastMonthCompletion > 0
            ? (($completionPercentage - $lastMonthCompletion) / $lastMonthCompletion) * 100
            : 0;

        // Satisfaction Rating
        $averageRating = Review::avg('rating') ?? 0;
        $totalReviews = Review::count();

        // Department Distribution
        $departments = $this->getDepartmentDistribution();

        return [
            'trainers' => [
                'value' => $totalTrainers,
                'growth' => round($trainersGrowth, 1),
                'trend' => $trainersGrowth >= 0 ? 'up' : 'down',
                'sparklineData' => $trainerSparkline,
            ],
            'trainees' => [
                'value' => $totalTrainees,
                'growth' => round($traineesGrowth, 1),
                'trend' => $traineesGrowth >= 0 ? 'up' : 'down',
            ],
            'courses' => [
                'total' => $totalCourses,
                'pending' => $pendingCourses,
                'active' => $activeCourses,
            ],
            'completion' => [
                'percentage' => round($completionPercentage, 1),
                'growth' => round($completionGrowth, 1),
                'trend' => $completionGrowth >= 0 ? 'up' : 'down',
            ],
            'satisfaction' => [
                'rating' => round($averageRating, 1),
                'maxRating' => 5.0,
                'totalReviews' => $totalReviews,
            ],
            'departments' => $departments,
        ];
    }

    private function getRegistrationTrend()
    {
        // Get last 6 months registration data
        $months = [];
        $currentYear = [];
        $lastYear = [];
        $twoYearsAgo = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M');

            // Current year
            $currentYear[] = Enrollment::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            // Last year
            $lastYearDate = $date->copy()->subYear();
            $lastYear[] = Enrollment::whereYear('created_at', $lastYearDate->year)
                ->whereMonth('created_at', $lastYearDate->month)
                ->count();

            // Two years ago
            $twoYearsAgoDate = $date->copy()->subYears(2);
            $twoYearsAgo[] = Enrollment::whereYear('created_at', $twoYearsAgoDate->year)
                ->whereMonth('created_at', $twoYearsAgoDate->month)
                ->count();
        }

        return [
            'months' => $months,
            'series' => [
                [
                    'name' => (string) (Carbon::now()->year - 2),
                    'data' => $twoYearsAgo,
                ],
                [
                    'name' => (string) (Carbon::now()->year - 1),
                    'data' => $lastYear,
                ],
                [
                    'name' => (string) Carbon::now()->year,
                    'data' => $currentYear,
                ],
            ],
        ];
    }

    private function getTopCategories()
    {
        $categories = Program::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $totalPrograms = Program::count();
        $colors = ['bg-blue-500', 'bg-purple-500', 'bg-emerald-500', 'bg-amber-500', 'bg-pink-500'];

        return $categories->map(function ($category, $index) use ($totalPrograms, $colors) {
            return [
                'name' => $category->category ?? 'Uncategorized',
                'percentage' => $totalPrograms > 0 ? round(($category->count / $totalPrograms) * 100) : 0,
                'count' => $category->count,
                'color' => $colors[$index] ?? 'bg-gray-500',
            ];
        })->toArray();
    }

    private function getTopCourses()
    {
        $topPrograms = Program::withCount('sessions as enrollment_count')
            ->join('training_sessions', 'programs.id', '=', 'training_sessions.program_id')
            ->join('enrollments', 'training_sessions.id', '=', 'enrollments.session_id')
            ->select('programs.id', 'programs.name', 'programs.category', DB::raw('COUNT(DISTINCT enrollments.user_id) as enrollment_count'))
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

    private function getTopTrainers()
    {
        $trainers = User::whereHas('role', function ($query) {
            $query->where('name', 'trainer');
        })
            ->withCount(['trainingSessions as courses_count'])
            ->with(['trainingSessions.enrollments'])
            ->get();

        $trainerStats = $trainers->map(function ($trainer) {
            // Count unique students across all trainer's sessions
            $studentIds = collect();
            foreach ($trainer->trainingSessions as $session) {
                $studentIds = $studentIds->merge($session->enrollments->pluck('user_id'));
            }

            // Get average rating from reviews of trainer's sessions
            $reviewIds = [];
            foreach ($trainer->trainingSessions as $session) {
                $sessionReviews = Review::where('session_id', $session->id)->pluck('rating');
                $reviewIds = array_merge($reviewIds, $sessionReviews->toArray());
            }
            $avgRating = count($reviewIds) > 0 ? array_sum($reviewIds) / count($reviewIds) : 0;

            return [
                'name' => $trainer->name,
                'rating' => round($avgRating, 1),
                'courses' => $trainer->courses_count,
                'students' => $studentIds->unique()->count(),
            ];
        })
            ->sortByDesc('rating')
            ->take(6)
            ->values()
            ->toArray();

        return $trainerStats;
    }

    private function getDepartmentDistribution()
    {
        $distribution = DB::table('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'student')
            ->select('profiles.department', DB::raw('COUNT(users.id) as count'))
            ->whereNotNull('profiles.department')
            ->groupBy('profiles.department')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $data = [];
        $labels = [];

        foreach ($distribution as $dept) {
            $labels[] = $dept->department;
            $data[] = (int) $dept->count;
        }

        // If no data, provide empty arrays
        if (empty($data)) {
            $data = [0];
            $labels = ['No Data'];
        }

        return [
            'data' => $data,
            'labels' => $labels,
        ];
    }
}
