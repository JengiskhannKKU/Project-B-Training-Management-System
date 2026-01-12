<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SessionDay;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Find enrollments in completed sessions
        $enrollments = Enrollment::whereHas('session', function ($q) {
            $q->where('status', 'completed');
        })->where('status', 'completed')
            ->with(['session', 'session.sessionDays'])
            ->get();

        $attendanceCount = 0;
        $enrollmentAttendancePercent = [];

        // Create attendance records for each session day
        foreach ($enrollments as $enrollment) {
            $sessionDays = $enrollment->session->sessionDays;

            // If no session days, skip
            if ($sessionDays->isEmpty()) {
                continue;
            }

            $presentDays = 0;
            $totalDays = 0;

            // Create attendance for each session day
            foreach ($sessionDays as $sessionDay) {
                // Skip cancelled session days
                if ($sessionDay->status === 'cancelled') {
                    continue;
                }

                $totalDays++;

                // Randomly determine attendance status (80% present, 15% late, 5% absent)
                $rand = rand(0, 100);
                if ($rand < 80) {
                    $status = 'present';
                    $note = null;
                    $presentDays++;
                } elseif ($rand < 95) {
                    $status = 'late';
                    $note = 'Arrived ' . rand(5, 30) . ' minutes late';
                    $presentDays++; // Late counts as present for attendance calculation
                } else {
                    $status = 'absent';
                    $notes = [
                        'Sick leave',
                        'Personal emergency',
                        'Excused absence',
                        'No notification',
                    ];
                    $note = $notes[array_rand($notes)];
                }

                // Create attendance with session day reference
                $checkedAt = Carbon::parse($sessionDay->date)
                    ->setTimeFromTimeString($sessionDay->start_time)
                    ->addMinutes(rand(0, 30));

                Attendance::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'session_day_id' => $sessionDay->id,
                    ],
                    [
                        'session_id' => $enrollment->session_id,
                        'user_id' => $enrollment->user_id,
                        'checked_at' => $checkedAt,
                        'status' => $status,
                        'checked_by' => $enrollment->session->trainer_id,
                        'note' => $note,
                    ]
                );

                $attendanceCount++;
            }

            // Calculate attendance percentage
            if ($totalDays > 0) {
                $attendancePercent = ($presentDays / $totalDays) * 100;
                $enrollmentAttendancePercent[$enrollment->id] = round($attendancePercent, 2);
            }
        }

        // Update enrollment attendance percentages
        foreach ($enrollmentAttendancePercent as $enrollmentId => $attendancePercent) {
            Enrollment::where('id', $enrollmentId)->update([
                'attendance_percent' => $attendancePercent
            ]);
        }

        $this->command->info('Attendance records seeded: ' . $attendanceCount);
        $this->command->info('Updated attendance percent for ' . count($enrollmentAttendancePercent) . ' enrollments');
    }
}
