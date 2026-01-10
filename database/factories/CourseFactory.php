<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = $this->faker->numberBetween(5, 10);
        $max = $this->faker->numberBetween($min + 5, 50);

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'category' => $this->faker->randomElement(['Programming', 'Design', 'Marketing', 'Business', 'Data Science']),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'learning_outcomes' => $this->faker->text(200),
            'target_audience' => $this->faker->text(100),
            'prerequisites' => $this->faker->text(100),
            'additional_info' => $this->faker->optional()->text(100),
            'thumbnail_path' => null,
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'min_participants' => $min,
            'max_participants' => $max,
            'owner_id' => User::factory(),
        ];
    }
}
