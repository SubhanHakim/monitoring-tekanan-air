<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
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

        // Ambil 10 data terbaru per device, urut dari terbaru ke terlama, lalu balik agar grafik maju
        $result = [];
        foreach ($deviceIds as $deviceId) {
            $data = \App\Models\SensorData::where('device_id', $deviceId)
                ->with('device')
                ->orderBy('recorded_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            $data = array_reverse($data); // urutkan dari terlama ke terbaru
            $result[$deviceId] = $data;
        }

        return response()->json($result);
    }
}
