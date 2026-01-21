<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sport;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            'Paragliding',
            'Hang Gliding',
            'Paramotor',
            'Skydiving',
        ];

        foreach ($sports as $sport) {
            Sport::create([
                'name' => $sport,  // Only the actual column in your DB
            ]);
        }
    }
}
