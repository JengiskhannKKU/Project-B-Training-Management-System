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
        $trainer = User::where('email', 'trainer@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$trainer) {
            return;
        }

        // Helper to get course ID by title
        $getCourseId = function ($title) {
            return Course::where('title', $title)->value('id');
        };

        $now = now();

        $sessions = [
            // Web Development Sessions
            [
                'course_id' => $getCourseId('Web Development Fundamentals'),
                'title' => 'Web Development Spring 2025',
                'start_at' => $now->copy()->addDays(5),
                'end_at' => $now->copy()->addDays(10),
                'min_participants' => 5,
                'capacity' => 30,
                'registration_start' => $now->copy()->subDays(10),
                'registration_end' => $now->copy()->addDays(3),
                'mode' => 'online',
                'online_link' => 'https://meeting.example.com/web-dev-spring',
                'trainer_id' => $trainer->id,
                'location' => 'Online',
                'status' => 'scheduled',
            ],
            [
                'course_id' => $getCourseId('Web Development Fundamentals'),
                'title' => 'Web Development Winter 2024',
                'start_at' => $now->copy()->subDays(80),
                'end_at' => $now->copy()->subDays(70),
                'min_participants' => 5,
                'capacity' => 30,
                'registration_start' => $now->copy()->subDays(100),
                'registration_end' => $now->copy()->subDays(85),
                'mode' => 'onsite',
                'trainer_id' => $trainer->id,
                'location' => 'Room 101, Main Building',
                'status' => 'completed',
            ],

            // React Sessions
            [
                'course_id' => $getCourseId('Advanced React patterns'),
                'title' => 'Advanced React Q1 2025',
                'start_at' => $now->copy()->addDays(15),
                'end_at' => $now->copy()->addDays(20),
                'min_participants' => 5,
                'capacity' => 20,
                'registration_start' => $now->copy()->subDays(5),
                'registration_end' => $now->copy()->addDays(12),
                'mode' => 'online',
                'online_link' => 'https://meeting.example.com/react-q1',
                'trainer_id' => $trainer->id,
                'location' => 'Online',
                'status' => 'scheduled',
            ],

            // Data Science Sessions
            [
                'course_id' => $getCourseId('Data Science 101'),
                'title' => 'Data Science Winter 2025',
                'start_at' => $now->copy()->subDays(45),
                'end_at' => $now->copy()->subDays(35),
                'min_participants' => 10,
                'capacity' => 50,
                'registration_start' => $now->copy()->subDays(65),
                'registration_end' => $now->copy()->subDays(50),
                'mode' => 'hybrid',
                'online_link' => 'https://meeting.example.com/data-science-winter',
                'trainer_id' => $trainer->id,
                'location' => 'Lab 3, Science Building',
                'status' => 'completed',
            ],

            // Cybersecurity Sessions
            [
                'course_id' => $getCourseId('Cybersecurity Essentials'),
                'title' => 'Cybersecurity February 2025',
                'start_at' => $now->copy()->addDays(25),
                'end_at' => $now->copy()->addDays(30),
                'min_participants' => 5,
                'capacity' => 25,
                'registration_start' => $now->copy()->addDays(5),
                'registration_end' => $now->copy()->addDays(20),
                'mode' => 'onsite',
                'trainer_id' => $trainer->id,
                'location' => 'Security Lab, Tech Center',
                'status' => 'scheduled',
            ],

            // Cloud Computing Sessions
            [
                'course_id' => $getCourseId('Cloud Computing with AWS'),
                'title' => 'Cloud Computing March 2025',
                'start_at' => $now->copy()->addDays(35),
                'end_at' => $now->copy()->addDays(42),
                'min_participants' => 5,
                'capacity' => 40,
                'registration_start' => $now->copy()->addDays(10),
                'registration_end' => $now->copy()->addDays(30),
                'mode' => 'online',
                'online_link' => 'https://meeting.example.com/cloud-march',
                'trainer_id' => $trainer->id,
                'location' => 'Online',
                'status' => 'scheduled',
            ],

            // DevOps Sessions
            [
                'course_id' => $getCourseId('DevOps Practices'),
                'title' => 'DevOps April 2025',
                'start_at' => $now->copy()->addDays(50),
                'end_at' => $now->copy()->addDays(58),
                'min_participants' => 5,
                'capacity' => 30,
                'registration_start' => $now->copy()->addDays(20),
                'registration_end' => $now->copy()->addDays(45),
                'mode' => 'hybrid',
                'online_link' => 'https://meeting.example.com/devops-april',
                'trainer_id' => $trainer->id,
                'location' => 'Innovation Hub, Room 5',
                'status' => 'scheduled',
            ],
            [
                'course_id' => $getCourseId('DevOps Practices'),
                'title' => 'DevOps Completed Fall 2024',
                'start_at' => $now->copy()->subDays(120),
                'end_at' => $now->copy()->subDays(110),
                'min_participants' => 5,
                'capacity' => 30,
                'registration_start' => $now->copy()->subDays(140),
                'registration_end' => $now->copy()->subDays(125),
                'mode' => 'onsite',
                'trainer_id' => $trainer->id,
                'location' => 'Training Center A',
                'status' => 'completed',
            ],
        ];

        foreach ($sessions as $session) {
            if ($session['course_id']) {
                TrainingSession::create($session);
            }
        }

        $this->command->info('Training sessions seeded: ' . count($sessions));
    }
}
