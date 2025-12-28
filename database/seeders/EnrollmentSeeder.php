<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = [
            // Session 1 - Web Dev Fundamentals Spring (Open)
            [
                'user_id' => 5,
                'session_id' => 1,
                'status' => 'confirmed',
                'enrolled_at' => Carbon::now()->subDays(4),
                'completed_at' => null,
            ],
            [
                'user_id' => 6,
                'session_id' => 1,
                'status' => 'confirmed',
                'enrolled_at' => Carbon::now()->subDays(3),
                'completed_at' => null,
            ],
            [
                'user_id' => 7,
                'session_id' => 1,
                'status' => 'pending',
                'enrolled_at' => Carbon::now()->subDays(1),
                'completed_at' => null,
            ],

            // Session 2 - Advanced React (Open)
            [
                'user_id' => 5,
                'session_id' => 2,
                'status' => 'confirmed',
                'enrolled_at' => Carbon::now()->subDays(3),
                'completed_at' => null,
            ],
            [
                'user_id' => 8,
                'session_id' => 2,
                'status' => 'confirmed',
                'enrolled_at' => Carbon::now()->subDays(2),
                'completed_at' => null,
            ],

            // Session 3 - Data Science (Completed)
            [
                'user_id' => 6,
                'session_id' => 3,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(35),
                'completed_at' => Carbon::now()->subDays(10),
            ],
            [
                'user_id' => 7,
                'session_id' => 3,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(34),
                'completed_at' => Carbon::now()->subDays(10),
            ],
            [
                'user_id' => 8,
                'session_id' => 3,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(33),
                'completed_at' => Carbon::now()->subDays(10),
            ],
            [
                'user_id' => 9,
                'session_id' => 3,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(32),
                'completed_at' => Carbon::now()->subDays(10),
            ],

            // Session 4 - Cybersecurity (Upcoming)
            [
                'user_id' => 9,
                'session_id' => 4,
                'status' => 'confirmed',
                'enrolled_at' => Carbon::now()->subDays(2),
                'completed_at' => null,
            ],

            // Session 6 - Web Dev Winter (Completed)
            [
                'user_id' => 5,
                'session_id' => 6,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(65),
                'completed_at' => Carbon::now()->subDays(50),
            ],
            [
                'user_id' => 10,
                'session_id' => 6,
                'status' => 'completed',
                'enrolled_at' => Carbon::now()->subDays(64),
                'completed_at' => Carbon::now()->subDays(50),
            ],

            // Session 8 - Cancelled React session
            [
                'user_id' => 6,
                'session_id' => 8,
                'status' => 'cancelled',
                'enrolled_at' => Carbon::now()->subDays(9),
                'completed_at' => null,
            ],
        ];

        foreach ($enrollments as $enrollment) {
            Enrollment::create($enrollment);
        }
    }
}
