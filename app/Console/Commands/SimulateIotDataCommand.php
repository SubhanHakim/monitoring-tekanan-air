<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SimulateIotDataCommand extends Command
{
    protected $signature = 'iot:simulate 
                            {--devices=all : ID perangkat yang akan disimulasikan, pisahkan dengan koma atau "all" untuk semua}
                            {--interval=5 : Interval pengiriman data dalam detik}
                            {--count=10 : Jumlah data yang akan dikirim per perangkat}
                            {--realtime : Mode realtime (berjalan terus tanpa batas)}';
                            
    protected $description = 'Simulasi pengiriman data dari perangkat IoT tekanan air';

    public function handle()
    {
        $deviceFilter = $this->option('devices');
        $interval = (int)$this->option('interval');
        $count = (int)$this->option('count');
        $realtime = $this->option('realtime');
        
        // Ambil daftar perangkat
        $devices = ($deviceFilter === 'all') 
            ? Device::all() 
            : Device::whereIn('id', explode(',', $deviceFilter))->get();
            
        if ($devices->isEmpty()) {
            $this->error('Tidak ada perangkat yang ditemukan!');
            return 1;
        }
            
        $this->info("Memulai simulasi untuk " . $devices->count() . " perangkat");
        
        if ($realtime) {
            $this->info("Mode realtime aktif. Tekan CTRL+C untuk menghentikan simulasi.");
            $this->simulateRealtime($devices, $interval);
        } else {
            $this->info("Akan dikirim " . $count . " data per perangkat dengan interval " . $interval . " detik");
            $this->simulateBatch($devices, $interval, $count);
        }
        
        return 0;
    }
    
    protected function simulateBatch($devices, $interval, $count)
    {
        $progressBar = $this->output->createProgressBar($devices->count() * $count);
        $progressBar->start();
        
        foreach ($devices as $device) {
            for ($i = 0; $i < $count; $i++) {
                $data = $this->generateRandomData($device->id);
                
                SensorData::create($data);
                
                $this->logData($device, $data);
                $progressBar->advance();
                
                if ($i < $count - 1) {
                    sleep($interval);
                }
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("Simulasi selesai!");
    }
    
    protected function simulateRealtime($devices, $interval)
    {
        $iteration = 0;
        
        while (true) {
            $iteration++;
            $this->info("Iterasi #$iteration - " . Carbon::now()->format('H:i:s'));
            
            foreach ($devices as $device) {
                $data = $this->generateRandomData($device->id);
                
                SensorData::create($data);
                
                $this->logData($device, $data, true);
            }
            
            sleep($interval);
        }
    }
    
    protected function generateRandomData($deviceId)
    {
        // Simulasi perubahan tekanan antara 1-5 bar
        $pressure = round(rand(10, 50) / 10, 2);
        
        // Simulasi flowrate antara 5-20 L/s
        $flowrate = round(rand(50, 200) / 10, 2);
        
        // Simulasi baterai 20-100%
        $battery = rand(20, 100);
        
        // Simulasi error 10% dari waktu
        $errorCode = rand(0, 9) === 0 ? rand(1, 5) : 0;
        
        return [
            'device_id' => $deviceId,
            'pressure1' => $pressure,
            'flowrate' => $flowrate,
            'battery' => $battery,
            'error_code' => $errorCode,
            'recorded_at' => now(),
        ];
    }
    
    protected function logData($device, $data, $compact = false)
    {
        if ($compact) {
            $this->line("Device #{$device->id} ({$device->name}): {$data['pressure1']} bar, {$data['flowrate']} L/s, Baterai: {$data['battery']}%");
        } else {
            $this->line("Data terkirim untuk perangkat #{$device->id} ({$device->name}):");
            $this->line("  - Tekanan: {$data['pressure1']} bar");
            $this->line("  - Flowrate: {$data['flowrate']} L/s");
            $this->line("  - Baterai: {$data['battery']}%");
            $this->line("  - Error: " . ($data['error_code'] > 0 ? "Ya (kode: {$data['error_code']})" : "Tidak"));
        }
        
        // Log ke file juga jika diperlukan
        Log::info("IoT Simulator: Data perangkat #{$device->id}", $data);
    }
}