<x-filament-panels::page>
    <div class="fi-page-header mb-6">
        <h1 class="fi-page-title text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            {{ $unitName }}
        </h1>
        <p class="fi-page-description mt-2 text-sm text-gray-600 dark:text-gray-400">
            Dashboard monitoring tekanan air real-time
        </p>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Perangkat -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Perangkat</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $devices->count() }}</div>
            </div>
            <div class="mt-2 flex items-center text-primary-600 dark:text-primary-400">
                <x-heroicon-s-cpu-chip class="w-4 h-4 mr-1" />
                <span class="text-xs font-medium">Terdaftar</span>
            </div>
        </div>
        <!-- Card 2: Perangkat Aktif -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Perangkat Aktif</div>
                <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $activeDevices }}</div>
            </div>
            <div class="mt-2 flex items-center text-success-600 dark:text-success-400">
                <x-heroicon-s-check-circle class="w-4 h-4 mr-1" />
                <span class="text-xs font-medium">Online</span>
            </div>
        </div>
        <!-- Card 3: Perangkat Offline -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Perangkat Offline</div>
                <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $offlineDevices }}</div>
            </div>
            <div class="mt-2 flex items-center text-warning-600 dark:text-warning-400">
                <x-heroicon-s-exclamation-triangle class="w-4 h-4 mr-1" />
                <span class="text-xs font-medium">Tidak Aktif</span>
            </div>
        </div>
        <!-- Card 4: Perangkat Error -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Perangkat Error</div>
                <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $errorDevices }}</div>
            </div>
            <div class="mt-2 flex items-center text-danger-600 dark:text-danger-400">
                <x-heroicon-s-x-circle class="w-4 h-4 mr-1" />
                <span class="text-xs font-medium">Butuh Perhatian</span>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Chart 1: Tekanan Air -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4">
                <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Grafik Tekanan Air (Real-time)
                </h2>
            </div>
            <div class="h-80">
                <canvas id="pressureChart"></canvas>
            </div>
        </div>
        <!-- Chart 2: Flowrate -->
        <div
            class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4">
                <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Grafik Flowrate (Real-time)
                </h2>
            </div>
            <div class="h-80">
                <canvas id="flowrateChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Device List -->
    <div
        class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4">
            <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Daftar Perangkat
            </h2>
        </div>
        @if ($devices->isEmpty())
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="mb-4 rounded-full bg-primary-50 p-3 dark:bg-primary-500/10">
                    <x-heroicon-o-device-tablet class="h-6 w-6 text-primary-500" />
                </div>
                <h2 class="text-xl font-bold tracking-tight">Tidak ada perangkat</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada perangkat yang terdaftar untuk unit
                    ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                ID</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Nama Perangkat</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Lokasi</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Status</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Baterai</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Tekanan</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Flowrate</th>
                            <th
                                class="fi-ta-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                Terakhir Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach ($devices as $device)
                            @php
                                $lastData = $device->lastData;
                                $status = 'offline';
                                $statusColor = 'warning';
                                $statusText = 'Offline';
                                if ($lastData && $lastData->recorded_at >= now()->subMinutes(30)) {
                                    if ($lastData->error_code || $lastData->battery < 15) {
                                        $status = 'error';
                                        $statusColor = 'danger';
                                        $statusText = 'Error';
                                    } else {
                                        $status = 'active';
                                        $statusColor = 'success';
                                        $statusText = 'Aktif';
                                    }
                                }
                            @endphp
                            <tr id="device-{{ $device->id }}" class="device-row device-status-{{ $status }}">
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $device->id }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="font-medium text-gray-900 dark:text-gray-200">{{ $device->name }}</div>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $device->location ?? 'N/A' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span
                                        class="fi-badge inline-flex items-center justify-center whitespace-nowrap rounded-full bg-{{ $statusColor }}-500/10 px-2 py-0.5 text-{{ $statusColor }}-600 ring-1 ring-inset ring-{{ $statusColor }}-600/10 dark:bg-{{ $statusColor }}-500/10 dark:text-{{ $statusColor }}-400 dark:ring-{{ $statusColor }}-400/20">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td
                                    class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400 device-battery">
                                    {{ $lastData->battery ?? 'N/A' }}%</td>
                                <td
                                    class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400 device-pressure">
                                    {{ $lastData->pressure1 ?? 'N/A' }} bar</td>
                                <td
                                    class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400 device-flowrate">
                                    {{ $lastData->flowrate ?? 'N/A' }} L/s</td>
                                <td
                                    class="fi-ta-cell px-3 py-4 text-sm text-gray-600 dark:text-gray-400 device-last-update">
                                    {{ $lastData ? $lastData->recorded_at->diffForHumans() : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
        <script>
            // Data awal
            const initialData = @json($latestData);

            // Palet warna menarik & cukup banyak
            const colors = [
                'rgba(59,130,246,1)', // biru
                'rgba(239,68,68,1)', // merah
                'rgba(16,185,129,1)', // hijau
                'rgba(245,158,11,1)', // kuning
                'rgba(139,92,246,1)', // ungu
                'rgba(251,191,36,1)', // orange
                'rgba(34,197,94,1)', // hijau muda
                'rgba(236,72,153,1)', // pink
                'rgba(59,130,246,0.7)', // biru muda
                'rgba(20,184,166,1)', // teal
            ];

            // Helper warna transparan
            function transparent(color, alpha = 0.15) {
                return color.replace('1)', `${alpha})`);
            }

            // Chart untuk tekanan
            const pressureChartCtx = document.getElementById('pressureChart');
            const pressureChart = new Chart(pressureChartCtx, {
                type: 'line',
                data: {
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'minute'
                            },
                            title: {
                                display: true,
                                text: 'Waktu'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Tekanan (bar)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });

            // Chart untuk flowrate
            const flowrateChartCtx = document.getElementById('flowrateChart');
            const flowrateChart = new Chart(flowrateChartCtx, {
                type: 'line',
                data: {
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'minute'
                            },
                            title: {
                                display: true,
                                text: 'Waktu'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Flowrate (L/s)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });

            // Inisialisasi chart dengan data awal
            function initializeCharts(data) {
                let deviceIndex = 0;
                pressureChart.data.datasets = [];
                flowrateChart.data.datasets = [];
                for (const [deviceId, readings] of Object.entries(data)) {
                    if (!readings || readings.length === 0) continue;
                    const sortedReadings = [...readings].sort((a, b) => new Date(a.recorded_at) - new Date(b.recorded_at));
                    const deviceName = sortedReadings[0].device?.name ?? `Device ${deviceId}`;
                    const color = colors[deviceIndex % colors.length];
                    // Data untuk pressure chart
                    const pressureData = sortedReadings
                        .filter(r => r.pressure1 !== null && r.recorded_at)
                        .map(reading => ({
                            x: new Date(reading.recorded_at),
                            y: reading.pressure1
                        }));
                    pressureChart.data.datasets.push({
                        label: deviceName,
                        data: pressureData,
                        borderColor: color,
                        backgroundColor: transparent(color, 0.18),
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4
                    });
                    // Data untuk flowrate chart
                    const flowrateData = sortedReadings
                        .filter(r => r.flowrate !== null && r.recorded_at)
                        .map(reading => ({
                            x: new Date(reading.recorded_at),
                            y: reading.flowrate
                        }));
                    flowrateChart.data.datasets.push({
                        label: deviceName,
                        data: flowrateData,
                        borderColor: color,
                        backgroundColor: transparent(color, 0.18),
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4
                    });
                    deviceIndex++;
                }
                pressureChart.update();
                flowrateChart.update();
            }

            // Update data device dalam tabel
            function updateDeviceTable(data) {
                for (const [deviceId, readings] of Object.entries(data)) {
                    if (!readings || readings.length === 0) continue;
                    const sortedReadings = [...readings].sort((a, b) => new Date(a.recorded_at) - new Date(b.recorded_at));
                    const latestReading = sortedReadings[sortedReadings.length - 1];
                    const deviceRow = document.getElementById(`device-${deviceId}`);
                    if (deviceRow) {
                        const batteryEl = deviceRow.querySelector('.device-battery');
                        const pressureEl = deviceRow.querySelector('.device-pressure');
                        const flowrateEl = deviceRow.querySelector('.device-flowrate');
                        const lastUpdateEl = deviceRow.querySelector('.device-last-update');
                        if (batteryEl) batteryEl.textContent = `${latestReading.battery}%`;
                        if (pressureEl) pressureEl.textContent = `${latestReading.pressure1} bar`;
                        if (flowrateEl) flowrateEl.textContent = `${latestReading.flowrate} L/s`;
                        if (lastUpdateEl) lastUpdateEl.textContent = `Baru saja`;
                        // Update status
                        const statusBadge = deviceRow.querySelector('td:nth-child(4) span');
                        let newStatus = 'active';
                        let newColor = 'success';
                        let newText = 'Aktif';
                        if (latestReading.error_code || latestReading.battery < 15) {
                            newStatus = 'error';
                            newColor = 'danger';
                            newText = 'Error';
                        }
                        if (statusBadge) {
                            statusBadge.textContent = newText;
                            statusBadge.className = statusBadge.className
                                .replace(/bg-\w+-500\/10/g, `bg-${newColor}-500/10`)
                                .replace(/text-\w+-600/g, `text-${newColor}-600`)
                                .replace(/dark:text-\w+-400/g, `dark:text-${newColor}-400`)
                                .replace(/ring-\w+-600\/10/g, `ring-${newColor}-600/10`)
                                .replace(/dark:ring-\w+-400\/20/g, `dark:ring-${newColor}-400/20`);
                        }
                        deviceRow.className = `device-row device-status-${newStatus}`;
                    }
                }
            }

            // Inisialisasi dengan data awal
            initializeCharts(initialData);

            // Setup polling untuk data real-time setiap 5 detik
            setInterval(async function() {
                try {
                    const response = await fetch('/unit/api/latest-data');
                    const newData = await response.json();
                    initializeCharts(newData);
                    updateDeviceTable(newData);
                } catch (error) {
                    console.error('Error fetching latest data:', error);
                }
            }, 5000);
        </script>
    @endpush
</x-filament-panels::page>
