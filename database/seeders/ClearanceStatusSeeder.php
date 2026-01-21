<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClearanceStatus;

class ClearanceStatusSeeder extends Seeder
{
    public function run(): void
    {
        ClearanceStatus::create([
            'flying_location_id' => 1, // Or assign an existing flying location id
            'status' => 'pending',
            'reason' => null,
            'updated_by' => null,
        ]);

        ClearanceStatus::create([
            'flying_location_id' => 1,
            'status' => 'approved',
            'reason' => 'Automatically approved',
            'updated_by' => null,
        ]);

        ClearanceStatus::create([
            'flying_location_id' => 1,
            'status' => 'rejected',
            'reason' => 'Example rejection reason',
            'updated_by' => null,
        ]);
    }
}
