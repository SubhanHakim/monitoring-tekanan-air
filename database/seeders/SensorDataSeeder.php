<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SensorDataSeeder extends Seeder
{
    public function run(): void
    {
        $devices = Device::all();
        
        foreach ($devices as $device) {
            // Buat data untuk 2 hari terakhir, setiap 1 jam
            for ($hour = 48; $hour >= 0; $hour--) {
                $time = Carbon::now()->subHours($hour);
                
                // Simulasi data dengan sedikit variasi
                $flowrate = mt_rand(200, 350) / 10; // 20.0-35.0 L/s
                $pressure1 = mt_rand(30, 50) / 10;  // 3.0-5.0 bar
                $pressure2 = mt_rand(20, 40) / 10;  // 2.0-4.0 bar
                $battery = 100 - ($hour / 10);      // Battery menurun perlahan
                
                // Sesekali buat error untuk simulasi
                $errorCode = (rand(1, 30) === 1) ? 'E' . rand(10, 99) : null;
                
                SensorData::create([
                    'device_id' => $device->id,
                    'flowrate' => $flowrate,
                    'pressure1' => $pressure1,
                    'pressure2' => $pressure2,
                    'battery' => $battery,
                    'error_code' => $errorCode,
                    'recorded_at' => $time,
                ]);
            }
        }
    }
}