<?php

namespace App\Services;

use App\Models\Report;
use App\Models\SensorData;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportService
{
    public function generateReport(Report $report): array
    {
        $devices = $this->getDevicesForReport($report);

        return match ($report->report_format) {
            'statistical' => $this->generateStatisticalReport($report, $devices),
            'detailed' => $this->generateDetailedReport($report, $devices),
            default => $this->generateSummaryReport($report, $devices),
        };
    }

    private function generateStatisticalReport(Report $report, $devices): array
    {
        $startDate = Carbon::parse($report->start_date);
        $endDate = Carbon::parse($report->end_date);
        $metrics = $report->metrics ?? ['flowrate', 'totalizer'];

        $data = [];

        // Generate data for each date in the range
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
            'data' => $data,
            'metrics' => $metrics,
            'devices' => $devices,
            'summary' => $this->generateOverallSummary($data, $metrics),
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

            // Khusus untuk flowrate, hitung volume
            if ($metric === 'flowrate') {
                $stats['volume'] = [
                    'total_liters' => round($values->sum() * 3600 / $values->count(), 2),
                    'total_m3' => round($values->sum() * 3600 / $values->count() / 1000, 2),
                ];
            }
        }

        return $stats;
    }

    private function generateOverallSummary(array $data, array $metrics): array
    {
        $summary = [];

        foreach ($metrics as $metric) {
            $allValues = [];

            foreach ($data as $dayData) {
                foreach ($dayData['devices'] as $deviceData) {
                    if (isset($deviceData[$metric])) {
                        $allValues[] = $deviceData[$metric]['min'];
                        $allValues[] = $deviceData[$metric]['max'];
                    }
                }
            }

            if (!empty($allValues)) {
                $summary[$metric] = [
                    'overall_min' => min($allValues),
                    'overall_max' => max($allValues),
                    'overall_avg' => round(array_sum($allValues) / count($allValues), 2),
                ];
            }
        }

        return $summary;
    }

    private function generateSummaryReport(Report $report, $devices): array
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

            // Handle jika tidak ada data
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
            'data' => $data,
            'devices' => $devices,
        ];
    }

    private function generateDetailedReport(Report $report, $devices): array
    {
        $startDate = Carbon::parse($report->start_date);
        $endDate = Carbon::parse($report->end_date);

        $data = [];
        foreach ($devices as $device) {
            $sensorData = SensorData::where('device_id', $device->id)
                ->whereBetween('recorded_at', [$startDate, $endDate])
                ->orderBy('recorded_at', 'desc')
                ->limit(1000) // Batasi untuk performa
                ->get();

            $data[] = [
                'device' => $device,
                'sensor_data' => $sensorData
            ];
        }

        return [
            'report' => $report,
            'data' => $data,
            'devices' => $devices,
        ];
    }

    private function getDevicesForReport(Report $report)
    {
        return match ($report->data_source) {
            'all' => Device::all(),
            'device' => Device::where('id', $report->device_id)->get(),
            'group' => Device::where('device_group_id', $report->device_group_id)->get(),
            default => collect(),
        };
    }

    private function generateSimpleHTML(Report $report, array $data): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan ' . htmlspecialchars($report->name) . '</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($report->name) . '</h1>
        <p><strong>Periode:</strong> ' . htmlspecialchars($report->start_date) . ' - ' . htmlspecialchars($report->end_date) . '</p>
        <p><strong>Dibuat pada:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="section">
        <h2>Informasi Laporan</h2>
        <table>
            <tr>
                <th>Informasi</th>
                <th>Nilai</th>
            </tr>
            <tr>
                <td>ID Laporan</td>
                <td>' . $report->id . '</td>
            </tr>
            <tr>
                <td>Nama Laporan</td>
                <td>' . htmlspecialchars($report->name) . '</td>
            </tr>
            <tr>
                <td>Format Laporan</td>
                <td>' . htmlspecialchars($report->report_format) . '</td>
            </tr>
            <tr>
                <td>Sumber Data</td>
                <td>' . htmlspecialchars($report->data_source) . '</td>
            </tr>
        </table>
    </div>';

        // Add device data based on report format
        if ($report->report_format === 'summary' && isset($data['data']) && is_array($data['data'])) {
            $html .= '<div class="section">
            <h2>Ringkasan Data Perangkat</h2>
            <table>
                <tr>
                    <th>No</th>
                    <th>Perangkat</th>
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

        // Add statistical data
        if ($report->report_format === 'statistical' && isset($data['data']) && is_array($data['data'])) {
            $html .= '<div class="section">
            <h2>Data Statistik</h2>';

            foreach ($data['data'] as $dayData) {
                $html .= '<h3>' . $dayData['date'] . ' - ' . $dayData['day_name'] . '</h3>';
                $html .= '<table>
                <tr>
                    <th>Perangkat</th>
                    <th>Data</th>
                </tr>';

                foreach ($dayData['devices'] as $deviceData) {
                    $html .= '<tr>
                    <td>' . htmlspecialchars($deviceData['name']) . '</td>
                    <td>Data tersedia</td>
                </tr>';
                }

                $html .= '</table>';
            }

            $html .= '</div>';
        }

        $html .= '</body></html>';

        return $html;
    }
    public function generatePDF(Report $report, array $data): string
    {
        try {
            // ✅ Pastikan folder reports ada
            $this->ensureReportsDirectoryExists();

            // ✅ Generate simple HTML instead of using view first
            $html = $this->generateSimpleHTML($report, $data);

            Log::info('Starting PDF generation', [
                'report_id' => $report->id,
                'html_length' => strlen($html),
            ]);

            // Generate PDF
            $pdf = Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $fileName = 'report_' . $report->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            $filePath = 'reports/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            Log::info('Generating PDF file', [
                'report_id' => $report->id,
                'file_name' => $fileName,
                'full_path' => $fullPath,
            ]);

            // Generate PDF output
            $pdfOutput = $pdf->output();

            if (empty($pdfOutput)) {
                throw new \Exception('PDF output is empty');
            }

            Log::info('PDF output generated', [
                'report_id' => $report->id,
                'pdf_size' => strlen($pdfOutput),
            ]);

            // ✅ Direct file write (bypass Laravel Storage completely)
            $written = file_put_contents($fullPath, $pdfOutput);

            if ($written === false) {
                throw new \Exception('file_put_contents returned false');
            }

            if ($written === 0) {
                throw new \Exception('file_put_contents wrote 0 bytes');
            }

            // ✅ Verify file exists and has content
            if (!file_exists($fullPath)) {
                throw new \Exception('File does not exist after write: ' . $fullPath);
            }

            $actualFileSize = filesize($fullPath);
            if ($actualFileSize === 0) {
                throw new \Exception('File exists but has 0 bytes');
            }

            Log::info('PDF successfully created', [
                'report_id' => $report->id,
                'file_path' => $filePath,
                'full_path' => $fullPath,
                'bytes_written' => $written,
                'actual_file_size' => $actualFileSize,
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Gagal membuat PDF: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Method untuk memastikan direktori reports ada dan writable
     */
    private function ensureReportsDirectoryExists(): void
    {
        $reportPath = storage_path('app/reports');

        Log::info('Checking reports directory', [
            'path' => $reportPath,
            'exists' => File::exists($reportPath),
            'is_writable' => File::exists($reportPath) ? is_writable($reportPath) : false,
        ]);

        if (!File::exists($reportPath)) {
            Log::info('Creating reports directory');

            // Buat direktori dengan parent directories
            $created = File::makeDirectory($reportPath, 0777, true);

            if (!$created) {
                throw new \Exception('Gagal membuat direktori reports: ' . $reportPath);
            }

            // Set permissions setelah dibuat
            chmod($reportPath, 0777);
        }

        // Verify directory is writable
        if (!is_writable($reportPath)) {
            Log::error('Reports directory is not writable', [
                'path' => $reportPath,
                'permissions' => File::exists($reportPath) ? substr(sprintf('%o', fileperms($reportPath)), -4) : 'N/A',
                'owner' => File::exists($reportPath) ? fileowner($reportPath) : 'N/A',
                'group' => File::exists($reportPath) ? filegroup($reportPath) : 'N/A',
            ]);

            // Try to fix permissions dengan beberapa cara
            if (File::exists($reportPath)) {
                try {
                    chmod($reportPath, 0777);
                    Log::info('Permissions updated to 0777');
                } catch (\Exception $e) {
                    Log::error('Failed to update permissions', ['error' => $e->getMessage()]);
                }
            }

            // Test lagi
            if (!is_writable($reportPath)) {
                throw new \Exception('Directory reports tidak dapat ditulis: ' . $reportPath . ' (permissions: ' . substr(sprintf('%o', fileperms($reportPath)), -4) . ')');
            }
        }

        // Test write dengan file dummy
        $testFile = $reportPath . '/test_write.txt';
        try {
            file_put_contents($testFile, 'test');
            if (File::exists($testFile)) {
                unlink($testFile);
                Log::info('Write test successful');
            }
        } catch (\Exception $e) {
            Log::error('Write test failed', ['error' => $e->getMessage()]);
            throw new \Exception('Tidak dapat menulis ke direktori reports: ' . $e->getMessage());
        }
    }

    public function generateCSV(Report $report, array $data): string
    {
        // ✅ Pastikan folder reports ada
        $this->ensureReportsDirectoryExists();

        $fileName = 'report_' . $report->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'reports/' . $fileName;

        try {
            if ($report->report_format === 'statistical') {
                $this->generateStatisticalCSV($data, $filePath);
            } else {
                $this->generateStandardCSV($data, $filePath);
            }

            return $filePath;
        } catch (\Exception $e) {
            throw new \Exception('Gagal membuat CSV: ' . $e->getMessage());
        }
    }

    private function generateStatisticalCSV(array $data, string $filePath): void
    {
        $csvData = [];
        $headers = ['Tanggal', 'Hari', 'Perangkat'];

        // Add metric headers
        $metrics = $data['metrics'] ?? [];
        foreach ($metrics as $metric) {
            $metricName = match ($metric) {
                'flowrate' => 'Flowrate',
                'pressure1' => 'Tekanan 1',
                'pressure2' => 'Tekanan 2',
                'totalizer' => 'Totalizer',
                'battery' => 'Battery',
                default => ucfirst($metric),
            };

            $headers[] = $metricName . ' (Min)';
            $headers[] = $metricName . ' (Max)';
            $headers[] = $metricName . ' (Avg)';
            $headers[] = $metricName . ' (Total)';
        }

        // Add volume headers if flowrate is included
        if (in_array('flowrate', $metrics)) {
            $headers[] = 'Volume (L)';
            $headers[] = 'Volume (m³)';
        }

        $csvData[] = $headers;

        // Add data rows
        foreach ($data['data'] as $dayData) {
            foreach ($dayData['devices'] as $deviceData) {
                $row = [
                    $dayData['date'],
                    $dayData['day_name'],
                    $deviceData['name'],
                ];

                foreach ($metrics as $metric) {
                    if (isset($deviceData[$metric])) {
                        $row[] = $deviceData[$metric]['min'];
                        $row[] = $deviceData[$metric]['max'];
                        $row[] = $deviceData[$metric]['avg'];
                        $row[] = $deviceData[$metric]['total'];
                    } else {
                        $row[] = 0;
                        $row[] = 0;
                        $row[] = 0;
                        $row[] = 0;
                    }
                }

                // Add volume data if flowrate exists
                if (in_array('flowrate', $metrics) && isset($deviceData['volume'])) {
                    $row[] = $deviceData['volume']['total_liters'];
                    $row[] = $deviceData['volume']['total_m3'];
                } elseif (in_array('flowrate', $metrics)) {
                    $row[] = 0;
                    $row[] = 0;
                }

                $csvData[] = $row;
            }
        }

        // ✅ Write CSV dengan error handling
        $fullPath = storage_path('app/' . $filePath);
        $handle = fopen($fullPath, 'w');

        if ($handle === false) {
            throw new \Exception('Tidak dapat membuka file CSV untuk menulis: ' . $fullPath);
        }

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    private function generateStandardCSV(array $data, string $filePath): void
    {
        $csvData = [];
        $headers = ['Tanggal', 'Perangkat', 'Flowrate Avg', 'Pressure1 Avg', 'Total Records'];
        $csvData[] = $headers;

        foreach ($data['data'] as $item) {
            $row = [
                now()->format('d/m/Y'),
                $item['device']->name,
                $item['statistics']->avg_flowrate ?? 0,
                $item['statistics']->avg_pressure1 ?? 0,
                $item['statistics']->total_records ?? 0,
            ];
            $csvData[] = $row;
        }

        // ✅ Write CSV dengan error handling
        $fullPath = storage_path('app/' . $filePath);
        $handle = fopen($fullPath, 'w');

        if ($handle === false) {
            throw new \Exception('Tidak dapat membuka file CSV untuk menulis: ' . $fullPath);
        }

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}
