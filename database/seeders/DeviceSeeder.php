<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceGroup;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil ID grup yang tersedia
        $groupIds = DeviceGroup::pluck('id')->toArray();
        
        // Data perangkat
        $devices = [
            [
                'name' => 'Sensor Tekanan A1',
                'device_type' => 'pressure_sensor',
                'location' => 'Jl. Sukapura Blok A1',
                'status' => 'active',
                'device_group_id' => $groupIds[0] ?? null, // Group pertama
                'configuration' => [
                    'min_pressure' => '0.5',
                    'max_pressure' => '8.0',
                    'unit' => 'bar'
                ],
                'last_active_at' => now(),
            ],
            // ...perangkat lainnya dengan grup yang berbeda
        ];

        // Buat perangkat dengan grup acak untuk sisanya
        for ($i = 1; $i < 5; $i++) {
            $devices[] = [
                'name' => "Perangkat Sample {$i}",
                'device_type' => 'pressure_sensor',
                'location' => "Lokasi Sample {$i}",
                'status' => 'active',
                'device_group_id' => $groupIds[array_rand($groupIds)] ?? null, // Grup acak
                'configuration' => [
                    'param1' => 'value1',
                    'param2' => 'value2',
                ],
                'last_active_at' => now()->subHours(rand(1, 48)),
            ];
        }

        foreach ($devices as $device) {
            Device::create($device);
        }
    }
}