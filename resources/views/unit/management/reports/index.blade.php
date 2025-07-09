{{-- filepath: resources/views/unit/management/reports/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Unit Reports Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas fa-file-alt"></i> Manajemen Laporan
                            </h5>
                            <small class="text-muted">Unit: {{ $unit->name }}</small>
                        </div>
                        <div>
                            <a href="{{ route('unit.manage.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Dashboard
                            </a>
                            <a href="{{ route('unit.manage.reports.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Buat Laporan
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($reports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Nama Laporan</th>
                                        <th>Format</th>
                                        <th>Periode</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $report)
                                    <tr>
                                        <td>
                                            <strong>{{ $report->name }}</strong>
                                            @if($report->description)
                                            <br>
                                            <small class="text-muted">{{ Str::limit($report->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ ucfirst($report->report_format) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $report->start_date->format('d/m/Y') }}
                                                <br>
                                                {{ $report->end_date->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($report->status === 'completed')
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Selesai
                                                </span>
                                            @elseif($report->status === 'processing')
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-spinner fa-spin"></i> Proses
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
                                            {{ $report->created_at->format('d/m/Y H:i') }}
                                            <br>
                                            <small class="text-muted">{{ $report->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                @if($report->status === 'pending')
                                                    <a href="{{ route('unit.manage.reports.generate', $report) }}" 
                                                       class="btn btn-sm btn-primary"
                                                       onclick="return confirm('Apakah Anda yakin ingin generate laporan ini?')"
                                                       title="Generate Laporan">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($report->status === 'completed' && $report->file_path)
                                                    <a href="{{ route('unit.manage.reports.preview', $report) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       target="_blank"
                                                       title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('unit.manage.reports.download', $report) }}" 
                                                       class="btn btn-sm btn-success"
                                                       title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $reports->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-4"></i>
                            <h4>Belum ada laporan</h4>
                            <p class="text-muted">Belum ada laporan yang dibuat untuk unit ini.</p>
                            <a href="{{ route('unit.manage.reports.create') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus"></i> Buat Laporan Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection