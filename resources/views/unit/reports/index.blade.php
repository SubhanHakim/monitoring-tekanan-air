{{-- filepath: resources/views/unit/reports/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Laporan Unit: {{ $unit->name }}</h5>
                    <a href="{{ route('unit.reports.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Laporan
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($reports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Laporan</th>
                                        <th>Format</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $report)
                                    <tr>
                                        <td>{{ $report->name }}</td>
                                        <td>{{ ucfirst($report->report_format) }}</td>
                                        <td>{{ $report->start_date->format('d/m/Y') }} - {{ $report->end_date->format('d/m/Y') }}</td>
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
                                        <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($report->status === 'pending')
                                                <a href="{{ route('unit.reports.generate', $report) }}" 
                                                   class="btn btn-sm btn-primary"
                                                   onclick="return confirm('Apakah Anda yakin ingin generate laporan ini?')">
                                                    <i class="fas fa-cog"></i> Generate
                                                </a>
                                            @endif
                                            
                                            @if($report->status === 'completed' && $report->file_path)
                                                <a href="{{ route('unit.reports.preview', $report) }}" 
                                                   class="btn btn-sm btn-info" 
                                                   target="_blank">
                                                    <i class="fas fa-eye"></i> Preview
                                                </a>
                                                <a href="{{ route('unit.reports.download', $report) }}" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{ $reports->links() }}
                    @else
                        <div class="text-center">
                            <p>Belum ada laporan yang dibuat.</p>
                            <a href="{{ route('unit.reports.create') }}" class="btn btn-primary">
                                Buat Laporan Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection