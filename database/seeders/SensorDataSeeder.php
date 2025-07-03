<?php
// filepath: database/seeders/SensorDataSeeder.php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SensorDataSeeder extends Seeder
{
    public function run(): void
    {
        $deviceIds = Device::pluck('id')->toArray();
        
        // Buat 200 data sensor dengan device_id random
        for ($i = 0; $i < 200; $i++) {
            $randomDeviceId = $deviceIds[array_rand($deviceIds)];
            $time = Carbon::now()->subHours(rand(1, 48));

            $flowrate = mt_rand(200, 350) / 10; // 20.0-35.0 L/s
            $pressure1 = mt_rand(30, 50) / 10;  // 3.0-5.0 bar
            $pressure2 = mt_rand(20, 40) / 10;  // 2.0-4.0 bar
            $battery = mt_rand(70, 100); // Random battery
            $totalizer = mt_rand(1000, 5000) / 10; // Random totalizer

            $errorCode = (rand(1, 30) === 1) ? 'E' . rand(10, 99) : null;

            SensorData::create([
                'device_id' => $randomDeviceId,
                'flowrate' => $flowrate,
                'pressure1' => $pressure1,
                'pressure2' => $pressure2,
                'totalizer' => $totalizer,
                'battery' => $battery,
                'error_code' => $errorCode,
                'recorded_at' => $time,
            ]);
        }
    }
}