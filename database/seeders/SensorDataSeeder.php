<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SensorDataSeeder extends Seeder
{
    public function run(): void
    {
        $devices = Device::all();
        
        if ($devices->isEmpty()) {
            $this->command->info('Tidak ada perangkat untuk dibuat data sensor. Silakan buat perangkat terlebih dahulu.');
            return;
        }
        
        foreach ($devices as $device) {
            // Buat data untuk 24 jam terakhir dengan interval 15 menit
            for ($hour = 0; $hour < 24; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 15) {
                    $time = now()->subHours(24)->addHours($hour)->addMinutes($minute);
                    
                    $flowrateBase = $device->device_type === 'flow_meter' ? rand(-20, 20) / 10 : null;
                    $batteryBase = rand(30, 40) / 10; // 3.0 - 4.0 volt
                    $pressure1Base = $device->device_type === 'pressure_sensor' ? rand(10, 80) / 10 : null; // 1.0 - 8.0 bar
                    $pressure2Base = $device->device_type === 'pressure_sensor' ? rand(10, 60) / 10 : null; // 1.0 - 6.0 bar
                    
                    // Tambahkan noise kecil
                    $flowrate = $flowrateBase !== null ? $flowrateBase + (rand(-10, 10) / 100) : null;
                    $battery = $batteryBase + (rand(-5, 5) / 100); // Sedikit variasi
                    $pressure1 = $pressure1Base !== null ? $pressure1Base + (rand(-10, 10) / 100) : null;
                    $pressure2 = $pressure2Base !== null ? $pressure2Base + (rand(-10, 10) / 100) : null;
                    
                    SensorData::create([
                        'device_id' => $device->id,
                        'recorded_at' => $time,
                        'flowrate' => $flowrate,
                        'totalizer' => $flowrateBase !== null ? rand(1000, 5000) / 100 : null,
                        'battery' => $battery,
                        'pressure1' => $pressure1,
                        'pressure2' => $pressure2,
                    ]);
                }
            }
            
            // Update device
            $device->update(['last_active_at' => now()]);
        }
        
        $this->command->info('Berhasil membuat ' . SensorData::count() . ' data sensor.');
    }
}