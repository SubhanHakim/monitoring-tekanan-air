<?php

namespace App\Services;

use App\Models\UnitReport;
use App\Models\SensorData;  // ✅ GANTI Reading dengan SensorData
use App\Models\Device;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnitReportService
{
    public function generateReport(UnitReport $unitReport): array
    {
        Log::info('Generating unit report:', [
            'unit_report_id' => $unitReport->id,
            'unit_id' => $unitReport->unit_id,
            'report_format' => $unitReport->report_format,
            'start_date' => $unitReport->start_date,
            'end_date' => $unitReport->end_date,
        ]);

        // ✅ QUERY SENSOR DATA untuk Unit
        $query = SensorData::query()  // ✅ GANTI Reading dengan SensorData
            ->with(['device', 'device.unit'])
            ->whereBetween('created_at', [
                $unitReport->start_date->startOfDay(),
                $unitReport->end_date->endOfDay()
            ]);

        // ✅ FILTER by Unit ID
        if ($unitReport->unit_id) {
            $query->whereHas('device', function ($q) use ($unitReport) {
                $q->where('unit_id', $unitReport->unit_id);
            });
        }

        // ✅ FILTER by Device jika spesifik
        if ($unitReport->data_source === 'device' && $unitReport->device_id) {
            $query->where('device_id', $unitReport->device_id);
        }

        $sensorData = $query->orderBy('created_at', 'desc')->get();

        Log::info('Sensor data found:', [
            'count' => $sensorData->count(),
            'report_format' => $unitReport->report_format,
            'first_data' => $sensorData->first()?->toArray(),
        ]);

        // ✅ FORMAT DATA BERDASARKAN REPORT FORMAT
        return $this->formatReportData($unitReport, $sensorData);
    }

    private function formatReportData(UnitReport $unitReport, $sensorData): array
    {
        // ✅ BASE DATA yang sama untuk semua format
        $baseData = [
            'total_readings' => $sensorData->count(),
            'period_start' => $unitReport->start_date,
            'period_end' => $unitReport->end_date,
            'devices' => $sensorData->pluck('device.name')->unique()->values()->toArray(),
        ];

        // ✅ FORMAT BERDASARKAN REPORT FORMAT
        switch ($unitReport->report_format) {
            case 'summary':
                return $this->formatSummaryReport($unitReport, $sensorData, $baseData);
            
            case 'detailed':
                return $this->formatDetailedReport($unitReport, $sensorData, $baseData);
            
            case 'statistical':
                return $this->formatStatisticalReport($unitReport, $sensorData, $baseData);
            
            default:
                return $this->formatSummaryReport($unitReport, $sensorData, $baseData);
        }
    }

    // ✅ FORMAT SUMMARY REPORT
    private function formatSummaryReport(UnitReport $unitReport, $sensorData, $baseData): array
    {
        // ✅ SESUAIKAN dengan field yang ada di SensorData
        $summary = [
            'total_readings' => $sensorData->count(),
            'avg_pressure' => round($sensorData->avg('pressure') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
            'min_pressure' => round($sensorData->min('pressure') ?? 0, 2),
            'max_pressure' => round($sensorData->max('pressure') ?? 0, 2),
            'avg_temperature' => round($sensorData->avg('temperature') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
            'min_temperature' => round($sensorData->min('temperature') ?? 0, 2),
            'max_temperature' => round($sensorData->max('temperature') ?? 0, 2),
            'avg_flow_rate' => round($sensorData->avg('flow_rate') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
            'min_flow_rate' => round($sensorData->min('flow_rate') ?? 0, 2),
            'max_flow_rate' => round($sensorData->max('flow_rate') ?? 0, 2),
        ];

        // ✅ LIMIT readings untuk summary (hanya 10 terakhir)
        $limitedReadings = $sensorData->take(10)->map(function ($data) {
            return [
                'timestamp' => $data->created_at,
                'device_name' => $data->device->name ?? 'Unknown Device',
                'device_location' => $data->device->location ?? '-',
                'pressure_value' => $data->pressure ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'temperature_value' => $data->temperature ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'flow_rate' => $data->flow_rate ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'status' => $data->status ?? 'normal',
            ];
        })->toArray();

        return array_merge($baseData, [
            'format' => 'summary',
            'summary' => $summary,
            'readings' => $limitedReadings,
        ]);
    }

    // ✅ FORMAT DETAILED REPORT
    private function formatDetailedReport(UnitReport $unitReport, $sensorData, $baseData): array
    {
        // ✅ SEMUA sensor data untuk detailed
        $detailedReadings = $sensorData->map(function ($data) {
            return [
                'timestamp' => $data->created_at,
                'device_name' => $data->device->name ?? 'Unknown Device',
                'device_location' => $data->device->location ?? '-',
                'pressure_value' => $data->pressure ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'temperature_value' => $data->temperature ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'flow_rate' => $data->flow_rate ?? 0,  // ✅ SESUAIKAN FIELD NAME
                'status' => $data->status ?? 'normal',
                'device_id' => $data->device_id,
                'reading_id' => $data->id,
            ];
        })->toArray();

        // ✅ DETAILED SUMMARY per device
        $deviceSummary = $sensorData->groupBy('device_id')->map(function ($deviceData) {
            $device = $deviceData->first()->device;
            return [
                'device_name' => $device->name ?? 'Unknown Device',
                'device_location' => $device->location ?? '-',
                'total_readings' => $deviceData->count(),
                'avg_pressure' => round($deviceData->avg('pressure') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_temperature' => round($deviceData->avg('temperature') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_flow_rate' => round($deviceData->avg('flow_rate') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'min_pressure' => round($deviceData->min('pressure') ?? 0, 2),
                'max_pressure' => round($deviceData->max('pressure') ?? 0, 2),
            ];
        })->values()->toArray();

        return array_merge($baseData, [
            'format' => 'detailed',
            'readings' => $detailedReadings,
            'device_summary' => $deviceSummary,
        ]);
    }

    // ✅ FORMAT STATISTICAL REPORT
    private function formatStatisticalReport(UnitReport $unitReport, $sensorData, $baseData): array
    {
        // ✅ STATISTICAL ANALYSIS
        $pressureData = $sensorData->pluck('pressure')->filter()->values();  // ✅ SESUAIKAN FIELD NAME
        $temperatureData = $sensorData->pluck('temperature')->filter()->values();  // ✅ SESUAIKAN FIELD NAME
        $flowRateData = $sensorData->pluck('flow_rate')->filter()->values();  // ✅ SESUAIKAN FIELD NAME

        $statistics = [
            'pressure_stats' => [
                'count' => $pressureData->count(),
                'mean' => round($pressureData->avg() ?? 0, 2),
                'median' => round($pressureData->median() ?? 0, 2),
                'min' => round($pressureData->min() ?? 0, 2),
                'max' => round($pressureData->max() ?? 0, 2),
                'std_dev' => round($this->calculateStandardDeviation($pressureData->toArray()) ?? 0, 2),
            ],
            'temperature_stats' => [
                'count' => $temperatureData->count(),
                'mean' => round($temperatureData->avg() ?? 0, 2),
                'median' => round($temperatureData->median() ?? 0, 2),
                'min' => round($temperatureData->min() ?? 0, 2),
                'max' => round($temperatureData->max() ?? 0, 2),
                'std_dev' => round($this->calculateStandardDeviation($temperatureData->toArray()) ?? 0, 2),
            ],
            'flow_rate_stats' => [
                'count' => $flowRateData->count(),
                'mean' => round($flowRateData->avg() ?? 0, 2),
                'median' => round($flowRateData->median() ?? 0, 2),
                'min' => round($flowRateData->min() ?? 0, 2),
                'max' => round($flowRateData->max() ?? 0, 2),
                'std_dev' => round($this->calculateStandardDeviation($flowRateData->toArray()) ?? 0, 2),
            ],
        ];

        // ✅ HOURLY ANALYSIS
        $hourlyData = $sensorData->groupBy(function ($data) {
            return $data->created_at->format('Y-m-d H:00');
        })->map(function ($hourlyData, $hour) {
            return [
                'hour' => $hour,
                'count' => $hourlyData->count(),
                'avg_pressure' => round($hourlyData->avg('pressure') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_temperature' => round($hourlyData->avg('temperature') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_flow_rate' => round($hourlyData->avg('flow_rate') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
            ];
        })->values()->toArray();

        // ✅ DAILY ANALYSIS
        $dailyData = $sensorData->groupBy(function ($data) {
            return $data->created_at->format('Y-m-d');
        })->map(function ($dailyData, $date) {
            return [
                'date' => $date,
                'count' => $dailyData->count(),
                'avg_pressure' => round($dailyData->avg('pressure') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_temperature' => round($dailyData->avg('temperature') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
                'avg_flow_rate' => round($dailyData->avg('flow_rate') ?? 0, 2),  // ✅ SESUAIKAN FIELD NAME
            ];
        })->values()->toArray();

        return array_merge($baseData, [
            'format' => 'statistical',
            'statistics' => $statistics,
            'hourly_data' => $hourlyData,
            'daily_data' => $dailyData,
            'sample_readings' => $sensorData->take(5)->map(function ($data) {
                return [
                    'timestamp' => $data->created_at,
                    'device_name' => $data->device->name ?? 'Unknown Device',
                    'pressure_value' => $data->pressure ?? 0,  // ✅ SESUAIKAN FIELD NAME
                    'temperature_value' => $data->temperature ?? 0,  // ✅ SESUAIKAN FIELD NAME
                    'flow_rate' => $data->flow_rate ?? 0,  // ✅ SESUAIKAN FIELD NAME
                ];
            })->toArray(),
        ]);
    }

    // ✅ HELPER FUNCTION untuk Standard Deviation
    private function calculateStandardDeviation(array $data): float
    {
        if (empty($data)) return 0;
        
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data)) / count($data);
        
        return sqrt($variance);
    }

    // ✅ generatePDF method tetap sama
    public function generatePDF(UnitReport $unitReport, array $data): string
    {
        Log::info('Generating PDF for unit report:', [
            'report_id' => $unitReport->id,
            'format' => $unitReport->report_format,
            'data_format' => $data['format'] ?? 'unknown',
            'data_keys' => array_keys($data),
        ]);

        try {
            $templateName = match($unitReport->report_format) {
                'summary' => 'reports.unit-report-summary',
                'detailed' => 'reports.unit-report-detailed', 
                'statistical' => 'reports.unit-report-statistical',
                default => 'reports.unit-report-summary',
            };

            $pdf = PDF::loadView($templateName, [
                'unitReport' => $unitReport,
                'data' => $data,
                'generatedAt' => now(),
            ]);

            $pdf->setPaper('A4', 'portrait');

            $fileName = 'unit-report-' . $unitReport->report_format . '-' . $unitReport->id . '-' . time() . '.pdf';
            $filePath = 'unit-reports/' . $fileName;
            
            Storage::put($filePath, $pdf->output());

            Log::info('PDF generated successfully:', [
                'file_path' => $filePath,
                'template' => $templateName,
                'file_size' => Storage::size($filePath),
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('PDF generation failed:', [
                'error' => $e->getMessage(),
                'format' => $unitReport->report_format,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}