{{-- filepath: resources/views/unit/management/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Unit Management Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Management Dashboard</h2>
                    <h4 class="text-primary">{{ $unit->name }}</h4>
                    <div class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> {{ $unit->location }}
                        <span class="ml-3">
                            <span class="badge badge-{{ $unit->status === 'active' ? 'success' : 'danger' }}">
                                {{ $unit->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </span>
                    </div>
                </div>
                <div>
                    <a href="/unit" class="btn btn-info">
                        <i class="fas fa-chart-line"></i> Monitoring Dashboard
                    </a>
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
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $totalDevices }}</h3>
                            <p class="mb-0">Total Perangkat</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-microchip fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary-dark">
                    <small class="text-white-50">Perangkat terdaftar</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $activeDevices }}</h3>
                            <p class="mb-0">Perangkat Aktif</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success-dark">
                    <small class="text-white-50">Online sekarang</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $totalReports }}</h3>
                            <p class="mb-0">Total Laporan</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info-dark">
                    <small class="text-white-50">Laporan dibuat</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="mb-1">{{ $completedReports }}</h3>
                            <p class="mb-0">Laporan Selesai</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-3x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning-dark">
                    <small class="text-white-50">Siap diunduh</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="{{ route('unit.manage.reports.create') }}" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-plus"></i> Buat Laporan Baru
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('unit.manage.reports.index') }}" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-list"></i> Lihat Semua Laporan
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="/unit" class="btn btn-info btn-block btn-lg">
                                <i class="fas fa-tachometer-alt"></i> Dashboard Monitoring
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Reports -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt"></i> Laporan Terbaru
                    </h5>
                    <a href="{{ route('unit.manage.reports.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    @if($recentReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nama Laporan</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                    <tr>
                                        <td>
                                            <strong>{{ $report->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $report->report_format }}</small>
                                        </td>
                                        <td>
                                            @if($report->status === 'completed')
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Selesai
                                                </span>
                                            @elseif($report->status === 'processing')
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-spinner"></i> Proses
                                                </span>
                                            @elseif($report->status === 'failed')
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times"></i> Gagal
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $report->created_at->format('d/m/Y') }}
                                            <br>
                                            <small class="text-muted">{{ $report->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            @if($report->status === 'pending')
                                                <a href="{{ route('unit.manage.reports.generate', $report) }}" 
                                                   class="btn btn-sm btn-primary"
                                                   onclick="return confirm('Generate laporan ini?')">
                                                    <i class="fas fa-cog"></i>
                                                </a>
                                            @endif
                                            
                                            @if($report->status === 'completed' && $report->file_path)
                                                <a href="{{ route('unit.manage.reports.download', $report) }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada laporan yang dibuat</p>
                            <a href="{{ route('unit.manage.reports.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Buat Laporan Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Sensor Data Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-thermometer-half"></i> Data Sensor Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    @if($recentSensorData->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentSensorData->take(5) as $data)
                            <div class="list-group-item px-0 border-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">{{ $data->device->name }}</h6>
                                        <small class="text-muted">{{ $data->recorded_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-primary">
                                            <strong>{{ number_format($data->flowrate, 1) }}</strong> L/s
                                        </div>
                                        <div class="text-info">
                                            <strong>{{ number_format($data->pressure1, 1) }}</strong> bar
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        @if($recentSensorData->count() > 5)
                        <div class="text-center mt-3">
                            <a href="/unit" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-chart-line"></i> Lihat Detail Monitoring
                            </a>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-thermometer-half fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Belum ada data sensor</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-primary-dark { background-color: rgba(0,123,255,0.8) !important; }
.bg-success-dark { background-color: rgba(40,167,69,0.8) !important; }
.bg-info-dark { background-color: rgba(23,162,184,0.8) !important; }
.bg-warning-dark { background-color: rgba(255,193,7,0.8) !important; }
.opacity-75 { opacity: 0.75; }
</style>
@endsection