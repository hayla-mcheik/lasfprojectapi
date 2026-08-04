<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BeirutAirportSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            [
                'email' => 'weather@beirutairport.lb'
            ],
            [
                'name' => 'Beirut Airport',
                'phone' => '01000000',
                'password' => Hash::make('Airport123'),

                'role' => 'beirut_airport',

                'is_admin' => false,
                'is_active' => true,
                'is_approved' => true,
            ]
        );
    }
}