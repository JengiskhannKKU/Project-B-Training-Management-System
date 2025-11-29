<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert(
            [
                ['id' => 1, 'name' => 'admin',   'label' => 'Administrator'],
                ['id' => 2, 'name' => 'trainer', 'label' => 'Trainer'],
                ['id' => 3, 'name' => 'student', 'label' => 'Student'],
            ],
            ['id'],
            ['name', 'label']
        );
    }
}
