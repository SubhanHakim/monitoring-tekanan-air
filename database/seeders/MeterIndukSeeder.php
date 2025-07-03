<?php
// database/seeders/MeterIndukSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\DeviceVersion;

class MeterIndukSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/data/meter_induk.csv');
        $file = fopen($path, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($headers)) continue;
            $data = array_combine($headers, $row);

            $code = $data['kode'] ?: 'MTR-' . $data['gid'];
            $location = trim("{$data['kel_des']}, {$data['kec']}, {$data['kot_kab']}", ', ');

            $device = Device::create([
                'code' => $code,
                'name' => $data['name'],
                'device_type' => 'meter_induk',
                'location' => $location,
                'status' => in_array($data['kondisi'], ['active', 'inactive', 'maintenance', 'error']) ? $data['kondisi'] : 'active',
                'configuration' => json_encode([
                    'diameter' => $data['diameter'],
                    'merek' => $data['merek'],
                    'pelayanan' => $data['pelayanan'],
                    'sbr_dana' => $data['sbr_dana'],
                    'jenis' => $data['jenis'],
                    'kondisi' => $data['kondisi'],
                    'foto' => $data['foto'],
                    'gbr_teknik' => $data['gbr_teknik'],
                    'keterangan' => $data['keterangan'],
                    'geom' => $data['geom'],
                ]),
                'last_active_at' => now(),
            ]);

            // Parse geom jika ada
            $lat = null; $lng = null;
            if (!empty($data['geom']) && preg_match('/POINT\\(([-\d.]+) ([-\d.]+)\\)/', $data['geom'], $match)) {
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
                'installed_at' => $data['tgl_pasang'] ?: null,
                'installation_year' => is_numeric($data['tahun']) ? (int)$data['tahun'] : null,
                'configuration' => $device->configuration,
                'effective_from' => now(),
                'effective_to' => null,
            ]);
        }

        fclose($file);
    }
}