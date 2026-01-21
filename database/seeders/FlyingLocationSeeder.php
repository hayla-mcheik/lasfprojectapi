<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlyingLocation;
use Illuminate\Support\Str;

class FlyingLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Harissa',
                'description' => 'Popular paragliding location in Lebanon.',
                'region' => 'Mount Lebanon', // ✅ required
                'latitude' => 33.9780,
                'longitude' => 35.6361,
            ],
            [
                'name' => 'Batroun',
                'description' => 'Coastal flying area with scenic views.',
                'region' => 'North Lebanon', // ✅ required
                'latitude' => 34.2553,
                'longitude' => 35.6586,
            ],
        ];

        foreach ($locations as $location) {
            FlyingLocation::create([
                'name' => $location['name'],
                'slug' => Str::slug($location['name']),
                'description' => $location['description'],
                'region' => $location['region'], // ✅ include region
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
            ]);
        }
    }
}
