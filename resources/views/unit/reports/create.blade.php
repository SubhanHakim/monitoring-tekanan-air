{{-- filepath: resources/views/unit/reports/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Buat Laporan Baru - {{ $unit->name }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('unit.reports.store') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label for="name">Nama Laporan</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="report_format">Format Laporan</label>
                            <select class="form-control @error('report_format') is-invalid @enderror" 
                                    id="report_format" 
                                    name="report_format" 
                                    required>
                                <option value="">Pilih Format</option>
                                <option value="summary" {{ old('report_format') == 'summary' ? 'selected' : '' }}>
                                    Ringkasan
                                </option>
                                <option value="detailed" {{ old('report_format') == 'detailed' ? 'selected' : '' }}>
                                    Detail
                                </option>
                                <option value="statistical" {{ old('report_format') == 'statistical' ? 'selected' : '' }}>
                                    Statistik
                                </option>
                            </select>
                            @error('report_format')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="data_source">Sumber Data</label>
                            <select class="form-control @error('data_source') is-invalid @enderror" 
                                    id="data_source" 
                                    name="data_source" 
                                    required>
                                <option value="">Pilih Sumber Data</option>
                                <option value="all" {{ old('data_source') == 'all' ? 'selected' : '' }}>
                                    Semua Perangkat
                                </option>
                                <option value="device" {{ old('data_source') == 'device' ? 'selected' : '' }}>
                                    Perangkat Spesifik
                                </option>
                            </select>
                            @error('data_source')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group" id="device_group" style="display: none;">
                            <label for="device_id">Pilih Perangkat</label>
                            <select class="form-control @error('device_id') is-invalid @enderror" 
                                    id="device_id" 
                                    name="device_id">
                                <option value="">Pilih Perangkat</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }}
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
                                    <label for="start_date">Tanggal Mulai</label>
                                    <input type="date" 
                                           class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="{{ old('start_date') }}" 
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">Tanggal Akhir</label>
                                    <input type="date" 
                                           class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="{{ old('end_date') }}" 
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="file_type">Tipe File</label>
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

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Buat Laporan
                            </button>
                            <a href="{{ route('unit.reports.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
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
    
    dataSource.addEventListener('change', function() {
        if (this.value === 'device') {
            deviceGroup.style.display = 'block';
            document.getElementById('device_id').required = true;
        } else {
            deviceGroup.style.display = 'none';
            document.getElementById('device_id').required = false;
        }
    });
    
    // Trigger on page load
    dataSource.dispatchEvent(new Event('change'));
});
</script>
@endsection