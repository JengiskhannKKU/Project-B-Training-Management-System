<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $trainer = User::where('email', 'trainer@example.com')->first();

        $courses = [
            [
                'code' => 'WEB-101',
                'title' => 'Web Development Fundamentals',
                'description' => 'Learn the basics of HTML, CSS, and JavaScript.',
                'category' => 'Programming',
                'level' => 'beginner',
                'learning_outcomes' => '• HTML5 structure\n• CSS3 styling\n• JavaScript basics',
                'target_audience' => 'Beginners wanting to start web development.',
                'prerequisites' => 'Basic computer skills.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'RCT-201',
                'title' => 'Advanced React Patterns',
                'description' => 'Deep dive into React hooks, state management, and performance optimization.',
                'category' => 'Programming',
                'level' => 'advanced',
                'learning_outcomes' => '• React Hooks\n• State Management\n• Performance Optimization',
                'target_audience' => 'Experienced React developers.',
                'prerequisites' => 'Solid understanding of React basics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'DS-101',
                'title' => 'Data Science 101',
                'description' => 'Introduction to data analysis and visualization using Python.',
                'category' => 'Data Science',
                'level' => 'beginner',
                'learning_outcomes' => '• Python for Data Science\n• Pandas & NumPy\n• Data Visualization',
                'target_audience' => 'Aspiring data scientists.',
                'prerequisites' => 'Basic Python knowledge.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']], // Find by code
                $course // Update with all fields if exists, or create new
            );
        }

        $this->command->info('Courses seeded successfully');
    }
}

