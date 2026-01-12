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
                'name' => 'IT',
                'icon_name' => 'code',
                'color' => 'blue',
            ],
            [
                'name' => 'Management',
                'icon_name' => 'briefcase',
                'color' => 'purple',
            ],
            [
                'name' => 'Design',
                'icon_name' => 'palette',
                'color' => 'pink',
            ],
            [
                'name' => 'Marketing',
                'icon_name' => 'trending-up',
                'color' => 'amber',
            ],
            [
                'name' => 'Business',
                'icon_name' => 'trending-up',
                'color' => 'green',
            ],
            [
                'name' => 'Professional Development',
                'icon_name' => 'users',
                'color' => 'teal',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
