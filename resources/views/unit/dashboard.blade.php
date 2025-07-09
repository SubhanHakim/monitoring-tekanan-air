{{-- filepath: resources/views/unit/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Dashboard Unit: {{ $unit->name }}</h2>
                    <div class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> {{ $unit->location }}
                        <span class="ml-3">
                            <span class="badge badge-{{ $unit->status === 'active' ? 'success' : 'danger' }}">
                                {{ $unit->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            
            @if($unit->description)
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> {{ $unit->description }}
            </div>
            @endif
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>{{ $totalDevices }}</h4>
                            <p>Total Perangkat</p>
                        </div>
                        <div>
                            <i class="fas fa-microchip fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>{{ $activeDevices }}</h4>
                            <p>Perangkat Aktif</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>{{ $totalReports }}</h4>
                            <p>Total Laporan</p>
                        </div>
                        <div>
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4>{{ $completedReports }}</h4>
                            <p>Laporan Selesai</p>
                        </div>
                        <div>
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Reports -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Laporan Terbaru</h5>
                    <a href="{{ route('unit.reports.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    @if($recentReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                    <tr>
                                        <td>{{ $report->name }}</td>
                                        <td>
                                            @if($report->status === 'completed')
                                                <span class="badge badge-success">Selesai</span>
                                            @elseif($report->status === 'processing')
                                                <span class="badge badge-warning">Proses</span>
                                            @elseif($report->status === 'failed')
                                                <span class="badge badge-danger">Gagal</span>
                                            @else
                                                <span class="badge badge-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $report->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <p>Belum ada laporan</p>
                            <a href="{{ route('unit.reports.create') }}" class="btn btn-primary">
                                Buat Laporan Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Sensor Data -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Data Sensor Terbaru</h5>
                </div>
                <div class="card-body">
                    @if($recentSensorData->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Perangkat</th>
                                        <th>Flowrate</th>
                                        <th>Pressure</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentSensorData as $data)
                                    <tr>
                                        <td>{{ $data->device->name }}</td>
                                        <td>{{ number_format($data->flowrate, 2) }}</td>
                                        <td>{{ number_format($data->pressure1, 2) }}</td>
                                        <td>{{ $data->recorded_at->format('d/m H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <p>Belum ada data sensor</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection