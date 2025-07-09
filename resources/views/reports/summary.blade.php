{{-- filepath: resources/views/reports/summary.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Monitoring Tekanan Air - {{ $report->name }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 0;
            padding: 20px;
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
            font-size: 18px;
        }
        .header p { 
            margin: 5px 0; 
            color: #666;
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
            text-align: center;
        }
        .number { 
            text-align: right; 
        }
        .summary { 
            margin: 20px 0; 
        }
        .summary h2 { 
            color: #333; 
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report->name }}</h1>
        <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($report->start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($report->end_date)->format('d/m/Y') }}</p>
        <p><strong>Dibuat pada:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Jenis Laporan:</strong> Ringkasan</p>
    </div>

    <div class="summary">
        <h2>Ringkasan Data Perangkat</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Perangkat</th>
                    <th>Total Records</th>
                    <th>Flowrate Avg</th>
                    <th>Flowrate Max</th>
                    <th>Flowrate Min</th>
                    <th>Pressure1 Avg</th>
                    <th>Pressure1 Max</th>
                    <th>Pressure1 Min</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $item)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td>{{ $item['device']->name }}</td>
                    <td class="number">{{ number_format($item['statistics']->total_records) }}</td>
                    <td class="number">{{ number_format($item['statistics']->avg_flowrate, 2) }}</td>
                    <td class="number">{{ number_format($item['statistics']->max_flowrate, 2) }}</td>
                    <td class="number">{{ number_format($item['statistics']->min_flowrate, 2) }}</td>
                    <td class="number">{{ number_format($item['statistics']->avg_pressure1, 2) }}</td>
                    <td class="number">{{ number_format($item['statistics']->max_pressure1, 2) }}</td>
                    <td class="number">{{ number_format($item['statistics']->min_pressure1, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Monitoring Tekanan Air</p>
    </div>
</body>
</html>