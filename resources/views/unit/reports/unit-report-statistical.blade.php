<?php
// Create file: resources/views/reports/unit-report-statistical.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $unitReport->name }} - Statistik</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            margin: 15px; 
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #2563eb; 
            padding-bottom: 15px; 
        }
        .header h1 { 
            color: #2563eb; 
            margin: 0; 
            font-size: 20px; 
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
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
        .stats-grid { 
            display: table; 
            width: 100%; 
            margin-bottom: 15px; 
        }
        .stats-item { 
            display: table-cell; 
            width: 33.33%; 
            padding: 10px; 
            text-align: center; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc;
        }
        .stats-value { 
            font-size: 16px; 
            font-weight: bold; 
            color: #1e40af; 
        }
        .stats-label { 
            font-size: 9px; 
            color: #64748b; 
            margin-top: 5px;
        }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
            font-size: 9px;
        }
        .data-table th { 
            background-color: #2563eb; 
            color: white; 
            padding: 8px 6px; 
            text-align: center; 
            font-size: 8px;
        }
        .data-table td { 
            border: 1px solid #e2e8f0; 
            padding: 6px; 
            text-align: center;
        }
        .data-table tr:nth-child(even) { 
            background-color: #f8fafc; 
        }
        .section-title {
            color: #1e40af;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $unitReport->name }}</h1>
        <p><strong>LAPORAN STATISTIK</strong></p>
        <p>Unit: {{ $unitReport->unit->name ?? 'Unknown Unit' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>Periode</td>
            <td>{{ $unitReport->start_date->format('d F Y') }} - {{ $unitReport->end_date->format('d F Y') }}</td>
        </tr>
        <tr>
            <td>Total Readings</td>
            <td>{{ $data['total_readings'] ?? 0 }} data</td>
        </tr>
        <tr>
            <td>Jumlah Perangkat</td>
            <td>{{ count($data['devices'] ?? []) }} perangkat</td>
        </tr>
        <tr>
            <td>Generated</td>
            <td>{{ $generatedAt->format('d F Y, H:i:s') }}</td>
        </tr>
    </table>

    @if(isset($data['statistics']))
    <div class="section-title">📊 Analisis Statistik</div>
    
    <h4 style="color: #1e40af; margin: 15px 0 10px 0;">🔹 Statistik Tekanan (Bar)</h4>
    <div class="stats-grid">
        <div class="stats-item">
            <div class="stats-value">{{ $data['statistics']['pressure_stats']['mean'] ?? 0 }}</div>
            <div class="stats-label">Mean</div>
        </div>
        <div class="stats-item">
            <div class="stats-value">{{ $data['statistics']['pressure_stats']['median'] ?? 0 }}</div>
            <div class="stats-label">Median</div>
        </div>
        <div class="stats-item">
            <div class="stats-value">{{ $data['statistics']['pressure_stats']['std_dev'] ?? 0 }}</div>
            <div class="stats-label">Std Dev</div>
        </div>
    </div>

    <table class="data-table">
        <tr>
            <th>Count</th>
            <th>Min</th>
            <th>Max</th>
            <th>Mean</th>
            <th>Median</th>
            <th>Std Dev</th>
        </tr>
        <tr>
            <td>{{ $data['statistics']['pressure_stats']['count'] ?? 0 }}</td>
            <td>{{ $data['statistics']['pressure_stats']['min'] ?? 0 }}</td>
            <td>{{ $data['statistics']['pressure_stats']['max'] ?? 0 }}</td>
            <td>{{ $data['statistics']['pressure_stats']['mean'] ?? 0 }}</td>
            <td>{{ $data['statistics']['pressure_stats']['median'] ?? 0 }}</td>
            <td>{{ $data['statistics']['pressure_stats']['std_dev'] ?? 0 }}</td>
        </tr>
    </table>

    <h4 style="color: #1e40af; margin: 15px 0 10px 0;">🔹 Statistik Suhu (°C)</h4>
    <table class="data-table">
        <tr>
            <th>Count</th>
            <th>Min</th>
            <th>Max</th>
            <th>Mean</th>
            <th>Median</th>
            <th>Std Dev</th>
        </tr>
        <tr>
            <td>{{ $data['statistics']['temperature_stats']['count'] ?? 0 }}</td>
            <td>{{ $data['statistics']['temperature_stats']['min'] ?? 0 }}</td>
            <td>{{ $data['statistics']['temperature_stats']['max'] ?? 0 }}</td>
            <td>{{ $data['statistics']['temperature_stats']['mean'] ?? 0 }}</td>
            <td>{{ $data['statistics']['temperature_stats']['median'] ?? 0 }}</td>
            <td>{{ $data['statistics']['temperature_stats']['std_dev'] ?? 0 }}</td>
        </tr>
    </table>

    <h4 style="color: #1e40af; margin: 15px 0 10px 0;">🔹 Statistik Flow Rate (L/min)</h4>
    <table class="data-table">
        <tr>
            <th>Count</th>
            <th>Min</th>
            <th>Max</th>
            <th>Mean</th>
            <th>Median</th>
            <th>Std Dev</th>
        </tr>
        <tr>
            <td>{{ $data['statistics']['flow_rate_stats']['count'] ?? 0 }}</td>
            <td>{{ $data['statistics']['flow_rate_stats']['min'] ?? 0 }}</td>
            <td>{{ $data['statistics']['flow_rate_stats']['max'] ?? 0 }}</td>
            <td>{{ $data['statistics']['flow_rate_stats']['mean'] ?? 0 }}</td>
            <td>{{ $data['statistics']['flow_rate_stats']['median'] ?? 0 }}</td>
            <td>{{ $data['statistics']['flow_rate_stats']['std_dev'] ?? 0 }}</td>
        </tr>
    </table>
    @endif

    @if(isset($data['daily_data']) && count($data['daily_data']) > 0)
    <div class="section-title">📅 Analisis Harian</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jumlah Data</th>
                <th>Avg Pressure</th>
                <th>Avg Temperature</th>
                <th>Avg Flow Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['daily_data'] as $daily)
            <tr>
                <td>{{ \Carbon\Carbon::parse($daily['date'])->format('d/m/Y') }}</td>
                <td>{{ $daily['count'] }}</td>
                <td>{{ $daily['avg_pressure'] }} Bar</td>
                <td>{{ $daily['avg_temperature'] }} °C</td>
                <td>{{ $daily['avg_flow_rate'] }} L/min</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($data['sample_readings']) && count($data['sample_readings']) > 0)
    <div class="section-title">📋 Sample Data</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Device</th>
                <th>Pressure</th>
                <th>Temperature</th>
                <th>Flow Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['sample_readings'] as $reading)
            <tr>
                <td>{{ \Carbon\Carbon::parse($reading['timestamp'])->format('d/m/Y H:i') }}</td>
                <td>{{ $reading['device_name'] }}</td>
                <td>{{ $reading['pressure_value'] }} Bar</td>
                <td>{{ $reading['temperature_value'] }} °C</td>
                <td>{{ $reading['flow_rate'] }} L/min</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="position: fixed; bottom: 15px; right: 15px; font-size: 8px; color: #64748b;">
        Generated: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>