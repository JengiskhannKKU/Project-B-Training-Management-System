<?php


namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $traineeMain = User::firstWhere('email', 'trainee@example.com');
        $allTrainees = User::whereHas('role', function ($q) {
            $q->where('name', 'trainee'); })->get();

        // Safety check
        if ($allTrainees->isEmpty())
            return;

        // Get sessions by some characteristic or just all
        $webSpring = TrainingSession::where('title', 'like', '%Web Development%Spring%')->first();
        $reactQ1 = TrainingSession::where('title', 'like', '%React%Q1%')->first();
        $dsWinter = TrainingSession::where('title', 'like', '%Data Science%Winter 2025%')->first(); // Completed
        $secFeb = TrainingSession::where('title', 'like', '%Cybersecurity%February%')->first(); // Upcoming
        $cloudMar = TrainingSession::where('title', 'like', '%Cloud%March%')->first(); // Pending/Upcoming
        $webWinterOld = TrainingSession::where('title', 'like', '%Web Development%Winter 2024%')->first(); // Old Completed

        $enrollments = [];

        // 1. Enroll Main Trainee in variety
        if ($traineeMain) {
            // Enrolled in Active (Web Spring)
            if ($webSpring)
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $webSpring->id,
                    'status' => 'confirmed',
                    'enrolled_at' => Carbon::now()->subDays(5),
                    'completed_at' => null,
                ];
            // Completed (Data Science)
            if ($dsWinter)
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $dsWinter->id,
                    'status' => 'completed',
                    'enrolled_at' => Carbon::now()->subDays(40),
                    'completed_at' => Carbon::now()->subDays(10),
                ];
            // Pending (Cloud)
            if ($cloudMar)
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $cloudMar->id,
                    'status' => 'pending',
                    'enrolled_at' => Carbon::now()->subDays(1),
                    'completed_at' => null,
                ];
            // Completed Old (Web Winter)
            if ($webWinterOld)
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $webWinterOld->id,
                    'status' => 'completed',
                    'enrolled_at' => Carbon::now()->subDays(70),
                    'completed_at' => Carbon::now()->subDays(50),
                ];
        }

        // 2. Enroll other trainees randomly
        foreach ($allTrainees as $trainee) {
            if ($trainee->id === $traineeMain?->id)
                continue;

            // Randomly enroll in sessions
            $sessionsToEnroll = collect([$webSpring, $reactQ1, $dsWinter, $secFeb, $webWinterOld])->filter()->random(2);

            foreach ($sessionsToEnroll as $session) {
                $status = 'confirmed';
                $completedAt = null;

                if ($session->status === 'completed') {
                    $status = 'completed';
                    $completedAt = $session->end_date;
                } elseif ($session->status === 'cancelled') {
                    $status = 'cancelled';
                }

                $enrollments[] = [
                    'user_id' => $trainee->id,
                    'session_id' => $session->id,
                    'status' => $status,
                    'enrolled_at' => Carbon::now()->subDays(rand(2, 60)),
                    'completed_at' => $completedAt,
                ];
            }
        }

        foreach ($enrollments as $enrollment) {
            Enrollment::updateOrCreate(
                ['user_id' => $enrollment['user_id'], 'session_id' => $enrollment['session_id']],
                $enrollment
            );
        }
    }
}

