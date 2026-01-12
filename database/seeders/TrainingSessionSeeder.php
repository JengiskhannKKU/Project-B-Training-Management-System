<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainers = User::whereHas('role', function ($q) {
            $q->where('name', 'trainer');
        })->get();

        if ($trainers->isEmpty()) {
            $this->command->warn('No trainers found');
            return;
        }

        $courses = Course::all();
        if ($courses->isEmpty()) {
            $this->command->warn('No courses found');
            return;
        }

        $now = now();
        $sessions = [];
        $modes = ['online', 'onsite', 'hybrid'];
        $locations = [
            'online' => 'Online',
            'onsite' => ['Room 101, Main Building', 'Lab 3, Science Building', 'Security Lab, Tech Center', 'Training Center A', 'Innovation Hub, Room 5'],
            'hybrid' => ['Conference Room A', 'Hybrid Learning Center', 'Training Room 302']
        ];

        // Create 2-3 sessions per course with different statuses and times
        foreach ($courses as $course) {
            $numberOfSessions = rand(2, 3);

            for ($i = 0; $i < $numberOfSessions; $i++) {
                $trainer = $trainers->random();
                $mode = $modes[array_rand($modes)];

                // Determine session status and dates
                $statusRand = rand(0, 100);
                if ($statusRand < 40) {
                    // 40% completed sessions (in the past)
                    $status = 'completed';
                    $daysAgo = rand(30, 180);
                    $startDate = $now->copy()->subDays($daysAgo);
                    $endDate = $startDate->copy()->addDays(rand(3, 14));
                    $regStart = $startDate->copy()->subDays(rand(20, 40));
                    $regEnd = $startDate->copy()->subDays(rand(1, 5));
                } elseif ($statusRand < 75) {
                    // 35% scheduled sessions (future)
                    $status = 'scheduled';
                    $daysAhead = rand(5, 90);
                    $startDate = $now->copy()->addDays($daysAhead);
                    $endDate = $startDate->copy()->addDays(rand(3, 14));
                    $regStart = $now->copy()->subDays(rand(1, 10));
                    $regEnd = $startDate->copy()->subDays(rand(1, 3));
                } elseif ($statusRand < 90) {
                    // 15% ongoing sessions (currently happening)
                    $status = 'scheduled';
                    $daysAgo = rand(1, 3);
                    $startDate = $now->copy()->subDays($daysAgo);
                    $endDate = $now->copy()->addDays(rand(2, 7));
                    $regStart = $startDate->copy()->subDays(rand(15, 30));
                    $regEnd = $startDate->copy()->subDays(1);
                } else {
                    // 10% cancelled sessions
                    $status = 'cancelled';
                    $daysAhead = rand(10, 60);
                    $startDate = $now->copy()->addDays($daysAhead);
                    $endDate = $startDate->copy()->addDays(rand(3, 10));
                    $regStart = $now->copy()->subDays(rand(5, 15));
                    $regEnd = $startDate->copy()->subDays(rand(3, 7));
                }

                // Select location based on mode
                if ($mode === 'online') {
                    $location = $locations['online'];
                    $onlineLink = 'https://meeting.example.com/' . strtolower(str_replace(' ', '-', $course->title)) . '-' . rand(1000, 9999);
                } elseif ($mode === 'onsite') {
                    $location = $locations['onsite'][array_rand($locations['onsite'])];
                    $onlineLink = null;
                } else {
                    $location = $locations['hybrid'][array_rand($locations['hybrid'])];
                    $onlineLink = 'https://meeting.example.com/' . strtolower(str_replace(' ', '-', $course->title)) . '-hybrid-' . rand(1000, 9999);
                }

                // Generate session title
                $sessionNumber = $i + 1;
                $sessionTitle = $course->title . ' - Session ' . $sessionNumber;

                $sessions[] = [
                    'course_id' => $course->id,
                    'title' => $sessionTitle,
                    'start_at' => $startDate,
                    'end_at' => $endDate,
                    'min_participants' => rand(5, 10),
                    'capacity' => rand(20, 50),
                    'registration_start' => $regStart,
                    'registration_end' => $regEnd,
                    'mode' => $mode,
                    'online_link' => $onlineLink,
                    'trainer_id' => $trainer->id,
                    'location' => $location,
                    'status' => $status,
                ];
            }
        }

        // Create all sessions
        foreach ($sessions as $session) {
            TrainingSession::create($session);
        }

        $this->command->info('Training sessions seeded: ' . count($sessions));
    }
}
