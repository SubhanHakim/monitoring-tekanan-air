<?php
// filepath: database/seeders/ManometerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\DeviceVersion;
use App\Models\Unit;

class ManometerSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua unit_id yang tersedia
        $unitIds = Unit::pluck('id')->toArray();
        
        $devices = [
            [
                'code' => 'MANO-001',
                'name' => 'Manometer Utama 1',
                'device_type' => 'manometer',
                'location' => 'Kelurahan A, Kecamatan B',
                'status' => 'active',
                'diameter' => '100',
                'merek' => 'Yokogawa',
                'note' => 'Terpasang di pintu masuk utama',
                'geom' => 'POINT(107.123456 -6.123456)',
                'tgl_pasang' => '2024-01-01',
                'tahun' => 2024,
            ],
            [
                'code' => 'MANO-002',
                'name' => 'Manometer Cadangan',
                'device_type' => 'manometer',
                'location' => 'Kelurahan C, Kecamatan D',
                'status' => 'maintenance',
                'diameter' => '80',
                'merek' => 'Wika',
                'note' => 'Perlu kalibrasi ulang',
                'geom' => 'POINT(107.654321 -6.654321)',
                'tgl_pasang' => '2023-06-15',
                'tahun' => 2023,
            ],
            [
                'code' => 'MTR-001',
                'name' => 'Meter Induk Perum Panorama',
                'device_type' => 'meterinduk',
                'location' => 'Perum Panorama Garden',
                'status' => 'active',
                'diameter' => '50',
                'merek' => 'Linflow',
                'note' => 'Meter induk utama perumahan',
                'geom' => 'POINT(107.987654 -6.987654)',
                'tgl_pasang' => '2022-05-10',
                'tahun' => 2022,
            ],
            [
                'code' => 'MTR-002',
                'name' => 'Meter Induk Grand Juanda',
                'device_type' => 'meterinduk',
                'location' => 'Grand Juanda',
                'status' => 'maintenance',
                'diameter' => '100',
                'merek' => 'Amico',
                'note' => 'Perlu pengecekan ulang',
                'geom' => 'POINT(107.543210 -6.543210)',
                'tgl_pasang' => '2021-08-20',
                'tahun' => 2021,
            ],
        ];

        foreach ($devices as $data) {
            $device = Device::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'device_type' => $data['device_type'],
                'location' => $data['location'],
                'status' => $data['status'],
                'unit_id' => $unitIds[array_rand($unitIds)], // Random unit_id
                'configuration' => json_encode([
                    'diameter' => $data['diameter'],
                    'merek' => $data['merek'],
                    'note' => $data['note'],
                    'geom' => $data['geom'],
                ]),
                'last_active_at' => now(),
            ]);

            // Parse geom
            $lat = null;
            $lng = null;
            if (!empty($data['geom']) && preg_match('/POINT\(([-\d.]+) ([-\d.]+)\)/', $data['geom'], $match)) {
                $lng = $match[1] ?? null;
                $lat = $match[2] ?? null;
            }

            DeviceVersion::create([
                'device_id' => $device->id,
                'name' => $device->name,
                'device_type' => $device->device_type,
                'location' => $device->location,
                'status' => $device->status,
                'latitude' => $lat,
                'longitude' => $lng,
                'installed_at' => $data['tgl_pasang'],
                'installation_year' => $data['tahun'],
                'configuration' => $device->configuration,
                'effective_from' => now(),
                'effective_to' => null,
            ]);
        }
    }
}