<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@farmacia.com'],
            [
                'role_id' => 1,
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'active' => 1,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cajero@farmacia.com'],
            [
                'role_id' => 2,
                'name' => 'Cajero Principal',
                'password' => Hash::make('cajero123'),
                'active' => 1,
            ]
        );
    }
}
