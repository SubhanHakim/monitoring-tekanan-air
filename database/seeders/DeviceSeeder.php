<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\DeviceGroup;
use Carbon\Carbon;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Membuat perangkat...');

        // Dapatkan grup yang sudah ada
        $zonaUtara = DeviceGroup::where('name', 'Zona Utara')->first();
        $zonaSelatan = DeviceGroup::where('name', 'Zona Selatan')->first();
        $zonaTimur = DeviceGroup::where('name', 'Zona Timur')->first();
        $zonaBarat = DeviceGroup::where('name', 'Zona Barat')->first();

        // Buat perangkat
        $devices = [
            // Zona Utara
            [
                'name' => 'FLOW-UT-001',
                'device_type' => 'flowmeter',
                'location' => 'Jl. Merdeka No. 1, Jakarta Utara',
                'status' => 'active',
                'device_group_id' => $zonaUtara ? $zonaUtara->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 15, // menit
                    'report_interval' => 60,  // menit
                    'threshold_high' => 3.5,  // bar
                    'threshold_low' => 0.5,   // bar
                    'battery_threshold' => 20, // persen
                ]),
                'last_active_at' => Carbon::now()->subMinutes(rand(5, 60)),
            ],
            [
                'name' => 'PRESS-UT-001',
                'device_type' => 'pressure',
                'location' => 'Jl. Sudirman No. 10, Jakarta Utara',
                'status' => 'active',
                'device_group_id' => $zonaUtara ? $zonaUtara->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 10, // menit
                    'report_interval' => 30,  // menit
                    'threshold_high' => 4.0,  // bar
                    'threshold_low' => 0.8,   // bar
                    'battery_threshold' => 15, // persen
                ]),
                'last_active_at' => Carbon::now()->subMinutes(rand(5, 60)),
            ],
            
            // Zona Selatan
            [
                'name' => 'FLOW-SL-001',
                'device_type' => 'flowmeter',
                'location' => 'Jl. Gatot Subroto No. 15, Jakarta Selatan',
                'status' => 'active',
                'device_group_id' => $zonaSelatan ? $zonaSelatan->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 15, // menit
                    'report_interval' => 60,  // menit
                    'threshold_high' => 3.5,  // bar
                    'threshold_low' => 0.5,   // bar
                    'battery_threshold' => 20, // persen
                ]),
                'last_active_at' => Carbon::now()->subMinutes(rand(5, 60)),
            ],
            
            // Zona Timur
            [
                'name' => 'FLOW-TM-001',
                'device_type' => 'flowmeter',
                'location' => 'Jl. Bekasi Raya No. 20, Jakarta Timur',
                'status' => 'active',
                'device_group_id' => $zonaTimur ? $zonaTimur->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 15, // menit
                    'report_interval' => 60,  // menit
                    'threshold_high' => 3.2,  // bar
                    'threshold_low' => 0.6,   // bar
                    'battery_threshold' => 20, // persen
                ]),
                'last_active_at' => Carbon::now()->subMinutes(rand(5, 60)),
            ],
            
            // Zona Barat
            [
                'name' => 'FLOW-BR-001',
                'device_type' => 'flowmeter',
                'location' => 'Jl. Puri Indah No. 5, Jakarta Barat',
                'status' => 'active',
                'device_group_id' => $zonaBarat ? $zonaBarat->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 15, // menit
                    'report_interval' => 60,  // menit
                    'threshold_high' => 3.8,  // bar
                    'threshold_low' => 0.7,   // bar
                    'battery_threshold' => 20, // persen
                ]),
                'last_active_at' => Carbon::now()->subMinutes(rand(5, 60)),
            ],
            
            // Beberapa perangkat dalam status maintenance atau inactive
            [
                'name' => 'FLOW-UT-002',
                'device_type' => 'flowmeter',
                'location' => 'Jl. Mangga Dua No. 8, Jakarta Utara',
                'status' => 'maintenance',
                'device_group_id' => $zonaUtara ? $zonaUtara->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 15, // menit
                    'report_interval' => 60,  // menit
                    'threshold_high' => 3.5,  // bar
                    'threshold_low' => 0.5,   // bar
                    'battery_threshold' => 20, // persen
                ]),
                'last_active_at' => Carbon::now()->subDays(3),
            ],
            [
                'name' => 'PRESS-SL-001',
                'device_type' => 'pressure',
                'location' => 'Jl. Kemang No. 12, Jakarta Selatan',
                'status' => 'inactive',
                'device_group_id' => $zonaSelatan ? $zonaSelatan->id : null,
                'configuration' => json_encode([
                    'reading_interval' => 10, // menit
                    'report_interval' => 30,  // menit
                    'threshold_high' => 4.0,  // bar
                    'threshold_low' => 0.8,   // bar
                    'battery_threshold' => 15, // persen
                ]),
                'last_active_at' => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($devices as $deviceData) {
            Device::create($deviceData);
        }

        $this->command->info('Perangkat berhasil dibuat!');
    }
}