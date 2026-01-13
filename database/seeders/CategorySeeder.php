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
                'name' => 'Programming',
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
