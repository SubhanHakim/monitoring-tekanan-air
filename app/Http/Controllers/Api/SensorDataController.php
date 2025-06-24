<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SensorDataController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'data' => 'required|array',
            'data.*.recorded_at' => 'required|date',
            'data.*.flowrate' => 'nullable|numeric',
            'data.*.totalizer' => 'nullable|numeric',
            'data.*.battery' => 'nullable|numeric',
            'data.*.pressure1' => 'nullable|numeric',
            'data.*.pressure2' => 'nullable|numeric',
        ]);

        $device = Device::findOrFail($request->device_id);
        $sensorDataRecords = [];

        foreach ($request->data as $record) {
            $sensorData = new SensorData([
                'device_id' => $device->id,
                'recorded_at' => Carbon::parse($record['recorded_at']),
                'flowrate' => $record['flowrate'] ?? null,
                'totalizer' => $record['totalizer'] ?? null,
                'battery' => $record['battery'] ?? null,
                'pressure1' => $record['pressure1'] ?? null,
                'pressure2' => $record['pressure2'] ?? null,
                'additional_data' => array_diff_key($record, array_flip([
                    'recorded_at', 'flowrate', 'totalizer', 'battery', 'pressure1', 'pressure2'
                ])),
            ]);
            
            $sensorData->save();
            $sensorDataRecords[] = $sensorData;
        }

        // Update last_active_at pada device
        $device->update(['last_active_at' => now()]);

        return response()->json([
            'message' => 'Data sensor berhasil disimpan',
            'count' => count($sensorDataRecords),
        ], 201);
    }
}