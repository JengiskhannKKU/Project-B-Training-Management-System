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
                'title' => 'Web Development Fundamentals',
                'description' => 'Learn the basics of HTML, CSS, and JavaScript. This comprehensive course covers everything from structuring web pages to styling them and adding interactivity.',
                'category' => 'Programming',
                'level' => 'beginner',
                'learning_outcomes' => '• Understand HTML5 structure\n• Master CSS3 styling\n• Learn JavaScript basics',
                'target_audience' => 'Beginners wanting to start web development.',
                'prerequisites' => 'Basic computer skills.',
                'status' => 'published',
                'min_participants' => 5,
                'max_participants' => 30,
                'owner_id' => $trainer->id,
            ],
            [
                'title' => 'Advanced React patterns',
                'description' => 'Deep dive into React hooks, state management, and performance optimization.',
                'category' => 'Programming',
                'level' => 'advanced',
                'learning_outcomes' => '• Master React Hooks\n• State Management with Redux\n• Performance Optimization',
                'target_audience' => 'Experienced React developers.',
                'prerequisites' => 'Solid understanding of React basics.',
                'status' => 'published',
                'min_participants' => 5,
                'max_participants' => 20,
                'owner_id' => $trainer->id,
            ],
            [
                'title' => 'Data Science 101',
                'description' => 'Introduction to data analysis and visualization using Python.',
                'category' => 'Data Science',
                'level' => 'beginner',
                'learning_outcomes' => '• Python for Data Science\n• Pandas & NumPy\n• Data Visualization',
                'target_audience' => 'Aspiring data scientists.',
                'prerequisites' => 'Basic Python knowledge.',
                'status' => 'published',
                'min_participants' => 10,
                'max_participants' => 50,
                'owner_id' => $trainer->id,
            ],
            [
                'title' => 'Cybersecurity Essentials',
                'description' => 'Protect your systems and networks from digital attacks.',
                'category' => 'Security',
                'level' => 'intermediate',
                'learning_outcomes' => '• Network Security\n• Ethical Hacking Basics\n• Threat Mitigation',
                'target_audience' => 'IT professionals.',
                'prerequisites' => 'Networking basics.',
                'status' => 'published',
                'min_participants' => 5,
                'max_participants' => 25,
                'owner_id' => $trainer->id,
            ],
            [
                'title' => 'Cloud Computing with AWS',
                'description' => 'Deploy and manage applications on Amazon Web Services.',
                'category' => 'Cloud Computing',
                'level' => 'intermediate',
                'learning_outcomes' => '• EC2 & S3\n• IAM & Security\n• Serverless Computing',
                'target_audience' => 'DevOps engineers.',
                'prerequisites' => 'Linux basics.',
                'status' => 'published',
                'min_participants' => 5,
                'max_participants' => 40,
                'owner_id' => $trainer->id,
            ],
            [
                'title' => 'DevOps Practices',
                'description' => 'Learn CI/CD pipelines, containerization, and orchestration.',
                'category' => 'DevOps',
                'level' => 'advanced',
                'learning_outcomes' => '• Docker & Kubernetes\n• Jenkins/GitLab CI\n• Infrastructure as Code',
                'target_audience' => 'Developers and Ops professionals.',
                'prerequisites' => 'Software development lifecycle knowledge.',
                'status' => 'published',
                'min_participants' => 5,
                'max_participants' => 30,
                'owner_id' => $trainer->id,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}

