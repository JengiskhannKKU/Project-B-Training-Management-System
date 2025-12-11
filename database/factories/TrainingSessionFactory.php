<?php

namespace Database\Factories;

use App\Models\Program;
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
            'program_id' => Program::factory(),
            'title' => fake()->sentence(4),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'capacity' => fake()->numberBetween(10, 50),
            'trainer_id' => User::factory(),
            'location' => fake()->randomElement(['Room A101', 'Room B202', 'Room C303', 'Online', 'Hybrid']),
            'status' => fake()->randomElement(['upcoming', 'open', 'closed', 'completed', 'cancelled']),
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => null,
        ];
    }
}
