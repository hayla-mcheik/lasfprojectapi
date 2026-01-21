<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SportSeeder::class,
            FlyingLocationSeeder::class,
            // ClearanceStatusSeeder::class,
            PageSeeder::class,
            NewsCategorySeeder::class,
            // NewsSeeder::class,
        ]);
    }
}
