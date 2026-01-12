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
        $allTrainees = User::whereHas('role', function ($q) {
            $q->where('name', 'trainee');
        })->get();

        // Safety check
        if ($allTrainees->isEmpty()) {
            $this->command->warn('No trainees found.');
            return;
        }

        // Get all non-cancelled sessions
        $sessions = TrainingSession::whereIn('status', ['completed', 'scheduled'])->get();

        if ($sessions->isEmpty()) {
            $this->command->warn('No training sessions found.');
            return;
        }

        $enrollments = [];

        // Each session should have 10-30 enrollments to make it realistic
        foreach ($sessions as $session) {
            // Determine number of enrollments based on capacity
            $enrollmentCount = min(
                rand((int)($session->capacity * 0.5), (int)($session->capacity * 0.9)),
                $allTrainees->count()
            );

            // Randomly select trainees for this session
            $selectedTrainees = $allTrainees->random(min($enrollmentCount, $allTrainees->count()));

            foreach ($selectedTrainees as $trainee) {
                // Skip if already enrolled (avoid duplicates)
                $alreadyEnrolled = collect($enrollments)->contains(function ($e) use ($trainee, $session) {
                    return $e['user_id'] === $trainee->id && $e['session_id'] === $session->id;
                });

                if ($alreadyEnrolled) {
                    continue;
                }

                $status = 'confirmed';
                $completedAt = null;

                if ($session->status === 'completed') {
                    // 85% complete completed sessions, 10% cancelled, 5% no-show
                    $rand = rand(0, 100);
                    if ($rand < 85) {
                        $status = 'completed';
                        $completedAt = Carbon::parse($session->end_at)->addDay();
                    } elseif ($rand < 95) {
                        $status = 'cancelled';
                    } else {
                        $status = 'no-show';
                    }

                    $enrolledDate = Carbon::parse($session->start_at)->subDays(rand(10, 40));
                } else {
                    // For scheduled sessions: 60% confirmed, 30% pending, 10% cancelled
                    $rand = rand(0, 100);
                    if ($rand < 60) {
                        $status = 'confirmed';
                    } elseif ($rand < 90) {
                        $status = 'pending';
                    } else {
                        $status = 'cancelled';
                    }

                    $enrolledDate = Carbon::now()->subDays(rand(1, 20));
                }

                $enrollments[] = [
                    'user_id' => $trainee->id,
                    'session_id' => $session->id,
                    'status' => $status,
                    'attendance_percent' => 0.00,
                    'enrolled_at' => $enrolledDate,
                    'completed_at' => $completedAt,
                ];
            }
        }

        // Insert enrollments (avoid duplicates)
        foreach ($enrollments as $enrollment) {
            Enrollment::updateOrCreate(
                ['user_id' => $enrollment['user_id'], 'session_id' => $enrollment['session_id']],
                $enrollment
            );
        }

        $this->command->info('Enrollments seeded: ' . count($enrollments));
    }
}
