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

        $sessions = [
            [
                'course_id' => $getCourseId('Web Development Fundamentals'),
                'title' => 'Web Dev Batch 1',
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(10),
                'start_time' => '09:00:00',
                'end_time' => '16:00:00',
                'capacity' => 20,
                'trainer_id' => $trainer->id,
                'location' => 'Online',
                'status' => 'open',
                'approval_status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
            // Add more sessions matching seeded courses
        ];

        foreach ($sessions as $session) {
            if ($session['course_id']) {
                TrainingSession::create($session);
            }
        }
    }
}

