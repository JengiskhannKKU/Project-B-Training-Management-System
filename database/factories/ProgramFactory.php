<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['Web Development', 'Mobile Development', 'Data Science', 'DevOps', 'Cloud Computing']),
            'duration_hours' => fake()->numberBetween(10, 100),
            'image_url' => fake()->imageUrl(640, 480, 'education', true),
            'status' => fake()->randomElement(['active', 'inactive']),
            'approval_status' => 'pending',
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => null,
        ];
    }
}
