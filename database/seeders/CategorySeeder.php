<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'icon' => 'Code',
                'color' => 'blue',
            ],
            [
                'name' => 'Data Science',
                'icon' => 'Database',
                'color' => 'purple',
            ],
            [
                'name' => 'Design',
                'icon' => 'Palette',
                'color' => 'pink',
            ],
            [
                'name' => 'Business',
                'icon' => 'Briefcase',
                'color' => 'indigo',
            ],
            [
                'name' => 'Marketing',
                'icon' => 'TrendingUp',
                'color' => 'orange',
            ],
            [
                'name' => 'Programming',
                'icon' => 'Laptop',
                'color' => 'teal',
            ],
            [
                'name' => 'Photography',
                'icon' => 'Camera',
                'color' => 'yellow',
            ],
            [
                'name' => 'Education',
                'icon' => 'BookOpen',
                'color' => 'green',
            ],
            [
                'name' => 'Innovation',
                'icon' => 'Lightbulb',
                'color' => 'cyan',
            ],
            [
                'name' => 'General',
                'icon' => 'Tag',
                'color' => 'red',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('Categories seeded: ' . count($categories));
    }
}
