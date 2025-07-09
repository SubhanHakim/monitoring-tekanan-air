<?php
// filepath: app/Filament/Admin/Pages/SensorDataDashboard.php

namespace App\Filament\Admin\Pages;

use App\Models\Device;
use App\Models\SensorData;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Filter berdasarkan unit jika user adalah role 'unit'
        if (Auth::user()->role === 'unit') {
            $this->deviceOptions = Device::where('unit_id', Auth::user()->unit_id)->pluck('name', 'id')->toArray();
        } else {
            $this->deviceOptions = Device::pluck('name', 'id')->toArray();
        }

        if (!empty($this->deviceOptions)) {
            $this->selectedDevice = array_key_first($this->deviceOptions);
        }

        $this->loadChartData();
    }

    public function loadChartData()
    {
        if (!$this->selectedDevice) {
            $this->chartData = [
                'labels' => [],
                'flowrate' => [],
                'battery' => [],
                'pressure1' => [],
                'pressure2' => [],
                'totalizer' => [],
            ];
            return;
        }

        // 🔧 PERBAIKAN: Gunakan strategi fallback untuk mendapatkan lebih banyak data
        $query = SensorData::where('device_id', $this->selectedDevice);

        // 🐛 DEBUG: Log device yang dipilih
        Log::info('SensorDataDashboard - Selected Device: ' . $this->selectedDevice);
        Log::info('SensorDataDashboard - Date Range: ' . $this->dateRange);

        // 🔧 STRATEGI BARU: Jika data hari ini sedikit, ambil dari rentang yang lebih luas
        $todayCount = SensorData::where('device_id', $this->selectedDevice)
            ->whereDate('recorded_at', Carbon::today())
            ->count();

        Log::info('SensorDataDashboard - Today count: ' . $todayCount);

        // Jika data hari ini < 10, gunakan rentang yang lebih luas
        if ($this->dateRange === 'today' && $todayCount < 10) {
            Log::info('SensorDataDashboard - Using fallback to last 7 days due to insufficient today data');
            $query->whereDate('recorded_at', '>=', Carbon::now()->subDays(7));
        } else {
            // Filter berdasarkan rentang tanggal yang dipilih
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
        }

        // 🐛 DEBUG: Log raw query sebelum limit
        $totalRecords = $query->count();
        Log::info('SensorDataDashboard - Total records found: ' . $totalRecords);

        // 🔧 PERBAIKAN: Pastikan query PERSIS SAMA dengan dashboard utama
        $data = $query->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get(['id', 'recorded_at', 'flowrate', 'battery', 'pressure1', 'pressure2', 'totalizer'])
            ->reverse();

        // 🐛 DEBUG: Log data yang didapat
        Log::info('SensorDataDashboard - Data count after limit: ' . $data->count());
        Log::info('SensorDataDashboard - First record: ' . $data->first()?->recorded_at);
        Log::info('SensorDataDashboard - Last record: ' . $data->last()?->recorded_at);

        // 🔧 PERBAIKAN: Fix variabel $data di closure dengan use
        $firstDate = $data->first() ? Carbon::parse($data->first()->recorded_at)->toDateString() : null;
        $lastDate = $data->last() ? Carbon::parse($data->last()->recorded_at)->toDateString() : null;
        
        // Format label waktu
        $labels = $data->pluck('recorded_at')->map(function ($date) use ($firstDate, $lastDate) {
            $carbon = Carbon::parse($date);
            
            if ($firstDate !== $lastDate) {
                return $carbon->format('m/d H:i'); // Tampilkan bulan/hari jika lintas hari
            } else {
                return $carbon->format('H:i'); // Hanya jam:menit jika masih hari yang sama
            }
        })->toArray();

        $this->chartData = [
            'labels' => $labels,
            'flowrate' => $data->pluck('flowrate')->map(function ($value) {
                return $value !== null ? (float) $value : 0;
            })->toArray(),
            'battery' => $data->pluck('battery')->map(function ($value) {
                return $value !== null ? (float) $value : 0;
            })->toArray(),
            'pressure1' => $data->pluck('pressure1')->map(function ($value) {
                return $value !== null ? (float) $value : 0;
            })->toArray(),
            'pressure2' => $data->pluck('pressure2')->map(function ($value) {
                return $value !== null ? (float) $value : 0;
            })->toArray(),
            'totalizer' => $data->pluck('totalizer')->map(function ($value) {
                return $value !== null ? (float) $value : 0;
            })->toArray(),
        ];

        // 🐛 DEBUG: Log final chart data
        Log::info('SensorDataDashboard - Final labels count: ' . count($this->chartData['labels']));
        Log::info('SensorDataDashboard - Final labels: ' . json_encode($this->chartData['labels']));

        $this->dispatch('chartDataUpdated');
    }

    public function updatedSelectedDevice()
    {
        Log::info('SensorDataDashboard - Device changed to: ' . $this->selectedDevice);
        $this->loadChartData();
    }

    public function updatedDateRange()
    {
        Log::info('SensorDataDashboard - Date range changed to: ' . $this->dateRange);
        $this->loadChartData();
    }

    // 🔧 TAMBAHAN: Method untuk debugging manual
    public function getDebugInfo()
    {
        if (!$this->selectedDevice) {
            return 'No device selected';
        }

        $allDevicesData = [];
        foreach ($this->deviceOptions as $deviceId => $deviceName) {
            $todayCount = SensorData::where('device_id', $deviceId)
                ->whereDate('recorded_at', Carbon::today())
                ->count();
            $allDevicesData[] = [
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'today_count' => $todayCount
            ];
        }

        $query = SensorData::where('device_id', $this->selectedDevice);
        
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

        $totalCount = $query->count();
        $latestRecords = $query->orderBy('recorded_at', 'desc')->limit(5)->get(['recorded_at', 'pressure1']);

        return [
            'device_id' => $this->selectedDevice,
            'date_range' => $this->dateRange,
            'total_records' => $totalCount,
            'latest_5_records' => $latestRecords->toArray(),
            'carbon_today' => Carbon::today()->toDateString(),
            'all_devices_today_count' => $allDevicesData,
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ];
    }
}