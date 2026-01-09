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
            ],
            [
                'name' => 'Trainer User',
                'email' => 'trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
            ],
            [
                'name' => 'Trainee User',
                'email' => 'trainee@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
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
            ],
            [
                'name' => 'System Manager',
                'email' => 'manager@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['admin']->id ?? 1,
                'email_verified_at' => $now,
                'status' => 'active',
            ],

            // Trainers
            [
                'name' => 'John Trainer', // Duplicate name of 'Trainer User'? No, Trainer User is 'Trainer User'.
                // Actually in original seeder 'trainer@example.com' was 'Trainer User'.
                // Creating 'John Trainer' with 'trainer@example.com' would conflict if I didn't separate them.
                // Wait, in my previous edit I put 'John Trainer' with 'trainer@example.com'.
                // The 'Trainer User' had email 'trainer@example.com'.
                // So "John Trainer" should probably have a different email or I should rename "Trainer User" to "John Trainer".
                // But the USER said "Keep trainer@example.com". 
                // I will create NEW trainers for testing to avoid touching 'trainer@example.com'.
                'email' => 'john.trainer@example.com', // Changed email to avoid conflict
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
            ],
            [
                'name' => 'Sarah Tech',
                'email' => 'sarah.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
            ],
            [
                'name' => 'Mike Security',
                'email' => 'mike.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
            ],
            [
                'name' => 'David Cloud',
                'email' => 'david.trainer@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
            ],

            // Students
            [
                'name' => 'Alice Wonder',
                'email' => 'alice@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
            ],
            [
                'name' => 'Bob Builder',
                'email' => 'bob@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
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

