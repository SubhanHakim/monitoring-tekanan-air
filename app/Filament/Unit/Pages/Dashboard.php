<?php

namespace App\Filament\Unit\Pages;

use Filament\Pages\Page;
use App\Models\Device;
use App\Models\SensorData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.unit.pages.dashboard';

    protected static ?int $navigationSort = -2;

    protected ?string $heading = 'Dashboard Unit';

    protected ?string $subheading = 'Monitoring tekanan air real-time';

    protected static bool $shouldRegisterNavigation = true;

    protected function getViewData(): array
    {
        $user = Auth::user();
        $unit = $user->unit;

        if (!$unit) {
            return [
                'unitName' => 'Tidak terhubung ke unit',
                'devices' => collect(),
                'activeDevices' => 0,
                'offlineDevices' => 0, 
                'errorDevices' => 0,
                'latestData' => collect()
            ];
        }

        // Ambil semua perangkat yang terkait dengan unit ini
        $devices = Device::where('unit_id', $unit->id)->get();

        // Hitung status perangkat
        $activeDevices = Device::where('unit_id', $unit->id)
            ->whereHas('lastData', function($query) {
                $query->where('recorded_at', '>=', now()->subMinutes(30));
            })->count();
        
        $offlineDevices = Device::where('unit_id', $unit->id)
            ->whereDoesntHave('lastData', function($query) {
                $query->where('recorded_at', '>=', now()->subMinutes(30));
            })->count();
        
        $errorDevices = Device::where('unit_id', $unit->id)
            ->whereHas('lastData', function($query) {
                $query->where('recorded_at', '>=', now()->subMinutes(30))
                    ->where(function($q) {
                        $q->whereNotNull('error_code')
                            ->orWhere('battery', '<', 15);
                    });
            })->count();
        
        // Data untuk grafik
        $deviceIds = $devices->pluck('id')->toArray();
        
        $latestData = SensorData::whereIn('device_id', $deviceIds)
            ->with('device')
            ->orderBy('recorded_at', 'desc')
            ->limit(200)
            ->get()
            ->groupBy('device_id');

        return [
            'unitName' => $unit->name,
            'devices' => $devices,
            'activeDevices' => $activeDevices,
            'offlineDevices' => $offlineDevices,
            'errorDevices' => $errorDevices,
            'latestData' => $latestData
        ];
    }
}