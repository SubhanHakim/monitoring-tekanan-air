{{-- filepath: resources/views/filament/admin/pages/sensor-data-dashboard.blade.php --}}
<x-filament::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Filter Controls -->
            <div class="p-4 bg-white rounded-lg shadow">
                <div class="space-y-4">
                    <div>
                        <label for="device" class="block text-sm font-medium text-gray-700">Pilih Perangkat</label>
                        <select id="device" wire:model.live="selectedDevice"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            @foreach ($deviceOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="dateRange" class="block text-sm font-medium text-gray-700">Rentang Waktu</label>
                        <select id="dateRange" wire:model.live="dateRange"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="last7days">7 Hari Terakhir</option>
                            <option value="last30days">30 Hari Terakhir</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Latest Readings -->
            <div class="p-4 bg-white rounded-lg shadow">
                <h2 class="text-lg font-medium text-gray-900">Pembacaan Terbaru</h2>

                @if ($selectedDevice && count($chartData['labels'] ?? []))
                    @php
                        $lastIndex = count($chartData['labels']) - 1;
                        $lastTimestamp = end($chartData['labels']);
                    @endphp

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="p-2 border rounded-md">
                            <p class="text-sm text-gray-500">Flowrate</p>
                            <p class="text-xl font-semibold">
                                {{ isset($chartData['flowrate'][$lastIndex]) ? number_format($chartData['flowrate'][$lastIndex], 2) . ' l/s' : 'N/A' }}
                            </p>
                        </div>

                        <div class="p-2 border rounded-md">
                            <p class="text-sm text-gray-500">Baterai</p>
                            <p class="text-xl font-semibold">
                                {{ isset($chartData['battery'][$lastIndex]) ? number_format($chartData['battery'][$lastIndex], 2) . ' Volt' : 'N/A' }}
                            </p>
                        </div>

                        <div class="p-2 border rounded-md">
                            <p class="text-sm text-gray-500">Tekanan 1</p>
                            <p class="text-xl font-semibold">
                                {{ isset($chartData['pressure1'][$lastIndex]) ? number_format($chartData['pressure1'][$lastIndex], 2) . ' bar' : 'N/A' }}
                            </p>
                        </div>

                        <div class="p-2 border rounded-md">
                            <p class="text-sm text-gray-500">Tekanan 2</p>
                            <p class="text-xl font-semibold">
                                {{ isset($chartData['pressure2'][$lastIndex]) ? number_format($chartData['pressure2'][$lastIndex], 2) . ' bar' : 'N/A' }}
                            </p>
                        </div>

                        <div class="p-2 border rounded-md col-span-2">
                            <p class="text-sm text-gray-500">Totalizer</p>
                            <p class="text-xl font-semibold">
                                {{ isset($chartData['totalizer'][$lastIndex]) ? number_format($chartData['totalizer'][$lastIndex], 2) . ' m³' : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <p class="mt-2 text-sm text-gray-500">Terakhir diperbarui: {{ $lastTimestamp }}</p>
                @else
                    <div class="mt-4 p-4 bg-gray-50 rounded text-center">
                        <p>Tidak ada data tersedia.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Charts -->
        <div class="space-y-6">
            <!-- Flowrate Chart -->
            <div class="p-4 bg-white rounded-lg shadow">
                <h2 class="text-lg font-medium text-gray-900">Grafik Flowrate</h2>
                <div class="mt-4 h-80" id="flowrateChart"></div>
            </div>

            <!-- Battery Chart -->
            <div class="p-4 bg-white rounded-lg shadow">
                <h2 class="text-lg font-medium text-gray-900">Grafik Baterai</h2>
                <div class="mt-4 h-80" id="batteryChart"></div>
            </div>

            <!-- Pressure Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-white rounded-lg shadow">
                    <h2 class="text-lg font-medium text-gray-900">Tekanan 1</h2>
                    <div class="mt-4 h-80" id="pressure1Chart"></div>
                </div>
                <div class="p-4 bg-white rounded-lg shadow">
                    <h2 class="text-lg font-medium text-gray-900">Tekanan 2</h2>
                    <div class="mt-4 h-80" id="pressure2Chart"></div>
                </div>
            </div>

            <!-- Totalizer Chart -->
            <div class="p-4 bg-white rounded-lg shadow">
                <h2 class="text-lg font-medium text-gray-900">Grafik Totalizer</h2>
                <div class="mt-4 h-80" id="totalizerChart"></div>
            </div>
        </div>
    </div>

    <!-- Include ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        document.addEventListener('livewire:navigated', function() {
            renderCharts();
        });

        document.addEventListener('livewire:init', function() {
            renderCharts();

            Livewire.on('chartDataUpdated', function() {
                renderCharts();
            });
        });

        function renderCharts() {
            const chartData = @json($chartData);

            if (!chartData.labels || chartData.labels.length === 0) {
                return;
            }

            // Common chart options
            const commonOptions = {
                chart: {
                    type: 'line',
                    height: 320,
                    zoom: {
                        enabled: true
                    },
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: false
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: chartData.labels
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    intersect: false
                },
            };

            // Flowrate Chart
            if (document.getElementById('flowrateChart')) {
                new ApexCharts(document.getElementById('flowrateChart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Flowrate (l/s)',
                        data: chartData.flowrate ?? [],
                    }],
                    yaxis: {
                        title: {
                            text: 'Flowrate (l/s)'
                        }
                    },
                    colors: ['#1A56DB'],
                }).render();
            }

            // Battery Chart
            if (document.getElementById('batteryChart')) {
                new ApexCharts(document.getElementById('batteryChart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Baterai (Volt)',
                        data: chartData.battery ?? [],
                    }],
                    yaxis: {
                        title: {
                            text: 'Baterai (Volt)'
                        }
                    },
                    colors: ['#16A34A'],
                }).render();
            }

            // Pressure1 Chart
            if (document.getElementById('pressure1Chart')) {
                new ApexCharts(document.getElementById('pressure1Chart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Tekanan 1 (bar)',
                        data: chartData.pressure1 ?? [],
                    }],
                    yaxis: {
                        title: {
                            text: 'Tekanan (bar)'
                        }
                    },
                    colors: ['#DC2626'],
                }).render();
            }

            // Pressure2 Chart
            if (document.getElementById('pressure2Chart')) {
                new ApexCharts(document.getElementById('pressure2Chart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Tekanan 2 (bar)',
                        data: chartData.pressure2 ?? [],
                    }],
                    yaxis: {
                        title: {
                            text: 'Tekanan (bar)'
                        }
                    },
                    colors: ['#9333EA'],
                }).render();
            }

            // Totalizer Chart
            if (document.getElementById('totalizerChart')) {
                new ApexCharts(document.getElementById('totalizerChart'), {
                    ...commonOptions,
                    series: [{
                        name: 'Totalizer (m³)',
                        data: chartData.totalizer ?? [],
                    }],
                    yaxis: {
                        title: {
                            text: 'Totalizer (m³)'
                        }
                    },
                    colors: ['#F59E42'],
                }).render();
            }
        }
    </script>
</x-filament::page>
