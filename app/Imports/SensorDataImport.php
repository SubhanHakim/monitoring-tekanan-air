<?php
// filepath: app/Imports/SensorDataImport.php

namespace App\Imports;

use App\Models\SensorData;
use App\Models\Device;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SensorDataImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    protected $deviceId;
    
    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    public function model(array $row)
    {
        // Validate device belongs to user's unit
        $user = Auth::user();
        $device = Device::where('id', $this->deviceId)
                       ->where('unit_id', $user->unit->id)
                       ->first();
        
        if (!$device) {
            throw new \Exception('Perangkat tidak ditemukan atau tidak memiliki akses');
        }

        return new SensorData([
            'device_id' => $this->deviceId,
            'pressure1' => $row['pressure1'] ?? 0,
            'pressure2' => $row['pressure2'] ?? 0,
            'flowrate' => $row['flowrate'] ?? 0,
            'totalizer' => $row['totalizer'] ?? 0,
            'battery' => $row['battery'] ?? 100,
            'error_code' => $row['error_code'] ?? null,
            'recorded_at' => $this->parseDate($row['recorded_at'] ?? now()),
        ]);
    }

    public function rules(): array
    {
        return [
            'pressure1' => 'required|numeric|min:0|max:100',
            'flowrate' => 'required|numeric|min:0|max:1000',
            'totalizer' => 'required|numeric|min:0',
            'battery' => 'required|numeric|min:0|max:100',
            'pressure2' => 'nullable|numeric|min:0|max:100',
            'error_code' => 'nullable|string|max:10',
            'recorded_at' => 'nullable|date',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return now();
        }
        
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
}