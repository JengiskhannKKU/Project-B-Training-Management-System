<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'Trainer User',
                'email' => 'trainer@example.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Trainee User',
                'email' => 'trainee@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
