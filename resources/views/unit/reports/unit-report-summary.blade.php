<?php
// Create file: resources/views/reports/unit-report-summary.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $unitReport->name }} - Ringkasan</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 20px; 
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #2563eb; 
            padding-bottom: 20px; 
        }
        .header h1 { 
            color: #2563eb; 
            margin: 0; 
            font-size: 24px; 
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        .info-table td { 
            padding: 8px 12px; 
            border: 1px solid #e2e8f0; 
        }
        .info-table td:first-child { 
            font-weight: bold; 
            background-color: #f1f5f9; 
            width: 25%; 
        }
        .summary-stats { 
            margin-bottom: 20px; 
        }
        .stats-grid { 
            display: table; 
            width: 100%; 
            margin-bottom: 20px; 
        }
        .stats-item { 
            display: table-cell; 
            width: 25%; 
            padding: 15px; 
            text-align: center; 
            border: 1px solid #e2e8f0; 
        }
        .stats-value { 
            font-size: 18px; 
            font-weight: bold; 
            color: #1e40af; 
        }
        .stats-label { 
            font-size: 10px; 
            color: #64748b; 
            margin-top: 5px; 
        }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        .data-table th { 
            background-color: #2563eb; 
            color: white; 
            padding: 12px 8px; 
            text-align: left; 
            font-weight: bold; 
        }
        .data-table td { 
            border: 1px solid #e2e8f0; 
            padding: 8px; 
            text-align: left; 
        }
        .data-table tr:nth-child(even) { 
            background-color: #f8fafc; 
        }
        .no-data { 
            text-align: center; 
            padding: 40px; 
            background-color: #fef3c7; 
            border: 1px solid #f59e0b; 
            border-radius: 8px; 
            margin: 20px 0; 
        }
        .footer { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            font-size: 10px; 
            color: #64748b; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $unitReport->name }}</h1>
        <p><strong>LAPORAN RINGKASAN</strong></p>
        <p>Unit: {{ $unitReport->unit->name ?? 'Unknown Unit' }}</p>
        @if($unitReport->description)
            <p>{{ $unitReport->description }}</p>
        @endif
    </div>

    <table class="info-table">
        <tr>
            <td>Periode Laporan</td>
            <td>{{ $unitReport->start_date->format('d F Y') }} - {{ $unitReport->end_date->format('d F Y') }}</td>
        </tr>
        <tr>
            <td>Format Laporan</td>
            <td>{{ ucfirst($unitReport->report_format) }}</td>
        </tr>
        <tr>
            <td>Sumber Data</td>
            <td>
                @if($unitReport->data_source === 'all')
                    Semua Perangkat
                @elseif($unitReport->data_source === 'device')
                    Perangkat Spesifik: {{ $unitReport->device->name ?? 'Unknown Device' }}
                @else
                    {{ ucfirst($unitReport->data_source) }}
                @endif
            </td>
        </tr>
        <tr>
            <td>Total Data</td>
            <td>{{ $data['total_readings'] ?? 0 }} readings</td>
        </tr>
        <tr>
            <td>Dibuat pada</td>
            <td>{{ $generatedAt->format('d F Y, H:i:s') }}</td>
        </tr>
    </table>

    @if(isset($data['summary']) && $data['summary']['total_readings'] > 0)
    <div class="summary-stats">
        <h3>📊 Ringkasan Statistik</h3>
        
        <div class="stats-grid">
            <div class="stats-item" style="background-color: #dbeafe;">
                <div class="stats-value">{{ $data['summary']['total_readings'] ?? 0 }}</div>
                <div class="stats-label">Total Readings</div>
            </div>
            <div class="stats-item" style="background-color: #dcfce7;">
                <div class="stats-value">{{ $data['summary']['avg_pressure'] ?? 0 }}</div>
                <div class="stats-label">Avg Pressure (Bar)</div>
            </div>
            <div class="stats-item" style="background-color: #fef3c7;">
                <div class="stats-value">{{ $data['summary']['avg_temperature'] ?? 0 }}</div>
                <div class="stats-label">Avg Temperature (°C)</div>
            </div>
            <div class="stats-item" style="background-color: #e0e7ff;">
                <div class="stats-value">{{ $data['summary']['avg_flow_rate'] ?? 0 }}</div>
                <div class="stats-label">Avg Flow Rate (L/min)</div>
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td>Tekanan Minimum</td>
                <td>{{ $data['summary']['min_pressure'] ?? 0 }} Bar</td>
                <td>Tekanan Maximum</td>
                <td>{{ $data['summary']['max_pressure'] ?? 0 }} Bar</td>
            </tr>
            <tr>
                <td>Suhu Minimum</td>
                <td>{{ $data['summary']['min_temperature'] ?? 0 }} °C</td>
                <td>Suhu Maximum</td>
                <td>{{ $data['summary']['max_temperature'] ?? 0 }} °C</td>
            </tr>
            <tr>
                <td>Flow Rate Minimum</td>
                <td>{{ $data['summary']['min_flow_rate'] ?? 0 }} L/min</td>
                <td>Flow Rate Maximum</td>
                <td>{{ $data['summary']['max_flow_rate'] ?? 0 }} L/min</td>
            </tr>
        </table>
    </div>
    @endif

    @if(isset($data['readings']) && count($data['readings']) > 0)
    <div>
        <h3>📋 Sample Data Readings (10 Terbaru)</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal & Waktu</th>
                    <th>Perangkat</th>
                    <th>Pressure (Bar)</th>
                    <th>Temperature (°C)</th>
                    <th>Flow Rate (L/min)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['readings'] as $reading)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($reading['timestamp'])->format('d/m/Y H:i') }}</td>
                    <td>{{ $reading['device_name'] }}</td>
                    <td style="text-align: right;">{{ number_format($reading['pressure_value'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($reading['temperature_value'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($reading['flow_rate'], 2) }}</td>
                    <td>
                        <span style="
                            padding: 2px 6px;
                            border-radius: 4px;
                            font-size: 10px;
                            font-weight: bold;
                            background-color: {{ $reading['status'] === 'normal' ? '#dcfce7' : '#fecaca' }};
                            color: {{ $reading['status'] === 'normal' ? '#166534' : '#dc2626' }};
                        ">
                            {{ strtoupper($reading['status']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="no-data">
        <h3>⚠️ Tidak Ada Data Readings</h3>
        <p>Tidak ada data readings untuk periode yang dipilih.</p>
    </div>
    @endif

    <div class="footer">
        Generated by Monitoring System - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>