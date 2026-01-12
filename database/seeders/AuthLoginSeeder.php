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

        $roles = Role::whereIn('name', ['admin', 'trainer', 'trainee'])->get()->keyBy('name');

        $commonPassword = Hash::make('password');
        $now = now();


        // Key users to preserve (use firstOrCreate to keep existing passwords/settings)
        $keyUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['admin']->id ?? 1,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(2),
            ],
            [
                'name' => 'Trainer User',
                'email' => 'trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(4),
            ],
            [
                'name' => 'Trainee User',
                'email' => 'trainee@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'th',
                'last_login_at' => $now->subMinutes(30),
            ],
        ];

        foreach ($keyUsers as $userData) {
            // firstOrCreate will NOT update the password if the user exists.
            // But we must ensure the role is correct. 
            // If they want to "Keep" them, maybe they mean "don't touch".
            // So firstOrCreate is safer.
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Ensure profile exists
            Profile::firstOrCreate(['user_id' => $user->id]);
        }

        // Additional Test Users (Safe to update/reset)
        $testUsers = [
            // Admin
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['admin']->id ?? 1,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subDays(1),
            ],
            [
                'name' => 'System Manager',
                'email' => 'manager@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['admin']->id ?? 1,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(12),
            ],

            // Trainers
            [
                'name' => 'John Trainer',
                'email' => 'john.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(6),
            ],
            [
                'name' => 'Sarah Tech',
                'email' => 'sarah.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(8),
            ],
            [
                'name' => 'Mike Security',
                'email' => 'mike.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subDays(2),
            ],
            [
                'name' => 'David Cloud',
                'email' => 'david.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subDays(3),
            ],

            // Students
            [
                'name' => 'Alice Wonder',
                'email' => 'alice@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'th',
                'last_login_at' => $now->subHours(1),
            ],
            [
                'name' => 'Bob Builder',
                'email' => 'bob@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(5),
            ],
        ];

        foreach ($testUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            Profile::firstOrCreate(['user_id' => $user->id]);
        }
    }
}

