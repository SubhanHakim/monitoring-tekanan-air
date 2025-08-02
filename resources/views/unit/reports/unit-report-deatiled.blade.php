<?php
// Create file: resources/views/reports/unit-report-detailed.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $unitReport->name }} - Detail</title>
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
        .header p { 
            color: #64748b; 
            margin: 5px 0; 
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
        .info-table td:nth-child(2) { 
            background-color: #f8fafc; 
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
            padding: 8px 4px; 
            text-align: left; 
            font-size: 8px;
            font-weight: bold;
        }
        .data-table td { 
            border: 1px solid #e2e8f0; 
            padding: 4px; 
            text-align: left;
        }
        .data-table tr:nth-child(even) { 
            background-color: #f8fafc; 
        }
        .device-section { 
            margin-bottom: 20px; 
            page-break-inside: avoid; 
        }
        .device-header { 
            background-color: #e2e8f0; 
            padding: 8px; 
            font-weight: bold; 
            margin-bottom: 10px;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            margin: 20px 0;
        }
        .no-data h3 {
            color: #92400e;
            margin-top: 0;
        }
        .footer {
            position: fixed;
            bottom: 15px;
            right: 15px;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $unitReport->name }}</h1>
        <p><strong>LAPORAN DETAIL</strong></p>
        <p>Unit: {{ $unitReport->unit->name ?? 'Unknown Unit' }}</p>
        @if($unitReport->description)
            <p>{{ $unitReport->description }}</p>
        @endif
    </div>

    <div class="info-section">
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
                <td>Jumlah Perangkat</td>
                <td>{{ count($data['devices'] ?? []) }} perangkat</td>
            </tr>
            <tr>
                <td>Dibuat pada</td>
                <td>{{ $generatedAt->format('d F Y, H:i:s') }}</td>
            </tr>
        </table>
    </div>

    @if(isset($data['device_summary']) && count($data['device_summary']) > 0)
    <div class="device-section">
        <h3>📊 Ringkasan per Perangkat</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%">Perangkat</th>
                    <th style="width: 15%">Lokasi</th>
                    <th style="width: 10%">Total Data</th>
                    <th style="width: 13%">Avg Pressure</th>
                    <th style="width: 13%">Avg Temperature</th>
                    <th style="width: 13%">Avg Flow Rate</th>
                    <th style="width: 8%">Min Press</th>
                    <th style="width: 8%">Max Press</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['device_summary'] as $device)
                <tr>
                    <td><strong>{{ $device['device_name'] }}</strong></td>
                    <td>{{ $device['device_location'] }}</td>
                    <td style="text-align: center;">{{ $device['total_readings'] }}</td>
                    <td style="text-align: right;">{{ $device['avg_pressure'] }} Bar</td>
                    <td style="text-align: right;">{{ $device['avg_temperature'] }} °C</td>
                    <td style="text-align: right;">{{ $device['avg_flow_rate'] }} L/min</td>
                    <td style="text-align: right;">{{ $device['min_pressure'] }}</td>
                    <td style="text-align: right;">{{ $device['max_pressure'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($data['readings']) && count($data['readings']) > 0)
    <div class="device-section">
        <h3>📋 Data Readings Detail ({{ count($data['readings']) }} records)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%">Tanggal & Waktu</th>
                    <th style="width: 18%">Perangkat</th>
                    <th style="width: 15%">Lokasi</th>
                    <th style="width: 13%">Pressure (Bar)</th>
                    <th style="width: 13%">Temperature (°C)</th>
                    <th style="width: 13%">Flow Rate (L/min)</th>
                    <th style="width: 13%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['readings'] as $reading)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($reading['timestamp'])->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $reading['device_name'] }}</strong></td>
                    <td>{{ $reading['device_location'] }}</td>
                    <td style="text-align: right;">{{ number_format($reading['pressure_value'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($reading['temperature_value'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($reading['flow_rate'], 2) }}</td>
                    <td style="text-align: center;">
                        <span style="
                            padding: 2px 4px;
                            border-radius: 2px;
                            font-size: 7px;
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
        <h3>⚠️ Tidak Ada Data Detail</h3>
        <p>Tidak ada data readings untuk periode yang dipilih.</p>
        <div style="margin-top: 15px; font-size: 9px; color: #64748b;">
            <strong>Debug Info:</strong><br>
            Unit ID: {{ $unitReport->unit_id }}<br>
            Start Date: {{ $unitReport->start_date->format('d/m/Y') }}<br>
            End Date: {{ $unitReport->end_date->format('d/m/Y') }}<br>
            Data Source: {{ $unitReport->data_source }}<br>
            Device ID: {{ $unitReport->device_id ?? 'All devices' }}
        </div>
    </div>
    @endif

    <div class="footer">
        Generated by Monitoring System - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>