<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeviceGroup;

class DeviceGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Membuat grup perangkat...');

        $groups = [
            [
                'name' => 'Zona Utara',
                'description' => 'Perangkat di zona utara kota',
                'type' => 'zone',
                'color' => '#3b82f6', // biru
            ],
            [
                'name' => 'Zona Selatan',
                'description' => 'Perangkat di zona selatan kota',
                'type' => 'zone',
                'color' => '#ef4444', // merah
            ],
            [
                'name' => 'Zona Timur',
                'description' => 'Perangkat di zona timur kota',
                'type' => 'zone',
                'color' => '#f59e0b', // kuning
            ],
            [
                'name' => 'Zona Barat',
                'description' => 'Perangkat di zona barat kota',
                'type' => 'zone',
                'color' => '#10b981', // hijau
            ],
            [
                'name' => 'Flowmeter',
                'description' => 'Semua perangkat tipe flowmeter',
                'type' => 'device_type',
                'color' => '#6366f1', // ungu
            ],
            [
                'name' => 'Pressure Sensor',
                'description' => 'Semua perangkat sensor tekanan',
                'type' => 'device_type',
                'color' => '#f472b6', // pink
            ],
        ];

        foreach ($groups as $groupData) {
            DeviceGroup::create($groupData);
        }

        $this->command?->info('Grup perangkat berhasil dibuat!');
    }
}