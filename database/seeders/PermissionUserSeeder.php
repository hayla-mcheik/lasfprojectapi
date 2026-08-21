<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'permission@lasf.info',
            ],
            [
                'name' => 'Permission Official',
                'password' => Hash::make('Permission@2026'),
                'phone' => '+96103000000',

                'is_admin' => false,
                'is_active' => true,
                'is_approved' => true,

                'role' => 'permission',
            ]
        );

        $this->command->info(
            'Permission user created successfully.'
        );
    }
}