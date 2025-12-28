<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'user_id' => 1,
                'phone' => '1234567890',
                'date_of_birth' => '1985-05-15',
                'gender' => 'male',
                'organization' => 'Tech Corp',
                'department' => 'IT Administration',
                'bio' => 'Experienced administrator with 10+ years in training management.',
            ],
            [
                'user_id' => 2,
                'phone' => '2345678901',
                'date_of_birth' => '1980-08-22',
                'gender' => 'male',
                'organization' => 'Tech Corp',
                'department' => 'Training',
                'bio' => 'Professional trainer specializing in web development and cloud technologies.',
            ],
            [
                'user_id' => 3,
                'phone' => '3456789012',
                'date_of_birth' => '1988-03-10',
                'gender' => 'female',
                'organization' => 'Tech Corp',
                'department' => 'Training',
                'bio' => 'Expert in data science and machine learning with a passion for teaching.',
            ],
            [
                'user_id' => 4,
                'phone' => '4567890123',
                'date_of_birth' => '1990-11-05',
                'gender' => 'male',
                'organization' => 'Tech Corp',
                'department' => 'Training',
                'bio' => 'Certified trainer in cybersecurity and network administration.',
            ],
            [
                'user_id' => 5,
                'phone' => '5678901234',
                'date_of_birth' => '1995-06-18',
                'gender' => 'female',
                'organization' => 'StartUp Inc',
                'department' => 'Development',
                'bio' => 'Aspiring developer eager to learn new technologies.',
            ],
            [
                'user_id' => 6,
                'phone' => '6789012345',
                'date_of_birth' => '1992-09-25',
                'gender' => 'male',
                'organization' => 'StartUp Inc',
                'department' => 'Development',
                'bio' => 'Full-stack developer looking to expand skills in cloud computing.',
            ],
            [
                'user_id' => 7,
                'phone' => '7890123456',
                'date_of_birth' => '1993-02-14',
                'gender' => 'female',
                'organization' => 'Data Insights',
                'department' => 'Analytics',
                'bio' => 'Data analyst interested in machine learning and AI.',
            ],
            [
                'user_id' => 8,
                'phone' => '8901234567',
                'date_of_birth' => '1991-12-08',
                'gender' => 'male',
                'organization' => 'SecureTech',
                'department' => 'Security',
                'bio' => 'IT professional focused on cybersecurity and compliance.',
            ],
            [
                'user_id' => 9,
                'phone' => '9012345678',
                'date_of_birth' => '1994-07-30',
                'gender' => 'female',
                'organization' => 'Digital Solutions',
                'department' => 'UX Design',
                'bio' => 'UX designer exploring frontend development.',
            ],
            [
                'user_id' => 10,
                'phone' => '0123456789',
                'date_of_birth' => '1996-04-20',
                'gender' => 'male',
                'organization' => 'Cloud Systems',
                'department' => 'DevOps',
                'bio' => 'DevOps engineer passionate about automation and infrastructure.',
            ],
        ];

        foreach ($profiles as $profile) {
            Profile::create($profile);
        }
    }
}
