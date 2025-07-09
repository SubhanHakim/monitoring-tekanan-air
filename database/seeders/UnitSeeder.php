<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run()
    {
        Unit::create([
            'name' => 'Unit Apartemen Kemayoran',
            'location' => 'Kemayoran, Jakarta',
            'description' => 'Unit monitoring tekanan air untuk kompleks apartemen di Kemayoran',
            'status' => 'active',
        ]);

        Unit::create([
            'name' => 'Unit Apartemen Menteng',
            'location' => 'Menteng, Jakarta Pusat',
            'description' => 'Unit monitoring tekanan air untuk kompleks apartemen di Menteng',
            'status' => 'active',
        ]);

        Unit::create([
            'name' => 'Unit Apartemen Kelapa Gading',
            'location' => 'Kelapa Gading, Jakarta Utara',
            'description' => 'Unit monitoring tekanan air untuk kompleks apartemen di Kelapa Gading',
            'status' => 'active',
        ]);

        Unit::create([
            'name' => 'Unit Apartemen Pondok Indah',
            'location' => 'Pondok Indah, Jakarta Selatan',
            'description' => 'Unit monitoring tekanan air untuk kompleks apartemen di Pondok Indah',
            'status' => 'inactive',
        ]);
    }
}