<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user for approvals
        $admin = User::where('email', 'admin@example.com')->first();

        // Get all users
        $users = User::all();

        $categories = [];

        foreach ($users as $user) {
            $categoryData = $this->getUserCategoryData($user->email, $admin?->id);
            if ($categoryData) {
                $categories[] = array_merge($categoryData, ['user_id' => $user->id]);
            }
        }

        // Insert categories
        foreach ($categories as $category) {
            UserCategory::updateOrCreate(
                ['user_id' => $category['user_id']],
                $category
            );
        }

        $this->command->info('User categories seeded: ' . count($categories));
    }

    /**
     * Get category data based on user email
     */
    private function getUserCategoryData($email, $adminId)
    {
        $now = now();

        // Define categories for each user
        $categoryMap = [
            // Admin users - personnel category
            'admin@example.com' => [
                'category' => 'personnel',
                'department' => 'IT Administration',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'superadmin@example.com' => [
                'category' => 'personnel',
                'department' => 'Executive Office',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'manager@example.com' => [
                'category' => 'personnel',
                'department' => 'Training Management',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],

            // Trainer users - personnel category
            'trainer@example.com' => [
                'category' => 'personnel',
                'department' => 'Technical Training',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'john.trainer@example.com' => [
                'category' => 'personnel',
                'department' => 'Software Development',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'sarah.trainer@example.com' => [
                'category' => 'personnel',
                'department' => 'Data Science Department',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'mike.trainer@example.com' => [
                'category' => 'personnel',
                'department' => 'Security Team',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'david.trainer@example.com' => [
                'category' => 'personnel',
                'department' => 'Cloud Infrastructure',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],

            // Trainee users - mix of student and personnel
            'trainee@example.com' => [
                'category' => 'student',
                'school_name' => 'Example University',
                'department' => 'Faculty of Science',
                'organization' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'alice@example.com' => [
                'category' => 'student',
                'school_name' => 'Example University',
                'department' => 'Faculty of Engineering',
                'organization' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
            'bob@example.com' => [
                'category' => 'personnel',
                'department' => 'Human Resources',
                'organization' => null,
                'school_name' => null,
                'other_description' => null,
                'approved_at' => $now,
                'approved_by' => $adminId,
            ],
        ];

        return $categoryMap[$email] ?? [
            'category' => 'other',
            'school_name' => null,
            'department' => null,
            'organization' => null,
            'other_description' => 'General user without specific category',
            'approved_at' => null,
            'approved_by' => null,
        ];
    }
}
