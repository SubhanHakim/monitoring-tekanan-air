<!-- filepath: resources/views/filament/admin/resources/report-resource/pages/view-report.blade.php -->
<x-filament::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="p-6 bg-white rounded-lg shadow">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ $record->name }}</h1>
                    <div class="mt-1 text-gray-500">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                            @switch($record->type)
                                @case('daily')
                                    Harian
                                    @break
                                @case('weekly')
                                    Mingguan
                                    @break
                                @case('monthly')
                                    Bulanan
                                    @break
                                @default
                                    Kustom
                            @endswitch
                        </span>
                        <span class="ml-2">
                            Periode: {{ $record->start_date->format('d/m/Y') }} - {{ $record->end_date->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex flex-col items-end">
                    <div class="text-sm text-gray-500">Terakhir dibuat: {{ $record->last_generated_at?->format('d M Y H:i') }}</div>
                    <div class="text-sm text-gray-500">oleh: {{ $record->creator?->name }}</div>
                </div>
            </div>
            
            @if($record->description)
                <div class="mt-4 p-4 bg-gray-50 rounded-md">
                    <p class="text-sm text-gray-700">{{ $record->description }}</p>
                </div>
            @endif
            
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-blue-50 rounded-md">
                    <div class="text-sm font-medium text-blue-800">Perangkat</div>
                    <div class="mt-1 text-xl font-semibold">
                        {{ $record->device?->name ?? $record->deviceGroup?->name ?? 'Semua Perangkat' }}
                    </div>
                </div>
                
                <div class="p-4 bg-green-50 rounded-md">
                    <div class="text-sm font-medium text-green-800">Total Pembacaan</div>
                    <div class="mt-1 text-xl font-semibold">
                        {{ number_format($record->data['summary']['total_readings'] ?? 0) }}
                    </div>
                </div>
                
                <div class="p-4 bg-amber-50 rounded-md">
                    <div class="text-sm font-medium text-amber-800">Jumlah Perangkat</div>
                    <div class="mt-1 text-xl font-semibold">
                        {{ $record->data['summary']['device_count'] ?? 0 }}
                    </div>
                </div>
                
                <div class="p-4 bg-indigo-50 rounded-md">
                    <div class="text-sm font-medium text-indigo-800">Rata-rata Tekanan 1</div>
                    <div class="mt-1 text-xl font-semibold">
                        {{ number_format($record->data['summary']['avg_pressure1'] ?? 0, 2) }} bar
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        @if(isset($record->parameters['include_charts']) && $record->parameters['include_charts'])
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Grafik Data Sensor</h2>
                
                <div class="mb-6">
                    <div class="mb-2 text-sm font-medium text-gray-700">Flowrate</div>
                    <div class="h-80" id="flowrateChart"></div>
                </div>
                
                <div class="mb-6">
                    <div class="mb-2 text-sm font-medium text-gray-700">Battery</div>
                    <div class="h-80" id="batteryChart"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-2 text-sm font-medium text-gray-700">Pressure 1</div>
                        <div class="h-80" id="pressure1Chart"></div>
                    </div>
                    
                    <div>
                        <div class="mb-2 text-sm font-medium text-gray-700">Pressure 2</div>
                        <div class="h-80" id="pressure2Chart"></div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Summary Stats -->
        @if(isset($record->parameters['include_summary']) && $record->parameters['include_summary'])
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Ringkasan Statistik</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Parameter</th>
                                <th class="px-6 py-3">Flowrate</th>
                                <th class="px-6 py-3">Battery</th>
                                <th class="px-6 py-3">Pressure 1</th>
                                <th class="px-6 py-3">Pressure 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <th class="px-6 py-4 font-medium">Minimum</th>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['min_flowrate']) ? number_format($record->data['summary']['min_flowrate'], 2) . ' l/s' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['min_battery']) ? number_format($record->data['summary']['min_battery'], 2) . ' v' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['min_pressure1']) ? number_format($record->data['summary']['min_pressure1'], 2) . ' bar' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['min_pressure2']) ? number_format($record->data['summary']['min_pressure2'], 2) . ' bar' : 'N/A' }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="px-6 py-4 font-medium">Rata-rata</th>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['avg_flowrate']) ? number_format($record->data['summary']['avg_flowrate'], 2) . ' l/s' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['avg_battery']) ? number_format($record->data['summary']['avg_battery'], 2) . ' v' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['avg_pressure1']) ? number_format($record->data['summary']['avg_pressure1'], 2) . ' bar' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['avg_pressure2']) ? number_format($record->data['summary']['avg_pressure2'], 2) . ' bar' : 'N/A' }}
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="px-6 py-4 font-medium">Maksimum</th>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['max_flowrate']) ? number_format($record->data['summary']['max_flowrate'], 2) . ' l/s' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['max_battery']) ? number_format($record->data['summary']['max_battery'], 2) . ' v' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['max_pressure1']) ? number_format($record->data['summary']['max_pressure1'], 2) . ' bar' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ isset($record->data['summary']['max_pressure2']) ? number_format($record->data['summary']['max_pressure2'], 2) . ' bar' : 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        
        <!-- Daily Data Table -->
        @if(isset($record->parameters['include_daily_stats']) && $record->parameters['include_daily_stats'] && isset($record->data['daily_data']))
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Data Harian</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Jumlah Data</th>
                                <th class="px-6 py-3">Avg. Flowrate</th>
                                <th class="px-6 py-3">Avg. Battery</th>
                                <th class="px-6 py-3">Avg. Pressure 1</th>
                                <th class="px-6 py-3">Avg. Pressure 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->data['daily_data'] as $day)
                                <tr class="border-b">
                                    <th class="px-6 py-4 font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</th>
                                    <td class="px-6 py-4">{{ number_format($day['readings_count']) }}</td>
                                    <td class="px-6 py-4">{{ isset($day['avg_flowrate']) ? number_format($day['avg_flowrate'], 2) . ' l/s' : 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ isset($day['avg_battery']) ? number_format($day['avg_battery'], 2) . ' v' : 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ isset($day['avg_pressure1']) ? number_format($day['avg_pressure1'], 2) . ' bar' : 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ isset($day['avg_pressure2']) ? number_format($day['avg_pressure2'], 2) . ' bar' : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        
        <!-- Uptime Analysis -->
        @if(isset($record->parameters['analyze_uptime']) && $record->parameters['analyze_uptime'] && isset($record->data['uptime_analysis']))
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Analisis Uptime</h2>
                
                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-blue-50 rounded-md">
                        <div class="text-sm font-medium text-blue-800">Perangkat</div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ $record->data['uptime_analysis']['device_name'] ?? 'N/A' }}
                        </div>
                    </div>
                    
                    <div class="p-4 bg-green-50 rounded-md">
                        <div class="text-sm font-medium text-green-800">Total Uptime</div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ number_format($record->data['uptime_analysis']['total_uptime_hours'] ?? 0, 1) }} jam
                        </div>
                    </div>
                    
                    <div class="p-4 bg-amber-50 rounded-md">
                        <div class="text-sm font-medium text-amber-800">Persentase Uptime</div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ number_format($record->data['uptime_analysis']['uptime_percentage'] ?? 0, 1) }}%
                        </div>
                    </div>
                </div>
                
                @if(isset($record->data['uptime_analysis']['status_changes']) && count($record->data['uptime_analysis']['status_changes']) > 0)
                    <h3 class="text-md font-medium mb-2">Riwayat Status Perangkat</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Dari</th>
                                    <th class="px-6 py-3">Sampai</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($record->data['uptime_analysis']['status_changes'] as $change)
                                    <tr class="border-b">
                                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($change['from'])->format('d M Y H:i:s') }}</td>
                                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($change['to'])->format('d M Y H:i:s') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-md {{ $change['status'] == 'down' ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-green-50 text-green-700 ring-green-600/20' }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                                                {{ $change['status'] == 'down' ? 'Tidak Aktif' : 'Aktif' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ number_format($change['duration_minutes']) }} menit</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Anomaly Detection -->
        @if(isset($record->parameters['detect_anomalies']) && $record->parameters['detect_anomalies'] && isset($record->data['anomalies']) && count($record->data['anomalies']) > 0)
            <div class="p-6 bg-white rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Deteksi Anomali</h2>
                
                @foreach($record->data['anomalies'] as $type => $anomalies)
                    <div class="mb-6">
                        <h3 class="text-md font-medium mb-2">
                            Anomali {{ match($type) {
                                'flowrate' => 'Flowrate',
                                'battery' => 'Battery',
                                'pressure1' => 'Pressure 1',
                                'pressure2' => 'Pressure 2',
                                default => $type
                            } }}
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Waktu</th>
                                        <th class="px-6 py-3">Nilai</th>
                                        <th class="px-6 py-3">Rata-rata</th>
                                        <th class="px-6 py-3">Deviasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($anomalies as $anomaly)
                                        <tr class="border-b">
                                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($anomaly['recorded_at'])->format('d M Y H:i:s') }}</td>
                                            <td class="px-6 py-4">
                                                {{ number_format($anomaly['value'], 2) }}
                                                {{ match($type) {
                                                    'flowrate' => 'l/s',
                                                    'battery' => 'v',
                                                    'pressure1', 'pressure2' => 'bar',
                                                    default => ''
                                                } }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ number_format($anomaly['avg_value'], 2) }}
                                                {{ match($type) {
                                                    'flowrate' => 'l/s',
                                                    'battery' => 'v',
                                                    'pressure1', 'pressure2' => 'bar',
                                                    default => ''
                                                } }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center rounded-md {{ abs($anomaly['deviation']) > 3 ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20' }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                                                    {{ number_format($anomaly['deviation'], 1) }}σ
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    
    <!-- Include ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportData = @json($record->data);
            
            // Prepare chart data
            let labels = [];
            let flowrateData = [];
            let batteryData = [];
            let pressure1Data = [];
            let pressure2Data = [];
            
            if (reportData.hourly_data && reportData.hourly_data.length > 0) {
                reportData.hourly_data.forEach(item => {
                    // Format the timestamp
                    const date = new Date(item.hour);
                    labels.push(date.toLocaleDateString('id-ID', { 
                        day: 'numeric', 
                        month: 'short', 
                        hour: '2-digit', 
                        minute: '2-digit'
                    }));
                    
                    flowrateData.push(item.avg_flowrate);
                    batteryData.push(item.avg_battery);
                    pressure1Data.push(item.avg_pressure1);
                    pressure2Data.push(item.avg_pressure2);
                });
            }
            
            // Common chart options
            const commonOptions = {
                chart: {
                    type: 'line',
                    height: 320,
                    zoom: {
                        enabled: true,
                    },
                    toolbar: {
                        show: true,
                    },
                    animations: {
                        enabled: false,
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                },
                xaxis: {
                    categories: labels,
                },
                dataLabels: {
                    enabled: false,
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                },
            };
            
            // Flowrate Chart
            if (document.getElementById('flowrateChart')) {
                const flowrateChart = new ApexCharts(document.getElementById('flowrateChart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Flowrate (l/s)',
                        data: flowrateData,
                    }],
                    yaxis: {
                        title: {
                            text: 'Flowrate (l/s)',
                        },
                    },
                    colors: ['#1A56DB'],
                });
                flowrateChart.render();
            }
            
            // Battery Chart
            if (document.getElementById('batteryChart')) {
                const batteryChart = new ApexCharts(document.getElementById('batteryChart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Battery (V)',
                        data: batteryData,
                    }],
                    yaxis: {
                        title: {
                            text: 'Battery (V)',
                        },
                    },
                    colors: ['#16A34A'],
                });
                batteryChart.render();
            }
            
            // Pressure1 Chart
            if (document.getElementById('pressure1Chart')) {
                const pressure1Chart = new ApexCharts(document.getElementById('pressure1Chart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Pressure 1 (bar)',
                        data: pressure1Data,
                    }],
                    yaxis: {
                        title: {
                            text: 'Pressure (bar)',
                        },
                    },
                    colors: ['#DC2626'],
                });
                pressure1Chart.render();
            }
            
            // Pressure2 Chart
            if (document.getElementById('pressure2Chart')) {
                const pressure2Chart = new ApexCharts(document.getElementById('pressure2Chart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Pressure 2 (bar)',
                        data: pressure2Data,
                    }],
                    yaxis: {
                        title: {
                            text: 'Pressure (bar)',
                        },
                    },
                    colors: ['#9333EA'],
                });
                pressure2Chart.render();
            }
        });
    </script>
</x-filament::page>