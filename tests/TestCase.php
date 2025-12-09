<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the database before each test run so role constraints pass.
     */
    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;
}
