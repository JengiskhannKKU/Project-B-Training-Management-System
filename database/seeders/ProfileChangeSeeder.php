<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProfileChange;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProfileChangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $admin = User::where('email', 'admin@example.com')->first();

        // Get trainee user for sample changes
        $trainee = User::where('email', 'trainee@example.com')->first();

        if (!$admin || !$trainee) {
            $this->command->warn('Required users not found for profile changes seeder.');
            return;
        }

        $changes = [
            // User updating their own profile
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'phone',
                'old_value' => null,
                'new_value' => '+66 81 234 5678',
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'address',
                'old_value' => null,
                'new_value' => '123 Sukhumvit Road, Bangkok',
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'education_level',
                'old_value' => null,
                'new_value' => 'Bachelor\'s Degree',
                'created_at' => Carbon::now()->subDays(3),
            ],

            // Admin updating user profile
            [
                'user_id' => $trainee->id,
                'changed_by' => $admin->id,
                'field_name' => 'emergency_contact_name',
                'old_value' => null,
                'new_value' => 'John Doe (Father)',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'user_id' => $trainee->id,
                'changed_by' => $admin->id,
                'field_name' => 'emergency_contact_phone',
                'old_value' => null,
                'new_value' => '+66 81 987 6543',
                'created_at' => Carbon::now()->subDays(2),
            ],

            // User changing phone number (updated)
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'phone',
                'old_value' => '+66 81 234 5678',
                'new_value' => '+66 82 345 6789',
                'created_at' => Carbon::now()->subDays(1),
            ],

            // Admin updating user status
            [
                'user_id' => $trainee->id,
                'changed_by' => $admin->id,
                'field_name' => 'status',
                'old_value' => 'inactive',
                'new_value' => 'active',
                'created_at' => Carbon::now()->subHours(6),
            ],

            // User updating locale
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'locale',
                'old_value' => 'en',
                'new_value' => 'th',
                'created_at' => Carbon::now()->subHours(3),
            ],

            // User updating profile picture path
            [
                'user_id' => $trainee->id,
                'changed_by' => $trainee->id,
                'field_name' => 'profile_picture_path',
                'old_value' => null,
                'new_value' => 'profile_pictures/user_' . $trainee->id . '.jpg',
                'created_at' => Carbon::now()->subHour(),
            ],
        ];

        // Insert changes
        foreach ($changes as $change) {
            ProfileChange::create($change);
        }

        $this->command->info('Profile changes seeded: ' . count($changes));
    }
}
