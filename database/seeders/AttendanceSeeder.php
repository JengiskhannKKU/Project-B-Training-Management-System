<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = [
            // Session 3 - Data Science (Completed) - Enrollment IDs 6,7,8,9
            [
                'session_id' => 3,
                'enrollment_id' => 6,
                'checked_at' => Carbon::now()->subDays(30),
                'status' => 'present',
                'checked_by' => 3,
                'note' => 'On time',
            ],
            [
                'session_id' => 3,
                'enrollment_id' => 7,
                'checked_at' => Carbon::now()->subDays(30),
                'status' => 'present',
                'checked_by' => 3,
                'note' => null,
            ],
            [
                'session_id' => 3,
                'enrollment_id' => 8,
                'checked_at' => Carbon::now()->subDays(30),
                'status' => 'late',
                'checked_by' => 3,
                'note' => 'Arrived 15 minutes late',
            ],
            [
                'session_id' => 3,
                'enrollment_id' => 9,
                'checked_at' => Carbon::now()->subDays(30),
                'status' => 'present',
                'checked_by' => 3,
                'note' => 'Excellent participation',
            ],

            // Session 6 - Web Dev Winter (Completed) - Enrollment IDs 11,12
            [
                'session_id' => 6,
                'enrollment_id' => 11,
                'checked_at' => Carbon::now()->subDays(60),
                'status' => 'present',
                'checked_by' => 2,
                'note' => 'Great engagement',
            ],
            [
                'session_id' => 6,
                'enrollment_id' => 12,
                'checked_at' => Carbon::now()->subDays(60),
                'status' => 'present',
                'checked_by' => 2,
                'note' => null,
            ],
        ];

        foreach ($attendances as $attendance) {
            Attendance::create($attendance);
        }
    }
}
