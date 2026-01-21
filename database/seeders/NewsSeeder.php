<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $category = NewsCategory::first();

        News::create([
            'title' => 'LASF New Season Opening',
            'slug' => Str::slug('LASF New Season Opening'),
            'content' => 'The new flying season officially starts this month.',
            'news_category_id' => $category?->id,
            'is_published' => true,
        ]);
    }
}
