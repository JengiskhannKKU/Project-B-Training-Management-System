<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthLoginSeeder extends Seeder
{
    /**
     * Seed minimal roles + users for login.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $roles = Role::whereIn('name', ['admin', 'trainer', 'student'])->get()->keyBy('name');

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $roles['admin']->id ?? 1,
                'status' => 'active',
            ],
            [
                'name' => 'Trainer User',
                'email' => 'trainer@example.com',
                'password' => Hash::make('password'),
                'role_id' => $roles['trainer']->id ?? 2,
                'status' => 'active',
            ],
            [
                'name' => 'Student User',
                'email' => 'student@example.com',
                'password' => Hash::make('password'),
                'role_id' => $roles['student']->id ?? 3,
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            Profile::firstOrCreate(['user_id' => $user->id]);
        }
    }
}
