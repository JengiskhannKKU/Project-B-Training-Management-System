<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use App\Models\Category;
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

        // Get category IDs
        $itCategoryId = Category::where('name', 'IT')->value('id');
        $managementCategoryId = Category::where('name', 'Management')->value('id');
        $designCategoryId = Category::where('name', 'Design')->value('id');
        $marketingCategoryId = Category::where('name', 'Marketing')->value('id');
        $businessCategoryId = Category::where('name', 'Business')->value('id');

        $courses = [
            [
                'code' => 'WEB-101',
                'title' => 'Web Development Fundamentals',
                'description' => 'Learn the basics of HTML, CSS, and JavaScript.',
                'category_id' => $itCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• Understand HTML5 structure\n• Master CSS3 styling\n• Learn JavaScript basics',
                'target_audience' => 'Beginners wanting to start web development.',
                'prerequisites' => 'Basic computer skills.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'RCT-201',
                'title' => 'Advanced React patterns',
                'description' => 'Deep dive into React hooks, state management, and performance optimization.',
                'category_id' => $itCategoryId,
                'level' => 'advanced',
                'learning_outcomes' => '• Master React Hooks\n• State Management with Redux\n• Performance Optimization',
                'target_audience' => 'Experienced React developers.',
                'prerequisites' => 'Solid understanding of React basics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'DS-101',
                'title' => 'Data Science 101',
                'description' => 'Introduction to data analysis and visualization using Python.',
                'category_id' => $itCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• Python for Data Science\n• Pandas & NumPy\n• Data Visualization',
                'target_audience' => 'Aspiring data scientists.',
                'prerequisites' => 'Basic Python knowledge.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'SEC-201',
                'title' => 'Cybersecurity Essentials',
                'description' => 'Protect your systems and networks from digital attacks.',
                'category_id' => $itCategoryId,
                'level' => 'intermediate',
                'learning_outcomes' => '• Network Security\n• Ethical Hacking Basics\n• Threat Mitigation',
                'target_audience' => 'IT professionals.',
                'prerequisites' => 'Networking basics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'PM-101',
                'title' => 'Project Management Essentials',
                'description' => 'Learn agile and traditional project management methodologies.',
                'category_id' => $managementCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• Agile/Scrum\n• Project Planning\n• Risk Management',
                'target_audience' => 'Team leads and managers.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'UX-101',
                'title' => 'UI/UX Design Principles',
                'description' => 'Create user-centered designs for web and mobile.',
                'category_id' => $designCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• User Research\n• Wireframing\n• Prototyping',
                'target_audience' => 'Designers and product managers.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'MKT-101',
                'title' => 'Digital Marketing Strategy',
                'description' => 'Master digital marketing channels and analytics.',
                'category_id' => $marketingCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• SEO/SEM\n• Social Media Marketing\n• Analytics',
                'target_audience' => 'Marketing professionals.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'BUS-101',
                'title' => 'Business Strategy Fundamentals',
                'description' => 'Learn core business strategy concepts and frameworks.',
                'category_id' => $businessCategoryId,
                'level' => 'beginner',
                'learning_outcomes' => '• Strategic Analysis\n• Competitive Advantage\n• Business Models',
                'target_audience' => 'Business professionals and entrepreneurs.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
