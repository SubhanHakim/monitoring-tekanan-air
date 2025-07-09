<?php

namespace App\Services;

use App\Models\UnitReport;
use App\Models\Unit;
use App\Models\Device;
use App\Models\SensorData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;

class UnitReportService
{
    public function generateReport(UnitReport $report): array
    {
        $devices = $this->getDevicesForReport($report);

        return match ($report->report_format) {
            'statistical' => $this->generateStatisticalReport($report, $devices),
            'detailed' => $this->generateDetailedReport($report, $devices),
            default => $this->generateSummaryReport($report, $devices),
        };
    }

    private function getDevicesForReport(UnitReport $report)
    {
        return match ($report->data_source) {
            'all' => Device::where('unit_id', $report->unit_id)->get(),
            'device' => Device::where('id', $report->device_id)
                ->where('unit_id', $report->unit_id)->get(),
            'group' => Device::where('device_group_id', $report->device_group_id)
                ->where('unit_id', $report->unit_id)->get(),
            default => collect(),
        };
    }

    private function generateSummaryReport(UnitReport $report, $devices): array
    {
        $startDate = Carbon::parse($report->start_date);
        $endDate = Carbon::parse($report->end_date);

        $data = [];
        foreach ($devices as $device) {
            $deviceData = SensorData::where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_records,
                    AVG(flowrate) as avg_flowrate,
                    MAX(flowrate) as max_flowrate,
                    MIN(flowrate) as min_flowrate,
                    AVG(pressure1) as avg_pressure1,
                    MAX(pressure1) as max_pressure1,
                    MIN(pressure1) as min_pressure1
                ')
                ->first();

            if (!$deviceData || $deviceData->total_records == 0) {
                $deviceData = (object) [
                    'total_records' => 0,
                    'avg_flowrate' => 0,
                    'max_flowrate' => 0,
                    'min_flowrate' => 0,
                    'avg_pressure1' => 0,
                    'max_pressure1' => 0,
                    'min_pressure1' => 0,
                ];
            }

            $data[] = [
                'device' => $device,
                'statistics' => $deviceData
            ];
        }

        return [
            'report' => $report,
            'unit' => $report->unit,
            'data' => $data,
            'devices' => $devices,
        ];
    }

    private function generateDetailedReport(UnitReport $report, $devices): array
    {
        $startDate = Carbon::parse($report->start_date);
        $endDate = Carbon::parse($report->end_date);

        $data = [];
        foreach ($devices as $device) {
            $sensorData = SensorData::where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startDate, $endDate])
                ->orderBy('recorded_at', 'desc')
                ->limit(1000)
                ->get();

            $data[] = [
                'device' => $device,
                'sensor_data' => $sensorData
            ];
        }

        return [
            'report' => $report,
            'unit' => $report->unit,
            'data' => $data,
            'devices' => $devices,
        ];
    }

    private function generateStatisticalReport(UnitReport $report, $devices): array
    {
        $startDate = Carbon::parse($report->start_date);
        $endDate = Carbon::parse($report->end_date);
        $metrics = $report->metrics ?? ['flowrate', 'totalizer'];

        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayData = [
                'date' => $currentDate->format('d/m/Y'),
                'day_name' => $currentDate->format('l'),
                'devices' => []
            ];

            foreach ($devices as $device) {
                $deviceData = $this->getDeviceStatisticsForDate($device, $currentDate, $metrics);
                $dayData['devices'][$device->id] = array_merge(
                    ['name' => $device->name],
                    $deviceData
                );
            }

            $data[] = $dayData;
            $currentDate->addDay();
        }

        return [
            'report' => $report,
            'unit' => $report->unit,
            'data' => $data,
            'metrics' => $metrics,
            'devices' => $devices,
        ];
    }

    private function getDeviceStatisticsForDate(Device $device, Carbon $date, array $metrics): array
    {
        $query = SensorData::where('device_id', $device->id)
            ->whereDate('recorded_at', $date);

        $stats = [];

        foreach ($metrics as $metric) {
            $values = $query->pluck($metric)->filter()->values();

            if ($values->isEmpty()) {
                $stats[$metric] = [
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0,
                    'total' => 0,
                    'count' => 0,
                ];
                continue;
            }

            $stats[$metric] = [
                'min' => round($values->min(), 2),
                'max' => round($values->max(), 2),
                'avg' => round($values->avg(), 2),
                'total' => round($values->sum(), 2),
                'count' => $values->count(),
            ];

            if ($metric === 'flowrate') {
                $stats['volume'] = [
                    'total_liters' => round($values->sum() * 3600 / $values->count(), 2),
                    'total_m3' => round($values->sum() * 3600 / $values->count() / 1000, 2),
                ];
            }
        }

        return $stats;
    }

    public function generatePDF(UnitReport $report, array $data): string
    {
        try {
            $this->ensureReportsDirectoryExists();

            $html = $this->generateUnitHTML($report, $data);

            Log::info('Starting Unit PDF generation', [
                'unit_report_id' => $report->id,
                'unit_id' => $report->unit_id,
                'html_length' => strlen($html),
            ]);

            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $fileName = 'unit_report_' . $report->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            $filePath = 'reports/units/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            // Ensure unit reports directory exists
            $unitReportsDir = storage_path('app/reports/units');
            if (!is_dir($unitReportsDir)) {
                mkdir($unitReportsDir, 0777, true);
            }

            $pdfOutput = $pdf->output();

            if (empty($pdfOutput)) {
                throw new \Exception('PDF output is empty');
            }

            $written = file_put_contents($fullPath, $pdfOutput);

            if ($written === false || !file_exists($fullPath)) {
                throw new \Exception('Gagal menyimpan PDF unit report');
            }

            Log::info('Unit PDF successfully created', [
                'unit_report_id' => $report->id,
                'file_path' => $filePath,
                'file_size' => filesize($fullPath),
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Unit PDF generation failed', [
                'unit_report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Gagal membuat PDF unit report: ' . $e->getMessage());
        }
    }

    private function generateUnitHTML(UnitReport $report, array $data): string
    {
        $unit = $report->unit;

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Unit - ' . htmlspecialchars($unit->name) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 { 
            color: #333; 
            margin: 0 0 10px 0;
        }
        .unit-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
        }
        .number { 
            text-align: right; 
        }
        .section { 
            margin: 20px 0; 
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .status.active {
            background-color: #28a745;
        }
        .status.inactive {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Unit: ' . htmlspecialchars($unit->name) . '</h1>
        <p><strong>Laporan:</strong> ' . htmlspecialchars($report->name) . '</p>
        <p><strong>Periode:</strong> ' . htmlspecialchars($report->start_date) . ' - ' . htmlspecialchars($report->end_date) . '</p>
        <p><strong>Dibuat pada:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="unit-info">
        <h3>Informasi Unit</h3>
        <table>
            <tr>
                <th>Nama Unit</th>
                <td>' . htmlspecialchars($unit->name) . '</td>
            </tr>
            <tr>
                <th>Lokasi</th>
                <td>' . htmlspecialchars($unit->location) . '</td>
            </tr>
            <tr>
                <th>Deskripsi</th>
                <td>' . htmlspecialchars($unit->description ?? '-') . '</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="status ' . $unit->status . '">
                        ' . ($unit->status === 'active' ? 'Aktif' : 'Tidak Aktif') . '
                    </span>
                </td>
            </tr>
        </table>
    </div>';

        // Add device data
        if (isset($data['data']) && is_array($data['data'])) {
            $html .= '<div class="section">
            <h2>Data Perangkat Unit</h2>
            <table>
                <tr>
                    <th>No</th>
                    <th>Nama Perangkat</th>
                    <th>Total Records</th>
                    <th>Flowrate Avg</th>
                    <th>Pressure1 Avg</th>
                </tr>';

            foreach ($data['data'] as $index => $item) {
                $deviceName = isset($item['device']) ? $item['device']->name : 'Unknown';
                $stats = isset($item['statistics']) ? $item['statistics'] : null;

                $html .= '<tr>
                <td class="number">' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($deviceName) . '</td>
                <td class="number">' . ($stats ? number_format($stats->total_records) : '0') . '</td>
                <td class="number">' . ($stats ? number_format($stats->avg_flowrate, 2) : '0.00') . '</td>
                <td class="number">' . ($stats ? number_format($stats->avg_pressure1, 2) : '0.00') . '</td>
            </tr>';
            }

            $html .= '</table></div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function ensureReportsDirectoryExists(): void
    {
        $reportPath = storage_path('app/reports/units');

        if (!is_dir($reportPath)) {
            mkdir($reportPath, 0777, true);
        }

        if (!is_writable($reportPath)) {
            throw new \Exception('Directory unit reports tidak dapat ditulis: ' . $reportPath);
        }
    }
}
