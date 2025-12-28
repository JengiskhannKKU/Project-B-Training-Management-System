<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'name' => 'Web Development Fundamentals',
                'code' => 'WEB-101',
                'description' => 'Learn the basics of HTML, CSS, and JavaScript. Perfect for beginners starting their web development journey.',
                'category' => 'Web Development',
                'duration_hours' => 40,
                'level' => 'beginner',
                'image_url' => 'https://via.placeholder.com/400x300/4F46E5/FFFFFF?text=Web+Dev',
                'created_by' => 2,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(30),
                'approval_note' => 'Excellent curriculum for beginners.',
                'status' => 'active',
            ],
            [
                'name' => 'Advanced React Development',
                'code' => 'REACT-201',
                'description' => 'Deep dive into React ecosystem including hooks, context, Redux, and modern patterns.',
                'category' => 'Web Development',
                'duration_hours' => 60,
                'level' => 'advanced',
                'image_url' => 'https://via.placeholder.com/400x300/06B6D4/FFFFFF?text=React',
                'created_by' => 2,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(25),
                'approval_note' => 'Great advanced content.',
                'status' => 'active',
            ],
            [
                'name' => 'Data Science with Python',
                'code' => 'DS-101',
                'description' => 'Introduction to data science using Python, NumPy, Pandas, and basic machine learning.',
                'category' => 'Data Science',
                'duration_hours' => 80,
                'level' => 'intermediate',
                'image_url' => 'https://via.placeholder.com/400x300/10B981/FFFFFF?text=Data+Science',
                'created_by' => 3,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(20),
                'approval_note' => 'Comprehensive data science program.',
                'status' => 'active',
            ],
            [
                'name' => 'Cybersecurity Essentials',
                'code' => 'SEC-101',
                'description' => 'Learn the fundamentals of cybersecurity, network security, and ethical hacking.',
                'category' => 'Security',
                'duration_hours' => 50,
                'level' => 'beginner',
                'image_url' => 'https://via.placeholder.com/400x300/EF4444/FFFFFF?text=Security',
                'created_by' => 4,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(15),
                'approval_note' => 'Essential security training.',
                'status' => 'active',
            ],
            [
                'name' => 'Cloud Computing with AWS',
                'code' => 'CLOUD-101',
                'description' => 'Master AWS services including EC2, S3, Lambda, and cloud architecture best practices.',
                'category' => 'Cloud Computing',
                'duration_hours' => 70,
                'level' => 'intermediate',
                'image_url' => 'https://via.placeholder.com/400x300/F59E0B/FFFFFF?text=Cloud+AWS',
                'created_by' => 2,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(10),
                'approval_note' => 'Solid cloud computing curriculum.',
                'status' => 'active',
            ],
            [
                'name' => 'Machine Learning Fundamentals',
                'code' => 'ML-101',
                'description' => 'Introduction to machine learning algorithms, neural networks, and practical applications.',
                'category' => 'Data Science',
                'duration_hours' => 90,
                'level' => 'advanced',
                'image_url' => 'https://via.placeholder.com/400x300/8B5CF6/FFFFFF?text=Machine+Learning',
                'created_by' => 3,
                'approval_status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => null,
                'status' => 'inactive',
            ],
            [
                'name' => 'Mobile App Development',
                'code' => 'MOB-101',
                'description' => 'Build native mobile apps for iOS and Android using React Native.',
                'category' => 'Mobile Development',
                'duration_hours' => 60,
                'level' => 'intermediate',
                'image_url' => 'https://via.placeholder.com/400x300/EC4899/FFFFFF?text=Mobile+Dev',
                'created_by' => 2,
                'approval_status' => 'rejected',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(5),
                'approval_note' => 'Needs more practical examples. Please revise and resubmit.',
                'status' => 'inactive',
            ],
            [
                'name' => 'DevOps and CI/CD',
                'code' => 'DEVOPS-101',
                'description' => 'Learn DevOps practices, Docker, Kubernetes, Jenkins, and continuous integration/deployment.',
                'category' => 'DevOps',
                'duration_hours' => 55,
                'level' => 'intermediate',
                'image_url' => 'https://via.placeholder.com/400x300/14B8A6/FFFFFF?text=DevOps',
                'created_by' => 4,
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(8),
                'approval_note' => 'Excellent DevOps curriculum.',
                'status' => 'active',
            ],
        ];

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
