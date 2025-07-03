<?php
// filepath: app/Filament/Admin/Pages/Dashboard.php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Device;
use App\Models\SensorData;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.admin.pages.dashboard';

    public int $totalDevice = 0;
    public int $totalSensorData = 0;
    public int $totalUnit = 0;
    public int $totalUser = 0;

    public ?string $selectedDevice = null;
    public array $deviceOptions = [];

    public $latestPressure1 = null;
    public $latestPressure2 = null;
    public $latestTotalizer = null;

    public array $chartPressure1 = [];
    public array $chartPressure2 = [];
    public array $chartTotalizer = [];
    public array $chartLabels = [];

    public function mount()
    {
        // Filter berdasarkan unit jika user adalah role 'unit'
        if (auth()->user()->role === 'unit') {
            $this->deviceOptions = Device::where('unit_id', auth()->user()->unit_id)->pluck('name', 'id')->toArray();
            $this->totalDevice = Device::where('unit_id', auth()->user()->unit_id)->count();
            $this->totalSensorData = SensorData::whereHas('device', function ($q) {
                $q->where('unit_id', auth()->user()->unit_id);
            })->count();
        } else {
            $this->deviceOptions = Device::pluck('name', 'id')->toArray();
            $this->totalDevice = Device::count();
            $this->totalSensorData = SensorData::count();
        }

        $this->totalUnit = Unit::count();
        $this->totalUser = User::count();

        // Default pilih device pertama
        if (!$this->selectedDevice && !empty($this->deviceOptions)) {
            $this->selectedDevice = array_key_first($this->deviceOptions);
        }

        $this->loadDeviceData();
    }

    public function updatedSelectedDevice()
    {
        $this->loadDeviceData();
    }

    public function loadDeviceData()
    {
        if (!$this->selectedDevice) {
            $this->latestPressure1 = null;
            $this->latestPressure2 = null;
            $this->latestTotalizer = null;
            $this->chartPressure1 = [];
            $this->chartPressure2 = [];
            $this->chartTotalizer = [];
            $this->chartLabels = [];
            return;
        }

        $latest = SensorData::where('device_id', $this->selectedDevice)
            ->orderByDesc('recorded_at')
            ->first();

        $chartData = SensorData::where('device_id', $this->selectedDevice)
            ->orderByDesc('recorded_at')
            ->limit(20)
            ->get()
            ->reverse();

        $this->latestPressure1 = $latest?->pressure1;
        $this->latestPressure2 = $latest?->pressure2;
        $this->latestTotalizer = $latest?->totalizer;

        $this->chartPressure1 = $chartData->pluck('pressure1')->toArray();
        $this->chartPressure2 = $chartData->pluck('pressure2')->toArray();
        $this->chartTotalizer = $chartData->pluck('totalizer')->toArray();
        $this->chartLabels = $chartData->pluck('recorded_at')->map(fn($d) => Carbon::parse($d)->format('H:i'))->toArray();
    }
}