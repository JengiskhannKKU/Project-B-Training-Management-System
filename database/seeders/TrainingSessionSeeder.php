<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\TrainingSession;
use App\Models\SessionDay;
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
        $modes = ['online', 'onsite', 'hybrid'];
        $locations = [
            'online' => 'Online',
            'onsite' => ['Room 101, Main Building', 'Lab 3, Science Building', 'Security Lab, Tech Center', 'Training Center A', 'Innovation Hub, Room 5'],
            'hybrid' => ['Conference Room A', 'Hybrid Learning Center', 'Training Room 302']
        ];

        // Create 1-2 sessions per course with different statuses and times
        foreach ($courses as $course) {
            $numberOfSessions = rand(1, 2);

            for ($i = 0; $i < $numberOfSessions; $i++) {
                $trainer = $trainers->random();
                $mode = $modes[array_rand($modes)];

                // Determine session status and dates
                $statusRand = rand(0, 100);
                if ($statusRand < 40) {
                    // 40% completed sessions (in the past)
                    $status = 'completed';
                    $baseDate = $now->copy()->subDays(rand(30, 180));
                    $registrationStart = $baseDate->copy()->subDays(rand(20, 40));
                    $registrationEnd = $baseDate->copy()->subDays(rand(1, 5));
                } elseif ($statusRand < 75) {
                    // 35% scheduled sessions (future)
                    $status = 'scheduled';
                    $baseDate = $now->copy()->addDays(rand(5, 90));
                    $registrationStart = $now->copy()->subDays(rand(1, 10));
                    $registrationEnd = $baseDate->copy()->subDays(rand(1, 3));
                } elseif ($statusRand < 90) {
                    // 15% ongoing sessions (currently happening)
                    $status = 'scheduled';
                    $baseDate = $now->copy()->subDays(rand(1, 3));
                    $registrationStart = $baseDate->copy()->subDays(rand(15, 30));
                    $registrationEnd = $baseDate->copy()->subDays(1);
                } else {
                    // 10% cancelled sessions
                    $status = 'cancelled';
                    $baseDate = $now->copy()->addDays(rand(10, 60));
                    $registrationStart = $now->copy()->subDays(rand(5, 15));
                    $registrationEnd = $baseDate->copy()->subDays(rand(3, 7));
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

                // Create or update session
                $session = TrainingSession::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $sessionTitle,
                    ],
                    [
                        'min_participants' => rand(5, 10),
                        'capacity' => rand(20, 50),
                        'registration_start' => $registrationStart,
                        'registration_end' => $registrationEnd,
                        'mode' => $mode,
                        'online_link' => $onlineLink,
                        'trainer_id' => $trainer->id,
                        'location' => $location,
                        'status' => $status,
                    ]
                );

                // Delete existing session days for this session
                SessionDay::where('session_id', $session->id)->delete();

                // Create session days (random 2-3 specific dates)
                $numDays = rand(2, 3);
                $sessionDays = [];

                // Use a set to track unique dates
                $usedDates = [];

                for ($day = 0; $day < $numDays; $day++) {
                    // Keep generating dates until we find a unique one
                    do {
                        // Increment day by at least 1 to ensure unique dates
                        $daysToAdd = $day + ($day > 0 ? rand(0, 2) : 0);
                        $dayDate = $baseDate->copy()->addDays($daysToAdd);
                        $dateString = $dayDate->format('Y-m-d');
                    } while (in_array($dateString, $usedDates));

                    $usedDates[] = $dateString;

                    // Random times between 9 AM and 5 PM
                    $startHour = rand(9, 15);
                    $durationHours = rand(2, 4);
                    $startTime = sprintf('%02d:00', $startHour);
                    $endTime = sprintf('%02d:00', $startHour + $durationHours);

                    $sessionDays[] = [
                        'session_id' => $session->id,
                        'date' => $dateString,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day_number' => $day + 1,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                SessionDay::insert($sessionDays);
            }
        }

        $this->command->info('Training sessions seeded successfully');
    }
}
