<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create unit user
        User::create([
            'name' => 'Unit',
            'email' => 'unit@example.com',
            'password' => Hash::make('password'),
            'role' => 'unit',
        ]);
    }
}