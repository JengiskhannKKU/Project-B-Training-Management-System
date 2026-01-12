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
            $q->where('name', 'trainee');
        })->get();

        // Safety check
        if ($allTrainees->isEmpty()) {
            return;
        }

        // Get sessions by title (matching TrainingSessionSeeder)
        $webSpring = TrainingSession::where('title', 'Web Development Spring 2025')->first();
        $webWinter = TrainingSession::where('title', 'Web Development Winter 2024')->first();
        $reactQ1 = TrainingSession::where('title', 'Advanced React Q1 2025')->first();
        $dsWinter = TrainingSession::where('title', 'Data Science Winter 2025')->first();
        $secFeb = TrainingSession::where('title', 'Cybersecurity February 2025')->first();
        $cloudMar = TrainingSession::where('title', 'Cloud Computing March 2025')->first();
        $devOpsApril = TrainingSession::where('title', 'DevOps April 2025')->first();
        $devOpsFall = TrainingSession::where('title', 'DevOps Completed Fall 2024')->first();

        $enrollments = [];

        // 1. Enroll Main Trainee in variety
        if ($traineeMain) {
            // Scheduled/Active (Web Spring)
            if ($webSpring) {
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $webSpring->id,
                    'status' => 'confirmed',
                    'enrolled_at' => Carbon::now()->subDays(5),
                    'completed_at' => null,
                ];
            }

            // Completed (Data Science)
            if ($dsWinter) {
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $dsWinter->id,
                    'status' => 'completed',
                    'enrolled_at' => Carbon::now()->subDays(60),
                    'completed_at' => Carbon::now()->subDays(35),
                ];
            }

            // Pending (Cloud)
            if ($cloudMar) {
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $cloudMar->id,
                    'status' => 'pending',
                    'enrolled_at' => Carbon::now()->subDays(1),
                    'completed_at' => null,
                ];
            }

            // Completed Old (Web Winter)
            if ($webWinter) {
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $webWinter->id,
                    'status' => 'completed',
                    'enrolled_at' => Carbon::now()->subDays(100),
                    'completed_at' => Carbon::now()->subDays(70),
                ];
            }

            // Confirmed (DevOps Fall - completed)
            if ($devOpsFall) {
                $enrollments[] = [
                    'user_id' => $traineeMain->id,
                    'session_id' => $devOpsFall->id,
                    'status' => 'completed',
                    'enrolled_at' => Carbon::now()->subDays(140),
                    'completed_at' => Carbon::now()->subDays(110),
                ];
            }
        }

        // 2. Enroll other trainees randomly
        foreach ($allTrainees as $trainee) {
            if ($trainee->id === $traineeMain?->id) {
                continue;
            }

            // Available sessions
            $availableSessions = collect([$webSpring, $reactQ1, $dsWinter, $secFeb, $cloudMar, $devOpsApril, $webWinter, $devOpsFall])->filter();

            // Randomly enroll in 2-4 sessions
            $sessionsToEnroll = $availableSessions->random(rand(2, min(4, $availableSessions->count())));

            foreach ($sessionsToEnroll as $session) {
                $status = 'confirmed';
                $completedAt = null;

                if ($session->status === 'completed') {
                    $status = 'completed';
                    $completedAt = Carbon::parse($session->end_at)->addDay();
                } elseif ($session->status === 'scheduled') {
                    // Randomly assign pending, confirmed, or cancelled for scheduled sessions
                    $rand = rand(0, 10);
                    if ($rand < 2) {
                        $status = 'pending';
                    } elseif ($rand < 3) {
                        $status = 'cancelled';
                    }
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

        // Insert enrollments
        foreach ($enrollments as $enrollment) {
            Enrollment::updateOrCreate(
                ['user_id' => $enrollment['user_id'], 'session_id' => $enrollment['session_id']],
                $enrollment
            );
        }

        $this->command->info('Enrollments seeded: ' . count($enrollments));
    }
}
