<!-- filepath: resources/views/reports/pdf.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->name ?? 'Laporan Monitoring' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.5;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        h3 {
            font-size: 16px;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        .header {
            margin-bottom: 20px;
        }

        .meta {
            font-size: 11px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .stat-box {
            flex: 1 0 calc(25% - 20px);
            margin: 10px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .stat-label {
            font-size: 11px;
            color: #555;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }

        .anomaly {
            background-color: #ffeded;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        
        .chart-container {
            margin: 20px 0;
            text-align: center;
        }
        
        .empty-data {
            padding: 20px;
            text-align: center;
            font-style: italic;
            color: #999;
            border: 1px dashed #ddd;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $report->name ?? 'Laporan Monitoring' }}</h1>
        <div class="meta">
            Tipe: 
            {{ match($report->type ?? 'custom') {
                'daily' => 'Harian',
                'weekly' => 'Mingguan',
                'monthly' => 'Bulanan',
                default => 'Kustom',
            } }}
            |
            Periode: {{ isset($report->start_date) ? $report->start_date->format('d/m/Y') : 'N/A' }} - 
            {{ isset($report->end_date) ? $report->end_date->format('d/m/Y') : 'N/A' }} | 
            Dibuat: 
            {{ isset($report->last_generated_at) ? $report->last_generated_at->format('d M Y H:i') : now()->format('d M Y H:i') }}
        </div>
        @if(isset($report->description) && $report->description)
            <p>{{ $report->description }}</p>
        @endif
    </div>

    <h2>Informasi Perangkat</h2>
    <table>
        <tr>
            <th>Perangkat</th>
            <td>{{ isset($report->device) ? $report->device->name : (isset($report->deviceGroup) ? $report->deviceGroup->name : 'Semua Perangkat') }}</td>
        </tr>
        <tr>
            <th>Total Pembacaan</th>
            <td>{{ isset($data) && isset($data['summary']) && isset($data['summary']['total_readings']) ? number_format($data['summary']['total_readings']) : '0' }}</td>
        </tr>
        <tr>
            <th>Jumlah Perangkat</th>
            <td>{{ isset($data) && isset($data['summary']) && isset($data['summary']['device_count']) ? $data['summary']['device_count'] : '0' }}</td>
        </tr>
        <tr>
            <th>Periode Data</th>
            <td>
                {{ isset($data) && isset($data['summary']) && isset($data['summary']['first_reading']) ? \Carbon\Carbon::parse($data['summary']['first_reading'])->format('d M Y H:i:s') : 'N/A' }}
                -
                {{ isset($data) && isset($data['summary']) && isset($data['summary']['last_reading']) ? \Carbon\Carbon::parse($data['summary']['last_reading'])->format('d M Y H:i:s') : 'N/A' }}
            </td>
        </tr>
    </table>

    <h2>Ringkasan Statistik</h2>
    @if(isset($data) && isset($data['summary']))
    <table>
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Flowrate</th>
                <th>Battery</th>
                <th>Pressure 1</th>
                <th>Pressure 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Minimum</th>
                <td>{{ isset($data['summary']['min_flowrate']) ? number_format($data['summary']['min_flowrate'], 2) . ' l/s' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['min_battery']) ? number_format($data['summary']['min_battery'], 2) . ' v' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['min_pressure1']) ? number_format($data['summary']['min_pressure1'], 2) . ' bar' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['min_pressure2']) ? number_format($data['summary']['min_pressure2'], 2) . ' bar' : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Rata-rata</th>
                <td>{{ isset($data['summary']['avg_flowrate']) ? number_format($data['summary']['avg_flowrate'], 2) . ' l/s' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['avg_battery']) ? number_format($data['summary']['avg_battery'], 2) . ' v' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['avg_pressure1']) ? number_format($data['summary']['avg_pressure1'], 2) . ' bar' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['avg_pressure2']) ? number_format($data['summary']['avg_pressure2'], 2) . ' bar' : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Maksimum</th>
                <td>{{ isset($data['summary']['max_flowrate']) ? number_format($data['summary']['max_flowrate'], 2) . ' l/s' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['max_battery']) ? number_format($data['summary']['max_battery'], 2) . ' v' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['max_pressure1']) ? number_format($data['summary']['max_pressure1'], 2) . ' bar' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['max_pressure2']) ? number_format($data['summary']['max_pressure2'], 2) . ' bar' : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Std Deviasi</th>
                <td>{{ isset($data['summary']['stddev_flowrate']) ? number_format($data['summary']['stddev_flowrate'], 2) . ' l/s' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['stddev_battery']) ? number_format($data['summary']['stddev_battery'], 2) . ' v' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['stddev_pressure1']) ? number_format($data['summary']['stddev_pressure1'], 2) . ' bar' : 'N/A' }}</td>
                <td>{{ isset($data['summary']['stddev_pressure2']) ? number_format($data['summary']['stddev_pressure2'], 2) . ' bar' : 'N/A' }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="empty-data">
        Tidak ada data statistik yang tersedia untuk periode ini
    </div>
    @endif

    @if(isset($data) && isset($data['anomalies']) && is_array($data['anomalies']) && count($data['anomalies']) > 0)
    <h2>Anomali Terdeteksi</h2>
    <p>Berikut adalah daftar pembacaan sensor yang menyimpang signifikan dari nilai rata-rata (±2 standar deviasi):</p>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Perangkat</th>
                <th>Parameter</th>
                <th>Nilai</th>
                <th>Rata-rata</th>
                <th>% Deviasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['anomalies'] as $anomaly)
            <tr class="anomaly">
                <td>{{ isset($anomaly['recorded_at']) ? \Carbon\Carbon::parse($anomaly['recorded_at'])->format('d/m/Y H:i:s') : 'N/A' }}</td>
                <td>{{ $anomaly['device_name'] ?? 'N/A' }}</td>
                <td>
                    {{ match($anomaly['parameter'] ?? '') {
                        'flowrate' => 'Flowrate',
                        'battery' => 'Battery',
                        'pressure1' => 'Pressure 1',
                        'pressure2' => 'Pressure 2',
                        default => $anomaly['parameter'] ?? 'N/A',
                    } }}
                </td>
                <td>
                    {{ isset($anomaly['value']) ? number_format($anomaly['value'], 2) : 'N/A' }}
                    {{ match($anomaly['parameter'] ?? '') {
                        'flowrate' => 'l/s',
                        'battery' => 'v',
                        'pressure1' => 'bar',
                        'pressure2' => 'bar',
                        default => '',
                    } }}
                </td>
                <td>
                    {{ isset($anomaly['avg_value']) ? number_format($anomaly['avg_value'], 2) : 'N/A' }}
                    {{ match($anomaly['parameter'] ?? '') {
                        'flowrate' => 'l/s',
                        'battery' => 'v',
                        'pressure1' => 'bar',
                        'pressure2' => 'bar',
                        default => '',
                    } }}
                </td>
                <td>{{ isset($anomaly['deviation_percent']) ? number_format($anomaly['deviation_percent'], 1) . '%' : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @elseif(isset($data) && isset($data['summary']) && isset($data['summary']['total_readings']) && $data['summary']['total_readings'] > 0)
    <h2>Anomali Terdeteksi</h2>
    <div class="empty-data">
        Tidak terdeteksi anomali pada periode ini
    </div>
    @endif

    <!-- Halaman baru untuk data sampel -->
    <div class="page-break"></div>

    <h2>Sampel Data</h2>
    @if(isset($data) && isset($data['samples']) && is_array($data['samples']) && count($data['samples']) > 0)
    <p>Berikut adalah sampel {{ count($data['samples']) }} pembacaan terbaru:</p>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Perangkat</th>
                <th>Flowrate</th>
                <th>Battery</th>
                <th>Pressure 1</th>
                <th>Pressure 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['samples'] as $sample)
            <tr>
                <td>{{ isset($sample->recorded_at) ? $sample->recorded_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                <td>{{ isset($sample->device) && isset($sample->device->name) ? $sample->device->name : 'N/A' }}</td>
                <td>{{ isset($sample->flowrate) ? number_format($sample->flowrate, 2) . ' l/s' : 'N/A' }}</td>
                <td>{{ isset($sample->battery) ? number_format($sample->battery, 2) . ' v' : 'N/A' }}</td>
                <td>{{ isset($sample->pressure1) ? number_format($sample->pressure1, 2) . ' bar' : 'N/A' }}</td>
                <td>{{ isset($sample->pressure2) ? number_format($sample->pressure2, 2) . ' bar' : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty-data">
        Tidak ada data sampel yang tersedia untuk periode ini
    </div>
    @endif

    <!-- Bagian Kesimpulan -->
    @if(isset($data) && isset($data['summary']) && isset($data['summary']['total_readings']) && $data['summary']['total_readings'] > 0)
    <h2>Kesimpulan</h2>
    <p>
        Berdasarkan analisis data untuk periode {{ isset($report->start_date) ? $report->start_date->format('d/m/Y') : 'N/A' }} hingga 
        {{ isset($report->end_date) ? $report->end_date->format('d/m/Y') : 'N/A' }}, ditemukan:
    </p>
    <ul>
        <li>Total {{ isset($data['summary']['total_readings']) ? number_format($data['summary']['total_readings']) : '0' }} pembacaan dari {{ $data['summary']['device_count'] ?? '0' }} perangkat</li>
        <li>Tekanan air rata-rata adalah {{ isset($data['summary']['avg_pressure1']) ? number_format($data['summary']['avg_pressure1'], 2) . ' bar' : 'N/A' }} (Pressure 1)</li>
        <li>Flow rate rata-rata adalah {{ isset($data['summary']['avg_flowrate']) ? number_format($data['summary']['avg_flowrate'], 2) . ' l/s' : 'N/A' }}</li>
        @if(isset($data['anomalies']) && is_array($data['anomalies']) && count($data['anomalies']) > 0)
        <li>Terdeteksi {{ count($data['anomalies']) }} anomali yang memerlukan perhatian</li>
        @else
        <li>Tidak terdeteksi anomali yang signifikan dalam periode ini</li>
        @endif
    </ul>
    @endif

    <h3>Catatan</h3>
    <p>Laporan ini dibuat secara otomatis berdasarkan data sensor yang dikumpulkan selama periode yang ditentukan. 
    Semua perhitungan statistik dilakukan dengan menggunakan algoritma standar.</p>
    
    <div class="footer">
        <p>Sistem Monitoring Tekanan Air &copy; {{ date('Y') }} | Dihasilkan pada: {{ now()->format('d M Y H:i:s') }}</p>
    </div>
</body>
</html>