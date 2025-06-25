<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SensorDataController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|exists:devices,id',
            'device_key' => 'required|string',
            'flowrate' => 'nullable|numeric',
            'pressure1' => 'nullable|numeric',
            'pressure2' => 'nullable|numeric',
            'battery' => 'nullable|numeric|min:0|max:100',
            'error_code' => 'nullable|string|max:10',
            'timestamp' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Verifikasi perangkat
        $device = Device::find($request->device_id);

        // Jika ini bukan simulasi, validasi API key
        if ($request->device_key !== 'sim_key' && $device->api_key !== $request->device_key) {
            return response()->json(['error' => 'Unauthorized device'], 401);
        }

        // Proses data
        try {
            $sensorData = new SensorData();
            $sensorData->device_id = $request->device_id;
            $sensorData->flowrate = $request->flowrate;
            $sensorData->pressure1 = $request->pressure1;
            $sensorData->pressure2 = $request->pressure2;
            $sensorData->battery = $request->battery;
            $sensorData->error_code = $request->error_code;

            // Gunakan timestamp perangkat jika ada, atau gunakan waktu server
            if ($request->timestamp) {
                $sensorData->recorded_at = Carbon::createFromTimestamp($request->timestamp);
            } else {
                $sensorData->recorded_at = now();
            }

            $sensorData->save();

            return response()->json(['success' => true, 'id' => $sensorData->id]);
        } catch (\Exception $e) {
            Log::error('Error saving sensor data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save data'], 500);
        }
    }

    public function getLatestData(Request $request)
    {
        $devices = Device::all();
        $result = [];
        foreach ($devices as $device) {
            $result[$device->id] = SensorData::where('device_id', $device->id)
                ->orderBy('recorded_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($device) {
                    $item->device = ['id' => $device->id, 'name' => $device->name];
                    return $item;
                });
        }
        return response()->json($result);
    }
}
