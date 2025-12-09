<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Opinionated defaults for quick local access.
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1, // admin
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Trainer User',
            'email' => 'trainer@example.com',
            'password' => Hash::make('password'),
            'role_id' => 2, // trainer
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role_id' => 3, // student
            'status' => 'active',
        ]);
    }
}
