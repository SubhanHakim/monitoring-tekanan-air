<?php

namespace App\Services;

use App\Models\Report;
use App\Models\SensorData;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    /**
     * Generate report data based on report settings
     */
    public function generateReport(Report $report): array
    {
        // Tentukan rentang tanggal
        $startDate = $report->start_date ?? now()->subDays(30);
        $endDate = $report->end_date ?? now();

        // Buat query dasar
        $query = SensorData::query()
            ->whereBetween('recorded_at', [$startDate, $endDate]);

        // Filter berdasarkan device atau device group
        if ($report->device_id) {
            $query->where('device_id', $report->device_id);
        } elseif ($report->device_group_id) {
            $query->whereHas('device', function ($q) use ($report) {
                $q->where('device_group_id', $report->device_group_id);
            });
        }

        // Data untuk laporan
        $result = [
            'report_info' => [
                'name' => $report->name,
                'type' => $report->type,
                'description' => $report->description,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ],
            'summary' => [],
            'daily_data' => [],
            'hourly_data' => [],
        ];

        // Ringkasan statistik
        $summary = $query->selectRaw('
            COUNT(*) as total_readings,
            COUNT(DISTINCT device_id) as device_count,
            MIN(recorded_at) as first_reading,
            MAX(recorded_at) as last_reading,
            AVG(flowrate) as avg_flowrate,
            MIN(flowrate) as min_flowrate,
            MAX(flowrate) as max_flowrate,
            STDDEV(flowrate) as stddev_flowrate,
            AVG(battery) as avg_battery,
            MIN(battery) as min_battery,
            MAX(battery) as max_battery,
            STDDEV(battery) as stddev_battery,
            AVG(pressure1) as avg_pressure1,
            MIN(pressure1) as min_pressure1,
            MAX(pressure1) as max_pressure1,
            STDDEV(pressure1) as stddev_pressure1,
            AVG(pressure2) as avg_pressure2,
            MIN(pressure2) as min_pressure2,
            MAX(pressure2) as max_pressure2,
            STDDEV(pressure2) as stddev_pressure2
        ')->first()->toArray();

        $result['summary'] = $summary;

        // Data harian
        $dailyData = $query->selectRaw('
            DATE(recorded_at) as date,
            COUNT(*) as readings_count,
            AVG(flowrate) as avg_flowrate,
            AVG(battery) as avg_battery,
            AVG(pressure1) as avg_pressure1,
            AVG(pressure2) as avg_pressure2
        ')
            ->groupBy(DB::raw('DATE(recorded_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'readings_count' => $item->readings_count,
                    'avg_flowrate' => round($item->avg_flowrate, 2),
                    'avg_battery' => round($item->avg_battery, 2),
                    'avg_pressure1' => round($item->avg_pressure1, 2),
                    'avg_pressure2' => round($item->avg_pressure2, 2),
                ];
            })
            ->toArray();

        $result['daily_data'] = $dailyData;

        // Data untuk chart
        $hourlyData = $query->selectRaw('
            DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00") as hour,
            AVG(flowrate) as avg_flowrate,
            AVG(battery) as avg_battery,
            AVG(pressure1) as avg_pressure1,
            AVG(pressure2) as avg_pressure2
        ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour,
                    'avg_flowrate' => round($item->avg_flowrate, 2),
                    'avg_battery' => round($item->avg_battery, 2),
                    'avg_pressure1' => round($item->avg_pressure1, 2),
                    'avg_pressure2' => round($item->avg_pressure2, 2),
                ];
            })
            ->toArray();

        $result['hourly_data'] = $hourlyData;

        // Analisis lama waktu device aktif/tidak aktif (jika parameter diaktifkan)
        if (isset($report->parameters['analyze_uptime']) && $report->parameters['analyze_uptime']) {
            $uptimeData = $this->calculateUptime($report, $startDate, $endDate);
            $result['uptime_analysis'] = $uptimeData;
        }

        // Deteksi anomali (jika parameter diaktifkan)
        if (
            isset($report->parameters['detect_anomalies']) && $report->parameters['detect_anomalies'] ||
            isset($report->parameters['include_anomalies']) && $report->parameters['include_anomalies']
        ) {
            $anomalies = $this->detectAnomalies($query, $report);
            $result['anomalies'] = $anomalies;
        }

        // Ambil sampel data untuk ditampilkan di laporan
        $result['samples'] = $query->with('device')
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get();

        return $result;
    }

    /**
     * Generate PDF from report data
     */
    public function generatePDF(Report $report, array $data): string
    {
        // Generate PDF menggunakan data dan template
        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'data' => $data,
        ]);

        // Simpan PDF ke storage
        $filename = 'report_' . $report->id . '_' . now()->format('YmdHis') . '.pdf';
        $path = 'reports/' . $filename;

        Storage::put($path, $pdf->output());

        // Update report dengan informasi file
        $report->update([
            'last_generated_at' => now(),
            'last_generated_file' => $path,
        ]);

        return $path;
    }

    private function calculateUptime(Report $report, $startDate, $endDate): array
    {
        $result = [];

        if ($report->device_id) {
            $device = $report->device;
            $lastRecording = null;
            $downtime = 0;
            $uptime = 0;

            // Ambil semua data yang sudah diurutkan berdasarkan waktu
            $data = SensorData::where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startDate, $endDate])
                ->orderBy('recorded_at')
                ->get(['recorded_at']);

            // Jika tidak ada data, return 0% uptime
            if ($data->isEmpty()) {
                return [
                    'device_id' => $device->id,
                    'device_name' => $device->name,
                    'total_uptime_hours' => 0,
                    'uptime_percentage' => 0,
                    'status_changes' => [],
                ];
            }

            // Hitung berdasarkan jarak antar data
            $statusChanges = [];
            foreach ($data as $index => $reading) {
                if ($index > 0 && $lastRecording !== null && $reading->recorded_at !== null) {
                    // Pastikan recorded_at adalah objek Carbon
                    $currentTime = $reading->recorded_at instanceof Carbon
                        ? $reading->recorded_at
                        : Carbon::parse($reading->recorded_at);

                    $lastTime = $lastRecording instanceof Carbon
                        ? $lastRecording
                        : Carbon::parse($lastRecording);

                    $timeDiff = $currentTime->diffInMinutes($lastTime);

                    // Jika jarak > 30 menit, dianggap downtime
                    if ($timeDiff > 30) {
                        $downtime += $timeDiff;
                        $statusChanges[] = [
                            'from' => $lastTime->format('Y-m-d H:i:s'),
                            'to' => $currentTime->format('Y-m-d H:i:s'),
                            'status' => 'down',
                            'duration_minutes' => $timeDiff,
                        ];
                    } else {
                        $uptime += $timeDiff;
                    }
                }
                $lastRecording = $reading->recorded_at;
            }

            // Pastikan startDate dan endDate adalah objek Carbon
            $startDateCarbon = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
            $endDateCarbon = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

            $totalTimeMinutes = $startDateCarbon->diffInMinutes($endDateCarbon);
            $uptimePercentage = $totalTimeMinutes > 0 ? round(($uptime / $totalTimeMinutes) * 100, 2) : 0;

            $result = [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'total_uptime_hours' => round($uptime / 60, 2),
                'uptime_percentage' => $uptimePercentage,
                'status_changes' => $statusChanges,
            ];
        }

        return $result;
    }

private function detectAnomalies($query, Report $report): array
{
    $anomalies = [];
    $parameters = $report->parameters ?? [];

    // Ambil data referensi untuk menentukan nilai rata-rata dan std dev
    $baselineData = clone $query;
    $baselineStats = $baselineData->selectRaw('
        AVG(flowrate) as avg_flowrate,
        STDDEV(flowrate) as std_flowrate,
        AVG(pressure1) as avg_pressure1,
        STDDEV(pressure1) as std_pressure1,
        AVG(pressure2) as avg_pressure2,
        STDDEV(pressure2) as std_pressure2,
        AVG(battery) as avg_battery,
        STDDEV(battery) as std_battery
    ')->first();

    // Jika tidak ada data, kembalikan array kosong
    if (!$baselineStats) {
        return [];
    }

    // Tentukan threshold untuk anomali (biasanya 2 atau 3 std dev)
    $threshold = $parameters['anomaly_threshold'] ?? 2.0;
    if (is_string($threshold)) {
        $threshold = (float) $threshold;
    }

    $anomalyResults = collect();

    // Cek flowrate anomalies jika rate tidak null dan std dev lebih dari 0
    if (isset($baselineStats->avg_flowrate) && $baselineStats->avg_flowrate !== null && 
        isset($baselineStats->std_flowrate) && $baselineStats->std_flowrate > 0) {
        
        $flowrateAnomalies = clone $query;
        $flowrateAnomalies = $flowrateAnomalies
            ->whereNotNull('flowrate')
            ->where(function ($q) use ($baselineStats, $threshold) {
                $q->where('flowrate', '>', $baselineStats->avg_flowrate + ($threshold * $baselineStats->std_flowrate))
                    ->orWhere('flowrate', '<', $baselineStats->avg_flowrate - ($threshold * $baselineStats->std_flowrate));
            })
            ->with('device')
            ->orderBy('recorded_at')
            ->limit(20)
            ->get();

        if ($flowrateAnomalies->count() > 0) {
            foreach ($flowrateAnomalies as $item) {
                if (!isset($item->flowrate) || $item->flowrate === null) {
                    continue;
                }
                
                $deviation = ($item->flowrate - $baselineStats->avg_flowrate) / $baselineStats->std_flowrate;
                
                // Hindari pembagian dengan nol
                $deviationPercent = 0;
                if ($baselineStats->avg_flowrate != 0) {
                    $deviationPercent = abs(($item->flowrate - $baselineStats->avg_flowrate) / $baselineStats->avg_flowrate) * 100;
                }

                $anomalyResults->push([
                    'recorded_at' => $item->recorded_at,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device->name ?? 'Unknown',
                    'parameter' => 'flowrate',
                    'value' => $item->flowrate,
                    'avg_value' => $baselineStats->avg_flowrate,
                    'deviation' => $deviation,
                    'deviation_percent' => $deviationPercent,
                ]);
            }
        }
    }

    // Cek pressure1 anomalies
    if (isset($baselineStats->avg_pressure1) && $baselineStats->avg_pressure1 !== null && 
        isset($baselineStats->std_pressure1) && $baselineStats->std_pressure1 > 0) {
        
        $pressure1Anomalies = clone $query;
        $pressure1Anomalies = $pressure1Anomalies
            ->whereNotNull('pressure1')
            ->where(function ($q) use ($baselineStats, $threshold) {
                $q->where('pressure1', '>', $baselineStats->avg_pressure1 + ($threshold * $baselineStats->std_pressure1))
                    ->orWhere('pressure1', '<', $baselineStats->avg_pressure1 - ($threshold * $baselineStats->std_pressure1));
            })
            ->with('device')
            ->orderBy('recorded_at')
            ->limit(20)
            ->get();

        if ($pressure1Anomalies->count() > 0) {
            foreach ($pressure1Anomalies as $item) {
                if (!isset($item->pressure1) || $item->pressure1 === null) {
                    continue;
                }
                
                $deviation = ($item->pressure1 - $baselineStats->avg_pressure1) / $baselineStats->std_pressure1;
                
                // Hindari pembagian dengan nol
                $deviationPercent = 0;
                if ($baselineStats->avg_pressure1 != 0) {
                    $deviationPercent = abs(($item->pressure1 - $baselineStats->avg_pressure1) / $baselineStats->avg_pressure1) * 100;
                }

                $anomalyResults->push([
                    'recorded_at' => $item->recorded_at,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device->name ?? 'Unknown',
                    'parameter' => 'pressure1',
                    'value' => $item->pressure1,
                    'avg_value' => $baselineStats->avg_pressure1,
                    'deviation' => $deviation,
                    'deviation_percent' => $deviationPercent,
                ]);
            }
        }
    }

    // Cek pressure2 anomalies
    if (isset($baselineStats->avg_pressure2) && $baselineStats->avg_pressure2 !== null && 
        isset($baselineStats->std_pressure2) && $baselineStats->std_pressure2 > 0) {
        
        $pressure2Anomalies = clone $query;
        $pressure2Anomalies = $pressure2Anomalies
            ->whereNotNull('pressure2')
            ->where(function ($q) use ($baselineStats, $threshold) {
                $q->where('pressure2', '>', $baselineStats->avg_pressure2 + ($threshold * $baselineStats->std_pressure2))
                    ->orWhere('pressure2', '<', $baselineStats->avg_pressure2 - ($threshold * $baselineStats->std_pressure2));
            })
            ->with('device')
            ->orderBy('recorded_at')
            ->limit(20)
            ->get();

        if ($pressure2Anomalies->count() > 0) {
            foreach ($pressure2Anomalies as $item) {
                if (!isset($item->pressure2) || $item->pressure2 === null) {
                    continue;
                }
                
                $deviation = ($item->pressure2 - $baselineStats->avg_pressure2) / $baselineStats->std_pressure2;
                
                // Hindari pembagian dengan nol
                $deviationPercent = 0;
                if ($baselineStats->avg_pressure2 != 0) {
                    $deviationPercent = abs(($item->pressure2 - $baselineStats->avg_pressure2) / $baselineStats->avg_pressure2) * 100;
                }

                $anomalyResults->push([
                    'recorded_at' => $item->recorded_at,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device->name ?? 'Unknown',
                    'parameter' => 'pressure2',
                    'value' => $item->pressure2,
                    'avg_value' => $baselineStats->avg_pressure2,
                    'deviation' => $deviation,
                    'deviation_percent' => $deviationPercent,
                ]);
            }
        }
    }

    // Cek battery anomalies
    if (isset($baselineStats->avg_battery) && $baselineStats->avg_battery !== null && 
        isset($baselineStats->std_battery) && $baselineStats->std_battery > 0) {
        
        $batteryAnomalies = clone $query;
        $batteryAnomalies = $batteryAnomalies
            ->whereNotNull('battery')
            ->where(function ($q) use ($baselineStats, $threshold) {
                $q->where('battery', '>', $baselineStats->avg_battery + ($threshold * $baselineStats->std_battery))
                    ->orWhere('battery', '<', $baselineStats->avg_battery - ($threshold * $baselineStats->std_battery));
            })
            ->with('device')
            ->orderBy('recorded_at')
            ->limit(20)
            ->get();

        if ($batteryAnomalies->count() > 0) {
            foreach ($batteryAnomalies as $item) {
                if (!isset($item->battery) || $item->battery === null) {
                    continue;
                }
                
                $deviation = ($item->battery - $baselineStats->avg_battery) / $baselineStats->std_battery;
                
                // Hindari pembagian dengan nol
                $deviationPercent = 0;
                if ($baselineStats->avg_battery != 0) {
                    $deviationPercent = abs(($item->battery - $baselineStats->avg_battery) / $baselineStats->avg_battery) * 100;
                }

                $anomalyResults->push([
                    'recorded_at' => $item->recorded_at,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device->name ?? 'Unknown',
                    'parameter' => 'battery',
                    'value' => $item->battery,
                    'avg_value' => $baselineStats->avg_battery,
                    'deviation' => $deviation,
                    'deviation_percent' => $deviationPercent,
                ]);
            }
        }
    }

    // Urutkan berdasarkan deviasi tertinggi
    return $anomalyResults->sortByDesc('deviation')->values()->toArray();
}
}
