<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+2 months');

        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(4),
            'start_at' => $startDate,
            'end_at' => $endDate,
            'min_participants' => fake()->numberBetween(5, 10),
            'capacity' => fake()->numberBetween(10, 50),
            'trainer_id' => User::factory(),
            'location' => fake()->randomElement(['Room A101', 'Room B202', 'Room C303', 'Online', 'Hybrid']),
            'mode' => fake()->randomElement(['onsite', 'online', 'hybrid']),
            'online_link' => fake()->url(),
            'status' => fake()->randomElement(['scheduled', 'ongoing', 'completed', 'cancelled']),
            'registration_start' => fake()->dateTimeBetween('-1 month', 'now'),
            'registration_end' => fake()->dateTimeBetween('now', '+1 week'),
        ];
    }
}
