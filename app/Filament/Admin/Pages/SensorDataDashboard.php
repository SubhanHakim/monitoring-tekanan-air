<?php

namespace App\Filament\Admin\Pages;

use App\Models\Device;
use App\Models\SensorData;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SensorDataDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static ?string $navigationLabel = 'Dashboard Sensor';
    
    protected static ?string $navigationGroup = 'Monitoring';
    
    protected static ?string $title = 'Dashboard Sensor';
    
    protected static ?int $navigationSort = 0;
    
    protected static string $view = 'filament.admin.pages.sensor-data-dashboard';
    
    public array $chartData = [];
    public array $deviceOptions = [];
    public ?string $selectedDevice = null;
    public string $dateRange = 'today';
    
    public function mount()
    {
        $this->deviceOptions = Device::pluck('name', 'id')->toArray();
        
        if (!empty($this->deviceOptions)) {
            $this->selectedDevice = array_key_first($this->deviceOptions);
        }
        
        $this->loadChartData();
    }
    
    public function loadChartData()
    {
        if (!$this->selectedDevice) {
            return;
        }
        
        $query = SensorData::where('device_id', $this->selectedDevice);
        
        // Filter berdasarkan rentang tanggal
        switch ($this->dateRange) {
            case 'today':
                $query->whereDate('recorded_at', Carbon::today());
                break;
            case 'yesterday':
                $query->whereDate('recorded_at', Carbon::yesterday());
                break;
            case 'last7days':
                $query->whereDate('recorded_at', '>=', Carbon::now()->subDays(7));
                break;
            case 'last30days':
                $query->whereDate('recorded_at', '>=', Carbon::now()->subDays(30));
                break;
        }
        
        $data = $query->orderBy('recorded_at')
            ->get(['recorded_at', 'flowrate', 'battery', 'pressure1', 'pressure2']);
        
        $labels = $data->pluck('recorded_at')->map(function ($date) {
            return Carbon::parse($date)->format('H:i:s');
        })->toArray();
        
        $this->chartData = [
            'labels' => $labels,
            'flowrate' => $data->pluck('flowrate')->toArray(),
            'battery' => $data->pluck('battery')->toArray(),
            'pressure1' => $data->pluck('pressure1')->toArray(),
            'pressure2' => $data->pluck('pressure2')->toArray(),
        ];
    }
    
    public function updatedSelectedDevice()
    {
        $this->loadChartData();
    }
    
    public function updatedDateRange()
    {
        $this->loadChartData();
    }
}