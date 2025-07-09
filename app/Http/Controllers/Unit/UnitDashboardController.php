<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\UnitReport;
use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UnitDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->unit_id) {
            return redirect()->route('login')->with('error', 'Unit tidak ditemukan');
        }

        $unit = $user->unit;
        
        // Check if unit is active
        if (!$unit->isActive()) {
            return view('unit.inactive')->with('unit', $unit);
        }
        
        // Statistics for Management Dashboard
        $totalDevices = Device::where('unit_id', $unit->id)->count();
        $activeDevices = Device::where('unit_id', $unit->id)
            ->whereHas('sensorData', function($query) {
                $query->where('recorded_at', '>=', Carbon::now()->subHours(1));
            })
            ->count();
        
        $totalReports = UnitReport::where('unit_id', $unit->id)->count();
        $completedReports = UnitReport::where('unit_id', $unit->id)
            ->where('status', 'completed')
            ->count();

        // Recent reports for management
        $recentReports = UnitReport::where('unit_id', $unit->id)
            ->with(['device', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent sensor data for overview
        $recentSensorData = SensorData::whereHas('device', function($query) use ($unit) {
                $query->where('unit_id', $unit->id);
            })
            ->with('device')
            ->orderBy('recorded_at', 'desc')
            ->limit(10)
            ->get();

        return view('unit.management.dashboard', compact(
            'unit',
            'totalDevices',
            'activeDevices',
            'totalReports',
            'completedReports',
            'recentReports',
            'recentSensorData'
        ));
    }
}