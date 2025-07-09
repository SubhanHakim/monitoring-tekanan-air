{{-- filepath: resources/views/filament/admin/pages/dashboard.blade.php --}}
<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-3xl font-bold text-blue-600">{{ $totalDevice }}</div>
            <div class="mt-2 text-gray-600">Total Perangkat</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-3xl font-bold text-green-600">{{ $totalSensorData }}</div>
            <div class="mt-2 text-gray-600">Total Data Sensor</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-3xl font-bold text-yellow-600">{{ $totalUnit }}</div>
            <div class="mt-2 text-gray-600">Total Unit</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-3xl font-bold text-purple-600">{{ $totalUser }}</div>
            <div class="mt-2 text-gray-600">Total User</div>
        </div>
    </div>

    <div class="mb-4">
        <label for="device" class="block text-sm font-medium text-gray-700 mb-1">Pilih Perangkat</label>
        <select id="device" wire:model.live="selectedDevice" class="block w-full max-w-xs rounded border-gray-300">
            @foreach ($deviceOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-lg text-gray-500">Tekanan 1 Terbaru</div>
            <div class="text-2xl font-bold text-blue-700 mt-2">{{ $latestPressure1 ?? 'N/A' }} <span
                    class="text-base">bar</span></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-lg text-gray-500">Tekanan 2 Terbaru</div>
            <div class="text-2xl font-bold text-blue-700 mt-2">{{ $latestPressure2 ?? 'N/A' }} <span
                    class="text-base">bar</span></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center">
            <div class="text-lg text-gray-500">Totalizer Terbaru</div>
            <div class="text-2xl font-bold text-orange-600 mt-2">{{ $latestTotalizer ?? 'N/A' }} <span
                    class="text-base">m³</span></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Grafik Tekanan 1</h2>
            <div id="pressure1Chart" class="h-64"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Grafik Tekanan 2</h2>
            <div id="pressure2Chart" class="h-64"></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">Grafik Totalizer</h2>
            <div id="totalizerChart" class="h-64"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartLabels = @json($chartLabels ?? []);
            const chartPressure1 = @json($chartPressure1 ?? []);
            const chartPressure2 = @json($chartPressure2 ?? []);
            const chartTotalizer = @json($chartTotalizer ?? []);

            // Chart Pressure 1
            if (document.getElementById('pressure1Chart')) {
                new ApexCharts(document.getElementById('pressure1Chart'), {
                    chart: {
                        type: 'line',
                        height: 220
                    },
                    series: [{
                        name: 'Tekanan 1',
                        data: chartPressure1
                    }],
                    xaxis: {
                        categories: chartLabels
                    },
                    colors: ['#2563eb'],
                }).render();
            }
            // Chart Pressure 2
            if (document.getElementById('pressure2Chart')) {
                new ApexCharts(document.getElementById('pressure2Chart'), {
                    chart: {
                        type: 'line',
                        height: 220
                    },
                    series: [{
                        name: 'Tekanan 2',
                        data: chartPressure2
                    }],
                    xaxis: {
                        categories: chartLabels
                    },
                    colors: ['#0ea5e9'],
                }).render();
            }
            // Chart Totalizer
            if (document.getElementById('totalizerChart')) {
                new ApexCharts(document.getElementById('totalizerChart'), {
                    chart: {
                        type: 'line',
                        height: 220
                    },
                    series: [{
                        name: 'Totalizer',
                        data: chartTotalizer
                    }],
                    xaxis: {
                        categories: chartLabels
                    },
                    colors: ['#f59e42'],                                                     
                              
                }).render();
            }
        });
    </script>
</x-filament::page>