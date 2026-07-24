<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class WatcherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
  public function run(): void
{
    User::firstOrCreate(
        ['email' => 'watcher@lasf.info'],
        [
            'name' => 'LASF Watcher',
            'phone' => '03123456',
            'password' => Hash::make('password'),

            'role' => 'watcher',

            'is_admin' => false,
            'is_active' => true,
            'is_approved' => true,
        ]
    );
}
}
