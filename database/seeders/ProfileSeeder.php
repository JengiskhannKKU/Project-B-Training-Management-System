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
                'organization' => 'Training Management',
                'department' => 'IT Administration',
                'bio' => 'Experienced administrator with 10+ years in training management.',
            ],
            [
                'user_id' => 2,
                'phone' => '2345678901',
                'date_of_birth' => '1980-08-22',
                'gender' => 'male',
                'organization' => 'Training Management',
                'department' => 'Training',
                'bio' => 'Professional trainer specializing in web development.',
            ],
            [
                'user_id' => 3,
                'phone' => '3456789012',
                'date_of_birth' => '1988-03-10',
                'gender' => 'female',
                'organization' => 'Training Management',
                'department' => 'Training',
                'bio' => 'Expert in data science with a passion for teaching.',
            ],
        ];

        foreach ($profiles as $profile) {
            Profile::create($profile);
        }
    }
}
