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
                'name' => 'John Trainer',
                'email' => 'trainer@example.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Sarah Trainer',
                'email' => 'trainer2@example.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Mike Trainer',
                'email' => 'trainer3@example.com',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => 'inactive',
            ],
            [
                'name' => 'Alice Student',
                'email' => 'student@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Carol Williams',
                'email' => 'carol@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'David Brown',
                'email' => 'david@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Emma Davis',
                'email' => 'emma@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Frank Miller',
                'email' => 'frank@example.com',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 'inactive',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
