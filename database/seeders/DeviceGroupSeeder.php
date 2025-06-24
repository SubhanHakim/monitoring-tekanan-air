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
        $this->command->info('Membuat grup perangkat...');

        // Buat grup perangkat berdasarkan zona
        $groups = [
            [
                'name' => 'Zona Utara',
                'description' => 'Perangkat di zona utara kota',
                'type' => 'zone', // Tambahkan type
            ],
            [
                'name' => 'Zona Selatan',
                'description' => 'Perangkat di zona selatan kota',
                'type' => 'zone', // Tambahkan type
            ],
            [
                'name' => 'Zona Timur',
                'description' => 'Perangkat di zona timur kota',
                'type' => 'zone', // Tambahkan type
            ],
            [
                'name' => 'Zona Barat',
                'description' => 'Perangkat di zona barat kota',
                'type' => 'zone', // Tambahkan type
            ],
            [
                'name' => 'Flowmeter',
                'description' => 'Semua perangkat tipe flowmeter',
                'type' => 'device_type', // Tambahkan type
            ],
            [
                'name' => 'Pressure Sensor',
                'description' => 'Semua perangkat sensor tekanan',
                'type' => 'device_type', // Tambahkan type
            ],
        ];

        foreach ($groups as $groupData) {
            DeviceGroup::create($groupData);
        }

        $this->command->info('Grup perangkat berhasil dibuat!');
    }
}