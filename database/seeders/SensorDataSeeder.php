<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorData;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SensorDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = Device::all();
        
        if ($devices->isEmpty()) {
            $this->command->info('Tidak ada device yang tersedia. Jalankan DeviceSeeder terlebih dahulu.');
            return;
        }

        $this->command->info('Membuat data sensor untuk 30 hari terakhir...');
        
        foreach ($devices as $device) {
            $this->command->info("Membuat data untuk device: {$device->name}");
            
            // Tentukan nilai sensor berdasarkan tipe perangkat
            $isFlowmeter = $device->device_type === 'flowmeter';
            $isPressure = $device->device_type === 'pressure';
            
            // Buat data untuk 30 hari terakhir dengan interval 1 jam
            for ($day = 30; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);
                
                // Buat 24 data per hari (setiap jam)
                $sensorData = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    $timestamp = $date->copy()->hour($hour)->minute(rand(0, 59))->second(rand(0, 59));
                    
                    // Hitung nilai normal dengan sedikit variasi
                    $flowrate = $isFlowmeter ? $this->generateRandomValue(25, 35, 3) : null; // 25-35 m3/h dengan variasi 3
                    $pressure1 = $this->generateRandomValue(2.5, 3.5, 0.2); // 2.5-3.5 bar dengan variasi 0.2
                    $pressure2 = $isPressure ? $this->generateRandomValue(1.8, 2.5, 0.15) : null; // 1.8-2.5 bar dengan variasi 0.15
                    $battery = $this->generateRandomValue(90, 100, 1); // 90-100% dengan variasi kecil
                    
                    // Sesekali tambahkan anomali (5% kemungkinan)
                    if (rand(1, 100) <= 5) {
                        switch (rand(1, 3)) {
                            case 1:
                                $flowrate = $isFlowmeter ? $this->generateRandomValue(15, 45, 10) : null; // Anomali flow rate
                                break;
                            case 2:
                                $pressure1 = $this->generateRandomValue(1.5, 4.5, 1); // Anomali pressure1
                                break;
                            case 3:
                                $pressure2 = $isPressure ? $this->generateRandomValue(1.0, 3.2, 0.8) : null; // Anomali pressure2
                                break;
                        }
                    }
                    
                    // Tambahkan data
                    $sensorData[] = [
                        'device_id' => $device->id,
                        'flowrate' => $flowrate,
                        'pressure1' => $pressure1,
                        'pressure2' => $pressure2,
                        'battery' => $battery,
                        'recorded_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                
                // Insert batch
                if (!empty($sensorData)) {
                    DB::table('sensor_data')->insert($sensorData);
                }
            }
        }
        
        $this->command->info('Selesai membuat data sensor!');
    }
    
    /**
     * Generate random value with normal distribution
     */
    private function generateRandomValue($min, $max, $variation)
    {
        $base = rand($min * 100, $max * 100) / 100;
        $variation = (rand(-$variation * 100, $variation * 100) / 100);
        return round($base + $variation, 2);
    }
}