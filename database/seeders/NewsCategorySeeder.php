<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsCategory;
use Illuminate\Support\Str;

class NewsCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['weather', 'notice', 'restriction'];

        foreach ($categories as $category) {
            NewsCategory::create([
                'name' => $category,

            ]);
        }
    }
}
