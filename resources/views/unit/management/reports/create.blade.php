{{-- filepath: resources/views/unit/management/reports/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Laporan Baru')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas fa-plus"></i> Buat Laporan Baru
                            </h5>
                            <small class="text-muted">Unit: {{ $unit->name }}</small>
                        </div>
                        <a href="{{ route('unit.manage.reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('unit.manage.reports.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-file-alt"></i> Nama Laporan *
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           placeholder="Contoh: Laporan Harian Tekanan Air"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="file_type">
                                        <i class="fas fa-file"></i> Tipe File *
                                    </label>
                                    <select class="form-control @error('file_type') is-invalid @enderror" 
                                            id="file_type" 
                                            name="file_type" 
                                            required>
                                        <option value="pdf" {{ old('file_type') == 'pdf' ? 'selected' : '' }}>
                                            PDF
                                        </option>
                                        <option value="csv" {{ old('file_type') == 'csv' ? 'selected' : '' }}>
                                            CSV
                                        </option>
                                    </select>
                                    @error('file_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-comment"></i> Deskripsi
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Deskripsi laporan (opsional)">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="report_format">
                                        <i class="fas fa-chart-bar"></i> Format Laporan *
                                    </label>
                                    <select class="form-control @error('report_format') is-invalid @enderror" 
                                            id="report_format" 
                                            name="report_format" 
                                            required>
                                        <option value="">Pilih Format</option>
                                        <option value="summary" {{ old('report_format') == 'summary' ? 'selected' : '' }}>
                                            📊 Ringkasan - Data statistik singkat
                                        </option>
                                        <option value="detailed" {{ old('report_format') == 'detailed' ? 'selected' : '' }}>
                                            📋 Detail - Data lengkap dengan grafik
                                        </option>
                                        <option value="statistical" {{ old('report_format') == 'statistical' ? 'selected' : '' }}>
                                            📈 Statistik - Analisis mendalam
                                        </option>
                                    </select>
                                    @error('report_format')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="data_source">
                                        <i class="fas fa-database"></i> Sumber Data *
                                    </label>
                                    <select class="form-control @error('data_source') is-invalid @enderror" 
                                            id="data_source" 
                                            name="data_source" 
                                            required>
                                        <option value="">Pilih Sumber Data</option>
                                        <option value="all" {{ old('data_source') == 'all' ? 'selected' : '' }}>
                                            🏢 Semua Perangkat
                                        </option>
                                        <option value="device" {{ old('data_source') == 'device' ? 'selected' : '' }}>
                                            📱 Perangkat Spesifik
                                        </option>
                                    </select>
                                    @error('data_source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="device_group" style="display: none;">
                            <label for="device_id">
                                <i class="fas fa-microchip"></i> Pilih Perangkat
                            </label>
                            <select class="form-control @error('device_id') is-invalid @enderror" 
                                    id="device_id" 
                                    name="device_id">
                                <option value="">Pilih Perangkat</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }}
                                        @if($device->location)
                                            - {{ $device->location }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('device_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">
                                        <i class="fas fa-calendar-alt"></i> Tanggal Mulai *
                                    </label>
                                    <input type="date" 
                                           class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="{{ old('start_date', now()->subDays(7)->format('Y-m-d')) }}" 
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">
                                        <i class="fas fa-calendar-check"></i> Tanggal Akhir *
                                    </label>
                                    <input type="date" 
                                           class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="{{ old('end_date', now()->format('Y-m-d')) }}" 
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle text-info"></i> Informasi
                                </h6>
                                <ul class="mb-0 small">
                                    <li><strong>Ringkasan:</strong> Statistik dasar (rata-rata, min, max)</li>
                                    <li><strong>Detail:</strong> Data lengkap dengan visualisasi</li>
                                    <li><strong>Statistik:</strong> Analisis mendalam dengan trend</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Buat Laporan
                            </button>
                            <a href="{{ route('unit.manage.reports.index') }}" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataSource = document.getElementById('data_source');
    const deviceGroup = document.getElementById('device_group');
    const deviceSelect = document.getElementById('device_id');
    
    dataSource.addEventListener('change', function() {
        if (this.value === 'device') {
            deviceGroup.style.display = 'block';
            deviceSelect.required = true;
        } else {
            deviceGroup.style.display = 'none';
            deviceSelect.required = false;
            deviceSelect.value = '';
        }
    });
    
    // Trigger on page load
    if (dataSource.value === 'device') {
        deviceGroup.style.display = 'block';
        deviceSelect.required = true;
    }
    
    // Validate date range
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
    
    endDate.addEventListener('change', function() {
        startDate.max = this.value;
    });
});
</script>
@endsection