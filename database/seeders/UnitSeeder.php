<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Unit Perumahan Cikarang',
                'location' => 'Cikarang, Bekasi',
                'description' => 'Unit monitoring tekanan air untuk kawasan perumahan di Cikarang',
                'status' => 'active',
            ],
            [
                'name' => 'Unit Industri Karawang',
                'location' => 'Karawang, Jawa Barat',
                'description' => 'Unit monitoring tekanan air untuk kawasan industri di Karawang',
                'status' => 'active',
            ],
            [
                'name' => 'Unit Apartemen Kemayoran',
                'location' => 'Kemayoran, Jakarta',
                'description' => 'Unit monitoring tekanan air untuk kompleks apartemen di Kemayoran',
                'status' => 'active',
            ],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }
    }
}