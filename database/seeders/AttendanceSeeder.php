<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Find enrollments in completed sessions
        $enrollments = Enrollment::whereHas('session', function ($q) {
            $q->where('status', 'completed');
        })->with('session')->get();

        // Create attendance records for completed sessions
        foreach ($enrollments as $enrollment) {
            // Randomly present/absent/late
            $rand = rand(0, 10);
            if ($rand > 2) {
                $status = 'present';
                $note = null;
            } elseif ($rand > 1) {
                $status = 'late';
                $note = 'Arrived 15 minutes late';
            } else {
                $status = 'absent';
                $note = 'Excused absence';
            }

            Attendance::updateOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                ],
                [
                    'session_id' => $enrollment->session_id,
                    'checked_at' => $enrollment->session->start_at
                        ? Carbon::parse($enrollment->session->start_at)->addHours(2)
                        : Carbon::now(),
                    'status' => $status,
                    'checked_by' => $enrollment->session->trainer_id,
                    'note' => $note,
                ]
            );
        }

        $this->command->info('Attendance records seeded: ' . $enrollments->count());
    }
}
