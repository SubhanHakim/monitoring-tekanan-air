{{-- filepath: resources/views/reports/detailed.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Detail - {{ $report->name }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 15px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 { 
            color: #333; 
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 4px; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .number { 
            text-align: right; 
        }
        .device-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .device-header {
            background-color: #e6f3ff;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #0066cc;
        }
        .device-header h3 {
            margin: 0;
            color: #0066cc;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report->name }} - Laporan Detail</h1>
        <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($report->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($report->end_date)->format('d/m/Y') }}</p>
        <p><strong>Dibuat pada:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    @foreach($data as $deviceIndex => $deviceData)
        @if($deviceIndex > 0)
            <div class="page-break"></div>
        @endif
        
        <div class="device-section">
            <div class="device-header">
                <h3>{{ $deviceData['device']->name }}</h3>
                <p>Total Records: {{ $deviceData['sensor_data']->count() }}</p>
            </div>
            
            @if($deviceData['sensor_data']->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Flowrate</th>
                            <th>Pressure1</th>
                            <th>Pressure2</th>
                            <th>Totalizer</th>
                            <th>Battery</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deviceData['sensor_data'] as $index => $sensor)
                        <tr>
                            <td class="number">{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($sensor->recorded_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="number">{{ number_format($sensor->flowrate, 2) }}</td>
                            <td class="number">{{ number_format($sensor->pressure1, 2) }}</td>
                            <td class="number">{{ number_format($sensor->pressure2, 2) }}</td>
                            <td class="number">{{ number_format($sensor->totalizer, 2) }}</td>
                            <td class="number">{{ number_format($sensor->battery, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Tidak ada data untuk perangkat ini dalam periode yang dipilih.</p>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Monitoring Tekanan Air</p>
    </div>
</body>
</html>