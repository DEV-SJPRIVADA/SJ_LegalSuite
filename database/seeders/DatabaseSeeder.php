<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            FaultsCatalogSeeder::class,
            CitationFaultTemplatesSeeder::class,
            DiligenceActaQuestionsSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
