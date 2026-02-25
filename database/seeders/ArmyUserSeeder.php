<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ArmyUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'army@lasf.info'], // Checks if email exists to avoid duplicates
            [
                'name' => 'Lebanese Army Official',
                'password' => Hash::make('Army@2026'), // Change this to a secure password
                'phone' => '+96101234567',
                'is_admin' => false,      // Important: false so they don't get Super Admin access
                'is_active' => true,
                'role' => 'army',         // Matches the middleware 'army_access' check
            ]
        );

        $this->command->info('Lebanese Army user created successfully.');
    }
}