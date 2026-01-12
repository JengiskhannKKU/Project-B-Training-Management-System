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
            [
                'name' => 'Student User',
                'email' => 'student@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'th',
                'last_login_at' => $now->subMinutes(15),
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

            // Create comprehensive profile based on role
            $this->createProfileForUser($user);
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

            // Trainees (30 more for comprehensive testing)
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

        // Generate 30 additional trainees
        $firstNames = ['Emma', 'Liam', 'Olivia', 'Noah', 'Ava', 'Ethan', 'Sophia', 'Mason', 'Isabella', 'William',
                       'Mia', 'James', 'Charlotte', 'Benjamin', 'Amelia', 'Lucas', 'Harper', 'Henry', 'Evelyn', 'Alexander',
                       'Abigail', 'Michael', 'Emily', 'Daniel', 'Elizabeth', 'Matthew', 'Sofia', 'Jackson', 'Avery', 'Sebastian'];

        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
                      'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
                      'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson'];

        for ($i = 0; $i < 30; $i++) {
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[$i % count($lastNames)];
            $testUsers[] = [
                'name' => $firstName . ' ' . $lastName . ' ' . ($i + 1),
                'email' => strtolower($firstName) . '.' . strtolower($lastName) . ($i + 1) . '@example.com',
                'password' => $commonPassword,
                'role_id' => $roles['trainee']->id ?? 3,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => $i % 2 ? 'en' : 'th',
                'last_login_at' => $now->subHours(rand(1, 72)),
            ];
        }

        // Add 5 more trainers
        $trainerNames = [
            ['Emily Johnson', 'emily.johnson@example.com'],
            ['Robert Chen', 'robert.chen@example.com'],
            ['Lisa Anderson', 'lisa.anderson@example.com'],
            ['Kevin Park', 'kevin.park@example.com'],
            ['Maria Garcia', 'maria.garcia@example.com'],
        ];

        foreach ($trainerNames as $trainer) {
            $testUsers[] = [
                'name' => $trainer[0],
                'email' => $trainer[1],
                'password' => $commonPassword,
                'role_id' => $roles['trainer']->id ?? 2,
                'email_verified_at' => $now,
                'status' => 'active',
                'locale' => 'en',
                'last_login_at' => $now->subHours(rand(1, 48)),
            ];
        }

        // Add 2 more admins
        $testUsers[] = [
            'name' => 'Operations Manager',
            'email' => 'operations@example.com',
            'password' => $commonPassword,
            'role_id' => $roles['admin']->id ?? 1,
            'email_verified_at' => $now,
            'status' => 'active',
            'locale' => 'en',
            'last_login_at' => $now->subHours(3),
        ];

        $testUsers[] = [
            'name' => 'Training Coordinator',
            'email' => 'coordinator@example.com',
            'password' => $commonPassword,
            'role_id' => $roles['admin']->id ?? 1,
            'email_verified_at' => $now,
            'status' => 'active',
            'locale' => 'th',
            'last_login_at' => $now->subHours(6),
        ];

        foreach ($testUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            $this->createProfileForUser($user);
        }
    }

    /**
     * Create comprehensive profile data based on user role
     */
    private function createProfileForUser(User $user): void
    {
        $role = $user->role->name ?? 'trainee';

        $baseProfile = [
            'user_id' => $user->id,
            'phone' => $this->generateThaiPhone(),
            'date_of_birth' => now()->subYears(rand(22, 45))->format('Y-m-d'),
            'gender' => rand(0, 1) ? 'male' : 'female',
            'bio' => $this->generateBio($role),
        ];

        // Add role-specific data
        if ($role === 'trainee') {
            $category = rand(0, 1) ? 'student' : 'personnel';

            if ($category === 'student') {
                $profile = array_merge($baseProfile, $this->generateStudentProfile());
            } else {
                $profile = array_merge($baseProfile, $this->generatePersonnelProfile());
            }
        } elseif ($role === 'trainer') {
            $profile = array_merge($baseProfile, $this->generateTrainerProfile());
        } else {
            $profile = array_merge($baseProfile, $this->generateAdminProfile());
        }

        Profile::updateOrCreate(
            ['user_id' => $user->id],
            $profile
        );
    }

    private function generateThaiPhone(): string
    {
        $prefixes = ['06', '08', '09'];
        return $prefixes[array_rand($prefixes)] . rand(10000000, 99999999);
    }

    private function generateBio(string $role): string
    {
        $bios = [
            'admin' => [
                'Experienced administrator with passion for educational excellence.',
                'Dedicated to managing and improving training programs.',
                'Committed to supporting both trainers and trainees.',
            ],
            'trainer' => [
                'Passionate educator with years of industry experience.',
                'Dedicated to helping students achieve their learning goals.',
                'Experienced professional committed to knowledge sharing.',
                'Industry expert focused on practical skill development.',
            ],
            'trainee' => [
                'Eager learner seeking to expand knowledge and skills.',
                'Motivated individual pursuing professional development.',
                'Committed to continuous learning and growth.',
                'Enthusiastic about acquiring new competencies.',
            ],
        ];

        return $bios[$role][array_rand($bios[$role])];
    }

    private function generateStudentProfile(): array
    {
        $faculties = ['Engineering', 'Science', 'Arts', 'Business Administration', 'Medicine', 'Law'];
        $majors = ['Computer Science', 'Information Technology', 'Software Engineering', 'Data Science', 'Business Analytics', 'Digital Marketing'];
        $prefixes = ['Mr.', 'Ms.', 'Miss'];
        $degreeLevels = ['bachelor', 'master', 'doctoral'];

        return [
            'category' => 'student',
            'prefix' => $prefixes[array_rand($prefixes)],
            'faculty' => $faculties[array_rand($faculties)],
            'major' => $majors[array_rand($majors)],
            'student_id' => 'STU' . rand(60000000, 66999999),
            'degree_level' => $degreeLevels[array_rand($degreeLevels)],
            'year_of_study' => rand(1, 4),
        ];
    }

    private function generatePersonnelProfile(): array
    {
        $organizations = ['Kasetsart University', 'Chulalongkorn University', 'Mahidol University', 'NSTDA', 'Government Agency'];
        $departments = ['IT Department', 'Human Resources', 'Research & Development', 'Administration', 'Academic Affairs'];
        $positions = ['Officer', 'Coordinator', 'Specialist', 'Manager', 'Senior Officer'];
        $prefixes = ['Mr.', 'Ms.', 'Mrs.', 'Dr.'];
        $employmentStatuses = ['permanent', 'contract', 'temporary'];
        $personnelTypes = ['academic', 'support', 'administrative'];

        return [
            'category' => 'personnel',
            'prefix' => $prefixes[array_rand($prefixes)],
            'personnel_id' => 'PER' . rand(10000, 99999),
            'organization' => $organizations[array_rand($organizations)],
            'department' => $departments[array_rand($departments)],
            'job_position' => $positions[array_rand($positions)],
            'employment_status' => $employmentStatuses[array_rand($employmentStatuses)],
            'personnel_type' => $personnelTypes[array_rand($personnelTypes)],
        ];
    }

    private function generateTrainerProfile(): array
    {
        $organizations = ['Tech Company', 'University', 'Training Institute', 'Consulting Firm'];
        $departments = ['Training Department', 'Academic Division', 'Professional Development'];
        $prefixes = ['Mr.', 'Ms.', 'Dr.', 'Assoc. Prof.'];

        return [
            'category' => 'personnel',
            'prefix' => $prefixes[array_rand($prefixes)],
            'personnel_id' => 'TRA' . rand(10000, 99999),
            'organization' => $organizations[array_rand($organizations)],
            'department' => $departments[array_rand($departments)],
            'job_position' => 'Senior Trainer',
            'employment_status' => 'permanent',
            'personnel_type' => 'academic',
        ];
    }

    private function generateAdminProfile(): array
    {
        $prefixes = ['Mr.', 'Ms.', 'Dr.'];

        return [
            'category' => 'personnel',
            'prefix' => $prefixes[array_rand($prefixes)],
            'personnel_id' => 'ADM' . rand(10000, 99999),
            'organization' => 'Training Management System',
            'department' => 'Administration',
            'job_position' => 'System Administrator',
            'employment_status' => 'permanent',
            'personnel_type' => 'administrative',
        ];
    }
}

