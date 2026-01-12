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
                'description' => 'Learn the basics of HTML, CSS, and JavaScript. This comprehensive course covers everything from structuring web pages to styling them and adding interactivity.',
                'category' => 'Programming',
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
                'category' => 'Programming',
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
                'category' => 'Data Science',
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
                'category' => 'Security',
                'level' => 'intermediate',
                'learning_outcomes' => '• Network Security\n• Ethical Hacking Basics\n• Threat Mitigation',
                'target_audience' => 'IT professionals.',
                'prerequisites' => 'Networking basics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'AWS-201',
                'title' => 'Cloud Computing with AWS',
                'description' => 'Deploy and manage applications on Amazon Web Services.',
                'category' => 'Cloud Computing',
                'level' => 'intermediate',
                'learning_outcomes' => '• EC2 & S3\n• IAM & Security\n• Serverless Computing',
                'target_audience' => 'DevOps engineers.',
                'prerequisites' => 'Linux basics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'DEV-301',
                'title' => 'DevOps Practices',
                'description' => 'Learn CI/CD pipelines, containerization, and orchestration.',
                'category' => 'DevOps',
                'level' => 'advanced',
                'learning_outcomes' => '• Docker & Kubernetes\n• Jenkins/GitLab CI\n• Infrastructure as Code',
                'target_audience' => 'Developers and Ops professionals.',
                'prerequisites' => 'Software development lifecycle knowledge.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],

            // Additional courses for comprehensive testing
            [
                'code' => 'ML-201',
                'title' => 'Machine Learning Fundamentals',
                'description' => 'Introduction to machine learning algorithms and applications.',
                'category' => 'Data Science',
                'level' => 'intermediate',
                'learning_outcomes' => '• Supervised Learning\n• Unsupervised Learning\n• Model Evaluation',
                'target_audience' => 'Data analysts and developers.',
                'prerequisites' => 'Python and basic statistics.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'MOB-201',
                'title' => 'Mobile App Development',
                'description' => 'Build native and cross-platform mobile applications.',
                'category' => 'Programming',
                'level' => 'intermediate',
                'learning_outcomes' => '• React Native\n• Flutter\n• Mobile UI/UX',
                'target_audience' => 'Web developers transitioning to mobile.',
                'prerequisites' => 'JavaScript or Dart knowledge.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'DB-101',
                'title' => 'Database Design and SQL',
                'description' => 'Master database design principles and SQL queries.',
                'category' => 'Database',
                'level' => 'beginner',
                'learning_outcomes' => '• Database Normalization\n• SQL Queries\n• Performance Optimization',
                'target_audience' => 'Developers and data analysts.',
                'prerequisites' => 'Basic programming knowledge.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'NET-201',
                'title' => 'Network Administration',
                'description' => 'Learn to configure and manage enterprise networks.',
                'category' => 'Networking',
                'level' => 'intermediate',
                'learning_outcomes' => '• Network Protocols\n• Router Configuration\n• Network Security',
                'target_audience' => 'IT administrators.',
                'prerequisites' => 'Basic networking concepts.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'UX-101',
                'title' => 'UI/UX Design Principles',
                'description' => 'Create user-centered designs for web and mobile.',
                'category' => 'Design',
                'level' => 'beginner',
                'learning_outcomes' => '• User Research\n• Wireframing\n• Prototyping',
                'target_audience' => 'Designers and product managers.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'BLK-201',
                'title' => 'Blockchain Technology',
                'description' => 'Understand blockchain fundamentals and smart contracts.',
                'category' => 'Emerging Tech',
                'level' => 'intermediate',
                'learning_outcomes' => '• Blockchain Basics\n• Smart Contracts\n• DApps Development',
                'target_audience' => 'Developers interested in blockchain.',
                'prerequisites' => 'Programming experience.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'PM-101',
                'title' => 'Project Management Essentials',
                'description' => 'Learn agile and traditional project management methodologies.',
                'category' => 'Management',
                'level' => 'beginner',
                'learning_outcomes' => '• Agile/Scrum\n• Project Planning\n• Risk Management',
                'target_audience' => 'Team leads and managers.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'AI-301',
                'title' => 'Artificial Intelligence Applications',
                'description' => 'Explore practical AI applications and implementation.',
                'category' => 'AI',
                'level' => 'advanced',
                'learning_outcomes' => '• Neural Networks\n• Natural Language Processing\n• Computer Vision',
                'target_audience' => 'ML practitioners.',
                'prerequisites' => 'Machine learning fundamentals.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'MKT-101',
                'title' => 'Digital Marketing Strategy',
                'description' => 'Master digital marketing channels and analytics.',
                'category' => 'Marketing',
                'level' => 'beginner',
                'learning_outcomes' => '• SEO/SEM\n• Social Media Marketing\n• Analytics',
                'target_audience' => 'Marketing professionals.',
                'prerequisites' => 'None.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
            [
                'code' => 'API-201',
                'title' => 'API Development with REST',
                'description' => 'Build scalable RESTful APIs for modern applications.',
                'category' => 'Programming',
                'level' => 'intermediate',
                'learning_outcomes' => '• REST Principles\n• API Security\n• Documentation',
                'target_audience' => 'Backend developers.',
                'prerequisites' => 'Backend programming experience.',
                'status' => 'published',
                'owner_id' => $trainer->id,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}

