<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About LASF',
                'content' => 'Lebanese Aero Sport Federation official page.',
            ],
            [
                'title' => 'Safety Rules',
                'content' => 'All pilots must respect aviation safety regulations.',
            ],
        ];

        foreach ($pages as $page) {
            Page::create([
                'title' => $page['title'],
                'slug' => Str::slug($page['title']),
                'content' => $page['content'],
      
            ]);
        }
    }
}
