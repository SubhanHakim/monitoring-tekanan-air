{{-- filepath: resources/views/reports/statistical.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Statistik - {{ $report->name }}</title>
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
            page-break-inside: avoid;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 5px; 
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
        .date-header {
            background-color: #e6f3ff;
            font-weight: bold;
            text-align: center;
        }
        .summary-section {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .summary-section h3 {
            margin-top: 0;
            color: #333;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report->name }} - Laporan Statistik</h1>
        <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($report->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($report->end_date)->format('d/m/Y') }}</p>
        <p><strong>Dibuat pada:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Metrik:</strong> {{ implode(', ', $metrics) }}</p>
    </div>

    {{-- Summary Section --}}
    @if(isset($summary))
    <div class="summary-section">
        <h3>Ringkasan Keseluruhan</h3>
        <table>
            <thead>
                <tr>
                    <th>Metrik</th>
                    <th>Minimum</th>
                    <th>Maximum</th>
                    <th>Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary as $metric => $stats)
                <tr>
                    <td>{{ ucfirst($metric) }}</td>
                    <td class="number">{{ number_format($stats['overall_min'], 2) }}</td>
                    <td class="number">{{ number_format($stats['overall_max'], 2) }}</td>
                    <td class="number">{{ number_format($stats['overall_avg'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Daily Data --}}
    @foreach($data as $dayIndex => $dayData)
        @if($dayIndex > 0)
            <div class="page-break"></div>
        @endif
        
        <h3>{{ $dayData['date'] }} - {{ $dayData['day_name'] }}</h3>
        
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Perangkat</th>
                    @foreach($metrics as $metric)
                        <th colspan="4">{{ ucfirst($metric) }}</th>
                    @endforeach
                    @if(in_array('flowrate', $metrics))
                        <th colspan="2">Volume</th>
                    @endif
                </tr>
                <tr>
                    @foreach($metrics as $metric)
                        <th>Min</th>
                        <th>Max</th>
                        <th>Avg</th>
                        <th>Total</th>
                    @endforeach
                    @if(in_array('flowrate', $metrics))
                        <th>Liter</th>
                        <th>m³</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($dayData['devices'] as $deviceId => $deviceData)
                <tr>
                    <td>{{ $deviceData['name'] }}</td>
                    @foreach($metrics as $metric)
                        @if(isset($deviceData[$metric]))
                            <td class="number">{{ number_format($deviceData[$metric]['min'], 2) }}</td>
                            <td class="number">{{ number_format($deviceData[$metric]['max'], 2) }}</td>
                            <td class="number">{{ number_format($deviceData[$metric]['avg'], 2) }}</td>
                            <td class="number">{{ number_format($deviceData[$metric]['total'], 2) }}</td>
                        @else
                            <td class="number">0</td>
                            <td class="number">0</td>
                            <td class="number">0</td>
                            <td class="number">0</td>
                        @endif
                    @endforeach
                    @if(in_array('flowrate', $metrics))
                        @if(isset($deviceData['volume']))
                            <td class="number">{{ number_format($deviceData['volume']['total_liters'], 2) }}</td>
                            <td class="number">{{ number_format($deviceData['volume']['total_m3'], 2) }}</td>
                        @else
                            <td class="number">0</td>
                            <td class="number">0</td>
                        @endif
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Monitoring Tekanan Air</p>
    </div>
</body>
</html>