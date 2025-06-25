<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            // Unit Cikarang (ID: 1)
            [
                'name' => 'Sensor Reservoir Utama',
                'location' => 'Reservoir Utama Cikarang',
                'unit_id' => 1,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 1, // Zona Utara
            ],
            [
                'name' => 'Sensor Distribusi Blok A',
                'location' => 'Junction Box Blok A Cikarang',
                'unit_id' => 1,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 2, // Zona Selatan
            ],
            
            // Unit Karawang (ID: 2)
            [
                'name' => 'Sensor Kawasan Industri 1',
                'location' => 'Pipa Utama Kawasan Industri Karawang',
                'unit_id' => 2,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 3, // Zona Timur
            ],
            [
                'name' => 'Sensor Kawasan Industri 2',
                'location' => 'Junction Box Kawasan Industri Karawang',
                'unit_id' => 2,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 4, // Zona Barat
            ],
            
            // Unit Kemayoran (ID: 3)
            [
                'name' => 'Sensor Tower A',
                'location' => 'Basement Tower A Kemayoran',
                'unit_id' => 3,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 6, // Pressure Sensor
            ],
            [
                'name' => 'Sensor Tower B',
                'location' => 'Basement Tower B Kemayoran',
                'unit_id' => 3,
                'api_key' => Str::random(32),
                'status' => 'active',
                'device_type' => 'pressure_sensor',
                'device_group_id' => 6, // Pressure Sensor
            ],
        ];

        foreach ($devices as $deviceData) {
            Device::create($deviceData);
        }
    }
}