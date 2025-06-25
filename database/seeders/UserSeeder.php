<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'unit_id' => null,
            'role' => 'admin'
        ]);

        // Users untuk setiap unit
        User::create([
            'name' => 'Petugas Cikarang',
            'email' => 'cikarang@example.com',
            'password' => Hash::make('password'),
            'unit_id' => 1,
            'role' => 'unit',
        ]);

        User::create([
            'name' => 'Petugas Karawang',
            'email' => 'karawang@example.com',
            'password' => Hash::make('password'),
            'unit_id' => 2,
            'role' => 'unit',
        ]);

        User::create([
            'name' => 'Petugas Kemayoran',
            'email' => 'kemayoran@example.com',
            'password' => Hash::make('password'),
            'unit_id' => 3,
            'role' => 'unit',
        ]);
    }
}