<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default data for the live site. Runs on every deploy (see Railway pre-deploy
 * command), so every seeder here must be safe to run repeatedly.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            ChurchOfficeAccountsSeeder::class,
            DepartmentAccountsSeeder::class,
        ]);
    }
}
