<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Console\Command;
use League\Csv\Reader;

class ImportSensorData extends Command
{
    protected $signature = 'app:import-sensor-data {file} {device_id}';
    protected $description = 'Import data sensor dari file CSV';

    public function handle()
    {
        $file = $this->argument('file');
        $deviceId = $this->argument('device_id');
        
        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }
        
        // Cari device
        $device = Device::find($deviceId);
        if (!$device) {
            $this->error("Device dengan ID {$deviceId} tidak ditemukan");
            return 1;
        }
        
        $this->info("Mengimpor data untuk perangkat: {$device->name}");
        
        // Baca CSV
        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);
        
        $records = $csv->getRecords();
        $count = 0;
        $errors = 0;
        
        foreach ($records as $record) {
            try {
                // Parse tanggal dan waktu
                $recordedAt = null;
                if (isset($record['Waktu'])) {
                    $recordedAt = Carbon::parse($record['Waktu']);
                }
                
                if (!$recordedAt) {
                    $this->warn("Baris dilewati: Format waktu tidak valid");
                    $errors++;
                    continue;
                }
                
                // Parse flowrate
                $flowrate = null;
                if (isset($record['Flowrate'])) {
                    $flowrateString = $record['Flowrate'];
                    if (preg_match('/(-?\d+\.\d+)\s+l\/s/', $flowrateString, $matches)) {
                        $flowrate = (float) $matches[1];
                    }
                }
                
                // Parse battery
                $battery = null;
                if (isset($record['Battery'])) {
                    $batteryString = $record['Battery'];
                    if (preg_match('/(\d+\.\d+)\s+Volt/', $batteryString, $matches)) {
                        $battery = (float) $matches[1];
                    }
                }
                
                // Parse pressure1
                $pressure1 = null;
                if (isset($record['Pressure1'])) {
                    $pressureString = $record['Pressure1'];
                    if ($pressureString !== 'null bar' && preg_match('/(\d+\.\d+)\s+bar/', $pressureString, $matches)) {
                        $pressure1 = (float) $matches[1];
                    }
                }
                
                // Parse pressure2
                $pressure2 = null;
                if (isset($record['Pressure2'])) {
                    $pressureString = $record['Pressure2'];
                    if ($pressureString !== 'null bar' && preg_match('/(\d+\.\d+)\s+bar/', $pressureString, $matches)) {
                        $pressure2 = (float) $matches[1];
                    }
                }
                
                // Buat data sensor
                SensorData::create([
                    'device_id' => $device->id,
                    'recorded_at' => $recordedAt,
                    'flowrate' => $flowrate,
                    'battery' => $battery,
                    'pressure1' => $pressure1,
                    'pressure2' => $pressure2,
                ]);
                
                $count++;
            } catch (\Exception $e) {
                $this->error("Error pada baris: " . json_encode($record) . " - " . $e->getMessage());
                $errors++;
            }
        }
        
        // Update last_active_at
        $device->update(['last_active_at' => now()]);
        
        $this->info("Selesai: {$count} baris diimpor, {$errors} error");
        return 0;
    }
}