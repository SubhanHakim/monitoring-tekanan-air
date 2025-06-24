<?php

namespace App\Filament\Admin\Pages;

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\SensorData;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnalyticsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationLabel = 'Laporan Analitik';
    
    protected static ?string $navigationGroup = 'Laporan & Analitik';
    
    protected static ?string $title = 'Laporan Analitik';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.admin.pages.analytics-report';
    
    // Form model properties
    public $deviceId = null;
    public $deviceGroupId = null;
    public $startDate;
    public $endDate;
    public $groupBy = 'daily';
    public $reportType = 'pressure';
    
    // Result data properties
    public $chartData = [];
    public $summaryData = [];
    public $tableData = [];
    
    public function mount()
    {
        // Set default date range to last 7 days
        $this->startDate = now()->subDays(7)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }
    
    protected function getFormSchema(): array
    {
        return [
            Select::make('reportType')
                ->label('Tipe Laporan')
                ->options([
                    'pressure' => 'Tekanan Air',
                    'flowrate' => 'Debit Air',
                    'battery' => 'Status Baterai',
                    'alert' => 'Peringatan & Anomali',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn() => $this->resetData()),
                
            Select::make('deviceGroupId')
                ->label('Grup Perangkat')
                ->options(fn() => DeviceGroup::pluck('name', 'id')->toArray())
                ->placeholder('Semua Grup')
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->deviceId = null;
                    $this->resetData();
                }),
                
            Select::make('deviceId')
                ->label('Perangkat')
                ->options(function () {
                    $query = Device::query();
                    
                    if ($this->deviceGroupId) {
                        $query->where('device_group_id', $this->deviceGroupId);
                    }
                    
                    return $query->pluck('name', 'id')->toArray();
                })
                ->placeholder('Semua Perangkat')
                ->live()
                ->afterStateUpdated(fn() => $this->resetData()),
                
            DatePicker::make('startDate')
                ->label('Tanggal Mulai')
                ->required()
                ->live()
                ->afterStateUpdated(fn() => $this->resetData()),
                
            DatePicker::make('endDate')
                ->label('Tanggal Akhir')
                ->required()
                ->live()
                ->afterStateUpdated(fn() => $this->resetData()),
                
            Select::make('groupBy')
                ->label('Kelompokkan Berdasarkan')
                ->options([
                    'hourly' => 'Per Jam',
                    'daily' => 'Harian',
                    'weekly' => 'Mingguan',
                    'monthly' => 'Bulanan',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn() => $this->resetData()),
        ];
    }
    
    public function resetData()
    {
        $this->chartData = [];
        $this->summaryData = [];
        $this->tableData = [];
    }
    
    public function generateReport()
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reportType' => ['required'],
            'groupBy' => ['required'],
        ]);
        
        // Build query
        $query = SensorData::query()
            ->whereBetween('recorded_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->orderBy('recorded_at');
            
        if ($this->deviceId) {
            $query->where('device_id', $this->deviceId);
        } elseif ($this->deviceGroupId) {
            $query->whereHas('device', function ($q) {
                $q->where('device_group_id', $this->deviceGroupId);
            });
        }
        
        // Execute different report types
        if ($this->reportType === 'pressure') {
            $this->generatePressureReport($query);
        } elseif ($this->reportType === 'flowrate') {
            $this->generateFlowrateReport($query);
        } elseif ($this->reportType === 'battery') {
            $this->generateBatteryReport($query);
        } elseif ($this->reportType === 'alert') {
            $this->generateAlertReport($query);
        }
    }
    
    private function generatePressureReport($query)
    {
        // Copy query for later aggregation
        $aggQuery = clone $query;
        
        // Format for grouping based on selected interval
        $groupFormat = $this->getGroupFormat();
        
        // Get data for selected devices
        $data = $query->select(
            DB::raw("DATE_FORMAT(recorded_at, '{$groupFormat}') as period"),
            'device_id',
            DB::raw('AVG(pressure1) as avg_pressure1'),
            DB::raw('AVG(pressure2) as avg_pressure2'),
            DB::raw('MIN(pressure1) as min_pressure1'),
            DB::raw('MIN(pressure2) as min_pressure2'),
            DB::raw('MAX(pressure1) as max_pressure1'),
            DB::raw('MAX(pressure2) as max_pressure2'),
            DB::raw('COUNT(*) as readings_count')
        )
        ->groupBy('period', 'device_id')
        ->get();
        
        // Get device names to display in chart
        $deviceNames = Device::whereIn('id', $data->pluck('device_id')->unique())
            ->pluck('name', 'id')
            ->toArray();
        
        // Structure data for chart
        $chartLabels = [];
        $chartSeries = [];
        
        // Initialize series structure
        foreach ($deviceNames as $id => $name) {
            $chartSeries["pressure1_{$id}"] = [
                'name' => "{$name} (Pressure 1)",
                'data' => [],
            ];
            $chartSeries["pressure2_{$id}"] = [
                'name' => "{$name} (Pressure 2)",
                'data' => [],
            ];
        }
        
        // Generate date periods
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        $periodInterval = $this->getIntervalForGroupBy();
        $periods = new CarbonPeriod($start, $periodInterval, $end);
        
        // Initialize data structure with zeros for all periods
        foreach ($periods as $date) {
            $formattedDate = $date->format($this->getDateFormat());
            $chartLabels[] = $formattedDate;
            
            foreach ($deviceNames as $id => $name) {
                $chartSeries["pressure1_{$id}"]['data'][$formattedDate] = null;
                $chartSeries["pressure2_{$id}"]['data'][$formattedDate] = null;
            }
        }
        
        // Fill in actual data
        foreach ($data as $record) {
            $period = $record->period;
            $deviceId = $record->device_id;
            
            if (isset($chartSeries["pressure1_{$deviceId}"]['data'][$period])) {
                $chartSeries["pressure1_{$deviceId}"]['data'][$period] = round($record->avg_pressure1, 2);
                $chartSeries["pressure2_{$deviceId}"]['data'][$period] = round($record->avg_pressure2, 2);
            }
        }
        
        // Structure final chart data
        $this->chartData = [
            'labels' => $chartLabels,
            'series' => array_values($chartSeries),
        ];
        
        // Generate summary data
        $summary = $aggQuery->select(
            'device_id',
            DB::raw('AVG(pressure1) as avg_pressure1'),
            DB::raw('AVG(pressure2) as avg_pressure2'),
            DB::raw('MIN(pressure1) as min_pressure1'),
            DB::raw('MIN(pressure2) as min_pressure2'),
            DB::raw('MAX(pressure1) as max_pressure1'),
            DB::raw('MAX(pressure2) as max_pressure2'),
            DB::raw('COUNT(*) as readings_count')
        )
        ->groupBy('device_id')
        ->get();
        
        // Generate table data
        $this->tableData = $data->map(function ($item) use ($deviceNames) {
            return [
                'period' => $item->period,
                'device' => $deviceNames[$item->device_id] ?? "Device #{$item->device_id}",
                'avg_pressure1' => round($item->avg_pressure1, 2) . ' bar',
                'avg_pressure2' => round($item->avg_pressure2, 2) . ' bar',
                'min_pressure1' => round($item->min_pressure1, 2) . ' bar',
                'max_pressure1' => round($item->max_pressure1, 2) . ' bar',
                'readings' => $item->readings_count,
            ];
        })->toArray();
        
        // Generate summary stats
        $this->summaryData = $summary->map(function ($item) use ($deviceNames) {
            return [
                'device' => $deviceNames[$item->device_id] ?? "Device #{$item->device_id}",
                'avg_pressure1' => round($item->avg_pressure1, 2) . ' bar',
                'avg_pressure2' => round($item->avg_pressure2, 2) . ' bar',
                'min_pressure1' => round($item->min_pressure1, 2) . ' bar',
                'max_pressure1' => round($item->max_pressure1, 2) . ' bar',
                'readings' => $item->readings_count,
            ];
        })->toArray();
    }
    
    private function generateFlowrateReport($query)
    {
        // Implementasi laporan flowrate serupa dengan pressure report
        // Namun dengan perubahan pada field yang digunakan (flowrate, totalizer)
        
        // Copy query for later aggregation
        $aggQuery = clone $query;
        
        // Format for grouping based on selected interval
        $groupFormat = $this->getGroupFormat();
        
        // Get data for selected devices
        $data = $query->select(
            DB::raw("DATE_FORMAT(recorded_at, '{$groupFormat}') as period"),
            'device_id',
            DB::raw('AVG(flowrate) as avg_flowrate'),
            DB::raw('MIN(flowrate) as min_flowrate'),
            DB::raw('MAX(flowrate) as max_flowrate'),
            DB::raw('AVG(totalizer) as avg_totalizer'),
            DB::raw('COUNT(*) as readings_count')
        )
        ->groupBy('period', 'device_id')
        ->get();
        
        // Get device names
        $deviceNames = Device::whereIn('id', $data->pluck('device_id')->unique())
            ->pluck('name', 'id')
            ->toArray();
        
        // Structure data for chart
        $chartLabels = [];
        $chartSeries = [];
        
        // Initialize series structure
        foreach ($deviceNames as $id => $name) {
            $chartSeries["flowrate_{$id}"] = [
                'name' => "{$name} (Flowrate)",
                'data' => [],
            ];
        }
        
        // Generate date periods
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        $periodInterval = $this->getIntervalForGroupBy();
        $periods = new CarbonPeriod($start, $periodInterval, $end);
        
        // Initialize data structure with zeros for all periods
        foreach ($periods as $date) {
            $formattedDate = $date->format($this->getDateFormat());
            $chartLabels[] = $formattedDate;
            
            foreach ($deviceNames as $id => $name) {
                $chartSeries["flowrate_{$id}"]['data'][$formattedDate] = null;
            }
        }
        
        // Fill in actual data
        foreach ($data as $record) {
            $period = $record->period;
            $deviceId = $record->device_id;
            
            if (isset($chartSeries["flowrate_{$deviceId}"]['data'][$period])) {
                $chartSeries["flowrate_{$deviceId}"]['data'][$period] = round($record->avg_flowrate, 2);
            }
        }
        
        // Structure final chart data
        $this->chartData = [
            'labels' => $chartLabels,
            'series' => array_values($chartSeries),
        ];
        
        // Generate summary data
        $summary = $aggQuery->select(
            'device_id',
            DB::raw('AVG(flowrate) as avg_flowrate'),
            DB::raw('MIN(flowrate) as min_flowrate'),
            DB::raw('MAX(flowrate) as max_flowrate'),
            DB::raw('AVG(totalizer) as avg_totalizer'),
            DB::raw('COUNT(*) as readings_count')
        )
        ->groupBy('device_id')
        ->get();
        
        // Generate table data
        $this->tableData = $data->map(function ($item) use ($deviceNames) {
            return [
                'period' => $item->period,
                'device' => $deviceNames[$item->device_id] ?? "Device #{$item->device_id}",
                'avg_flowrate' => round($item->avg_flowrate, 2) . ' l/s',
                'min_flowrate' => round($item->min_flowrate, 2) . ' l/s',
                'max_flowrate' => round($item->max_flowrate, 2) . ' l/s',
                'avg_totalizer' => round($item->avg_totalizer, 2) . ' m³',
                'readings' => $item->readings_count,
            ];
        })->toArray();
        
        // Generate summary stats
        $this->summaryData = $summary->map(function ($item) use ($deviceNames) {
            return [
                'device' => $deviceNames[$item->device_id] ?? "Device #{$item->device_id}",
                'avg_flowrate' => round($item->avg_flowrate, 2) . ' l/s',
                'min_flowrate' => round($item->min_flowrate, 2) . ' l/s',
                'max_flowrate' => round($item->max_flowrate, 2) . ' l/s',
                'avg_totalizer' => round($item->avg_totalizer, 2) . ' m³',
                'readings' => $item->readings_count,
            ];
        })->toArray();
    }
    
    private function generateBatteryReport($query)
    {
        // Format for grouping based on selected interval
        $groupFormat = $this->getGroupFormat();
        
        // Get data for selected devices
        $data = $query->select(
            DB::raw("DATE_FORMAT(recorded_at, '{$groupFormat}') as period"),
            'device_id',
            DB::raw('AVG(battery) as avg_battery'),
            DB::raw('MIN(battery) as min_battery'),
            DB::raw('MAX(battery) as max_battery'),
            DB::raw('COUNT(*) as readings_count')
        )
        ->groupBy('period', 'device_id')
        ->get();
        
        // Get device names
        $deviceNames = Device::whereIn('id', $data->pluck('device_id')->unique())
            ->pluck('name', 'id')
            ->toArray();
        
        // Structure data for chart
        $chartLabels = [];
        $chartSeries = [];
        
        // Initialize series structure
        foreach ($deviceNames as $id => $name) {
            $chartSeries["battery_{$id}"] = [
                'name' => "{$name} (Battery)",
                'data' => [],
            ];
        }
        
        // Generate date periods
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        
        $periodInterval = $this->getIntervalForGroupBy();
        $periods = new CarbonPeriod($start, $periodInterval, $end);
        
        // Initialize data structure with zeros for all periods
        foreach ($periods as $date) {
            $formattedDate = $date->format($this->getDateFormat());
            $chartLabels[] = $formattedDate;
            
            foreach ($deviceNames as $id => $name) {
                $chartSeries["battery_{$id}"]['data'][$formattedDate] = null;
            }
        }
        
        // Fill in actual data
        foreach ($data as $record) {
            $period = $record->period;
            $deviceId = $record->device_id;
            
            if (isset($chartSeries["battery_{$deviceId}"]['data'][$period])) {
                $chartSeries["battery_{$deviceId}"]['data'][$period] = round($record->avg_battery, 2);
            }
        }
        
        // Structure final chart data
        $this->chartData = [
            'labels' => $chartLabels,
            'series' => array_values($chartSeries),
        ];
        
        // Generate table data
        $this->tableData = $data->map(function ($item) use ($deviceNames) {
            return [
                'period' => $item->period,
                'device' => $deviceNames[$item->device_id] ?? "Device #{$item->device_id}",
                'avg_battery' => round($item->avg_battery, 2) . ' volt',
                'min_battery' => round($item->min_battery, 2) . ' volt',
                'max_battery' => round($item->max_battery, 2) . ' volt',
                'readings' => $item->readings_count,
            ];
        })->toArray();
        
        // Generate battery health status summaries
        $this->summaryData = $data->groupBy('device_id')->map(function ($items, $deviceId) use ($deviceNames) {
            $latestReading = $items->sortBy('period')->last();
            $avgBattery = $items->avg('avg_battery');
            $status = 'Baik';
            $statusColor = 'green';
            
            // Categorize battery health based on voltage
            if ($avgBattery < 2.5) {
                $status = 'Kritis';
                $statusColor = 'red';
            } elseif ($avgBattery < 3.2) {
                $status = 'Rendah';
                $statusColor = 'yellow';
            }
            
            return [
                'device' => $deviceNames[$deviceId] ?? "Device #{$deviceId}",
                'avg_battery' => round($avgBattery, 2) . ' volt',
                'latest_battery' => round($latestReading->avg_battery, 2) . ' volt',
                'status' => $status,
                'status_color' => $statusColor,
                'trends' => $this->getBatteryTrend($items),
            ];
        })->values()->toArray();
    }
    
    private function generateAlertReport($query)
    {
        // Laporan untuk anomali dan peringatan tekanan
        // Mendefinisikan threshold default untuk tekanan dan flowrate
        $pressureMin = 0.5; // bar
        $pressureMax = 8.0; // bar
        $flowrateMin = -1.0; // l/s
        $flowrateMax = 10.0; // l/s
        $batteryMin = 3.0; // volt
        
        // Filter data yang melebihi threshold
        $alerts = $query->where(function ($q) use ($pressureMin, $pressureMax, $flowrateMin, $flowrateMax, $batteryMin) {
            $q->where('pressure1', '<', $pressureMin)
              ->orWhere('pressure1', '>', $pressureMax)
              ->orWhere('pressure2', '<', $pressureMin)
              ->orWhere('pressure2', '>', $pressureMax)
              ->orWhere('flowrate', '<', $flowrateMin)
              ->orWhere('flowrate', '>', $flowrateMax)
              ->orWhere('battery', '<', $batteryMin);
        })
        ->with('device')
        ->orderBy('recorded_at', 'desc')
        ->get();
        
        // Group alerts by device and type
        $alertsByDevice = $alerts->groupBy('device_id');
        
        // Prepare alert summary by device
        $this->summaryData = $alertsByDevice->map(function ($deviceAlerts, $deviceId) {
            $device = $deviceAlerts->first()->device;
            
            $pressureLowCount = $deviceAlerts->filter(function ($alert) {
                return $alert->pressure1 < 0.5 || $alert->pressure2 < 0.5;
            })->count();
            
            $pressureHighCount = $deviceAlerts->filter(function ($alert) {
                return $alert->pressure1 > 8.0 || $alert->pressure2 > 8.0;
            })->count();
            
            $flowrateAbnormalCount = $deviceAlerts->filter(function ($alert) {
                return $alert->flowrate < -1.0 || $alert->flowrate > 10.0;
            })->count();
            
            $batteryLowCount = $deviceAlerts->filter(function ($alert) {
                return $alert->battery < 3.0;
            })->count();
            
            return [
                'device' => $device->name,
                'total_alerts' => $deviceAlerts->count(),
                'pressure_low' => $pressureLowCount,
                'pressure_high' => $pressureHighCount,
                'flowrate_abnormal' => $flowrateAbnormalCount,
                'battery_low' => $batteryLowCount,
            ];
        })->values()->toArray();
        
        // Prepare detailed alert data for table
        $this->tableData = $alerts->map(function ($alert) {
            $alertType = '';
            $severity = '';
            
            if ($alert->pressure1 < 0.5 || $alert->pressure2 < 0.5) {
                $alertType = 'Tekanan Rendah';
                $severity = 'warning';
            } elseif ($alert->pressure1 > 8.0 || $alert->pressure2 > 8.0) {
                $alertType = 'Tekanan Tinggi';
                $severity = 'danger';
            } elseif ($alert->flowrate < -1.0 || $alert->flowrate > 10.0) {
                $alertType = 'Flowrate Abnormal';
                $severity = 'warning';
            } elseif ($alert->battery < 3.0) {
                $alertType = 'Baterai Rendah';
                $severity = 'info';
            }
            
            return [
                'date' => $alert->recorded_at->format('Y-m-d H:i:s'),
                'device' => $alert->device->name,
                'alert_type' => $alertType,
                'severity' => $severity,
                'pressure1' => round($alert->pressure1, 2) . ' bar',
                'pressure2' => round($alert->pressure2, 2) . ' bar',
                'flowrate' => round($alert->flowrate, 2) . ' l/s',
                'battery' => round($alert->battery, 2) . ' volt',
            ];
        })->toArray();
        
        // Prepare chart data - alerts by day
        $alertsByDay = $alerts->groupBy(function ($item) {
            return $item->recorded_at->format('Y-m-d');
        });
        
        $chartLabels = array_keys($alertsByDay->toArray());
        
        $this->chartData = [
            'labels' => $chartLabels,
            'series' => [
                [
                    'name' => 'Jumlah Alert',
                    'data' => $alertsByDay->map->count()->values()->toArray(),
                ],
            ],
        ];
    }
    
    private function getGroupFormat()
    {
        switch ($this->groupBy) {
            case 'hourly':
                return '%Y-%m-%d %H:00';
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%Y-%u'; // ISO week number
            case 'monthly':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    private function getDateFormat()
    {
        switch ($this->groupBy) {
            case 'hourly':
                return 'Y-m-d H:00';
            case 'daily':
                return 'Y-m-d';
            case 'weekly':
                return 'Y-W'; // ISO week format
            case 'monthly':
                return 'Y-m';
            default:
                return 'Y-m-d';
        }
    }
    
    private function getIntervalForGroupBy()
    {
        switch ($this->groupBy) {
            case 'hourly':
                return '1 hour';
            case 'daily':
                return '1 day';
            case 'weekly':
                return '1 week';
            case 'monthly':
                return '1 month';
            default:
                return '1 day';
        }
    }
    
    private function getBatteryTrend($items)
    {
        // Calculate battery trends
        $sorted = $items->sortBy('period');
        
        if ($sorted->count() < 2) {
            return 'Stabil';
        }
        
        $first = $sorted->first()->avg_battery;
        $last = $sorted->last()->avg_battery;
        
        $diff = $last - $first;
        
        if (abs($diff) < 0.1) {
            return 'Stabil';
        }
        
        return $diff > 0 ? 'Naik' : 'Turun';
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generate Laporan')
                ->action('generateReport'),
                
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    return $this->exportToPdf();
                })
                ->disabled(fn() => empty($this->tableData)),
                
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    return $this->exportToExcel();
                })
                ->disabled(fn() => empty($this->tableData)),
        ];
    }
    
    private function exportToPdf()
    {
        // Export to PDF implementation
        // Would need to add PDF generation package
    }
    
    private function exportToExcel()
    {
        // Export to Excel implementation
        // Would need to add Excel generation package
    }
}