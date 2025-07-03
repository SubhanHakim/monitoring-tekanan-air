<?php
// filepath: app/Http/Controllers/Unit/DashboardApiController.php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardApiController extends Controller
{
    /**
     * Get latest sensor data for dashboard charts
     */
    public function getLatestData()
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([]);
        }

        // Ambil perangkat untuk unit ini
        $deviceIds = $unit->devices()->pluck('id')->toArray();

        if (empty($deviceIds)) {
            return response()->json([]);
        }

        // Ambil 50 data terbaru per device untuk grafik yang lebih smooth
        $result = [];
        foreach ($deviceIds as $deviceId) {
            $data = SensorData::where('device_id', $deviceId)
                ->with('device:id,name,location') // hanya ambil field yang diperlukan
                ->orderBy('recorded_at', 'desc')
                ->limit(50)
                ->get()
                ->reverse() // urutkan dari terlama ke terbaru untuk grafik
                ->values() // reset array keys
                ->toArray();

            $result[$deviceId] = $data;
        }

        return response()->json($result);
    }

    /**
     * Store manual sensor data input
     */
    public function manualInput(Request $request)
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer|exists:devices,id',
            'pressure1' => 'required|numeric|min:0|max:100',
            'pressure2' => 'nullable|numeric|min:0|max:100',
            'flowrate' => 'required|numeric|min:0|max:1000',
            'totalizer' => 'required|numeric|min:0',
            'battery' => 'required|numeric|min:0|max:100',
            'error_code' => 'nullable|string|max:10',
        ], [
            'device_id.required' => 'Perangkat harus dipilih',
            'device_id.exists' => 'Perangkat tidak ditemukan',
            'pressure1.required' => 'Tekanan 1 wajib diisi',
            'pressure1.numeric' => 'Tekanan 1 harus berupa angka',
            'pressure1.min' => 'Tekanan 1 minimal 0',
            'pressure1.max' => 'Tekanan 1 maksimal 100',
            'pressure2.numeric' => 'Tekanan 2 harus berupa angka',
            'pressure2.min' => 'Tekanan 2 minimal 0',
            'pressure2.max' => 'Tekanan 2 maksimal 100',
            'flowrate.required' => 'Flowrate wajib diisi',
            'flowrate.numeric' => 'Flowrate harus berupa angka',
            'flowrate.min' => 'Flowrate minimal 0',
            'flowrate.max' => 'Flowrate maksimal 1000',
            'totalizer.required' => 'Totalizer wajib diisi',
            'totalizer.numeric' => 'Totalizer harus berupa angka',
            'totalizer.min' => 'Totalizer minimal 0',
            'battery.required' => 'Battery wajib diisi',
            'battery.numeric' => 'Battery harus berupa angka',
            'battery.min' => 'Battery minimal 0',
            'battery.max' => 'Battery maksimal 100',
            'error_code.string' => 'Kode error harus berupa teks',
            'error_code.max' => 'Kode error maksimal 10 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Pastikan device milik unit ini
        $device = Device::where('id', $request->device_id)
                       ->where('unit_id', $unit->id)
                       ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat tidak ditemukan atau tidak memiliki akses'
            ], 404);
        }

        try {
            // Ambil totalizer terakhir untuk validasi
            $lastSensorData = SensorData::where('device_id', $device->id)
                                      ->orderBy('recorded_at', 'desc')
                                      ->first();

            // Validasi totalizer tidak boleh lebih kecil dari data sebelumnya
            if ($lastSensorData && $request->totalizer < $lastSensorData->totalizer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Totalizer tidak boleh lebih kecil dari data sebelumnya (' . $lastSensorData->totalizer . ' L)'
                ], 422);
            }

            // Simpan data sensor
            $sensorData = SensorData::create([
                'device_id' => $request->device_id,
                'pressure1' => $request->pressure1,
                'pressure2' => $request->pressure2,
                'flowrate' => $request->flowrate,
                'totalizer' => $request->totalizer,
                'battery' => $request->battery,
                'error_code' => $request->error_code,
                'recorded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data sensor berhasil disimpan',
                'data' => $sensorData->load('device:id,name,location')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get device statistics for cards
     */
    public function getDeviceStats()
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([
                'total' => 0,
                'active' => 0,
                'offline' => 0,
                'error' => 0
            ]);
        }

        $devices = $unit->devices()->with('lastData')->get();

        $stats = [
            'total' => $devices->count(),
            'active' => 0,
            'offline' => 0,
            'error' => 0
        ];

        foreach ($devices as $device) {
            $lastData = $device->lastData;
            
            if (!$lastData || $lastData->recorded_at < now()->subMinutes(30)) {
                $stats['offline']++;
            } elseif ($lastData->error_code || $lastData->battery < 15) {
                $stats['error']++;
            } else {
                $stats['active']++;
            }
        }

        return response()->json($stats);
    }

    /**
     * Get device list with latest data
     */
    public function getDeviceList()
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([]);
        }

        $devices = $unit->devices()
                       ->with(['lastData' => function($query) {
                           $query->select('device_id', 'pressure1', 'pressure2', 'flowrate', 'totalizer', 'battery', 'error_code', 'recorded_at');
                       }])
                       ->select('id', 'name', 'location', 'unit_id')
                       ->get();

        $deviceList = [];
        foreach ($devices as $device) {
            $lastData = $device->lastData;
            $status = 'offline';
            
            if ($lastData && $lastData->recorded_at >= now()->subMinutes(30)) {
                if ($lastData->error_code || $lastData->battery < 15) {
                    $status = 'error';
                } else {
                    $status = 'active';
                }
            }

            $deviceList[] = [
                'id' => $device->id,
                'name' => $device->name,
                'location' => $device->location,
                'status' => $status,
                'last_data' => $lastData ? [
                    'pressure1' => $lastData->pressure1,
                    'pressure2' => $lastData->pressure2,
                    'flowrate' => $lastData->flowrate,
                    'totalizer' => $lastData->totalizer,
                    'battery' => $lastData->battery,
                    'error_code' => $lastData->error_code,
                    'recorded_at' => $lastData->recorded_at->diffForHumans(),
                ] : null
            ];
        }

        return response()->json($deviceList);
    }

    /**
     * Get specific device data for detailed view
     */
    public function getDeviceData($deviceId, Request $request)
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Pastikan device milik unit ini
        $device = Device::where('id', $deviceId)
                       ->where('unit_id', $unit->id)
                       ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        // Ambil limit dari request, default 100
        $limit = $request->get('limit', 100);
        $limit = min($limit, 1000); // maksimal 1000 data

        $sensorData = SensorData::where('device_id', $deviceId)
                               ->orderBy('recorded_at', 'desc')
                               ->limit($limit)
                               ->get()
                               ->reverse()
                               ->values();

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'location' => $device->location,
            ],
            'data' => $sensorData
        ]);
    }
}