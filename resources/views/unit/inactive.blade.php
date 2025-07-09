{{-- filepath: resources/views/unit/inactive.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-5x text-warning"></i>
                    </div>
                    <h3>Unit Tidak Aktif</h3>
                    <p class="lead">Unit <strong>{{ $unit->name }}</strong> saat ini tidak aktif.</p>
                    <p class="text-muted">Silakan hubungi administrator untuk mengaktifkan unit ini.</p>
                    
                    <div class="mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Informasi Unit</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Nama:</strong></td>
                                        <td>{{ $unit->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Lokasi:</strong></td>
                                        <td>{{ $unit->location }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge badge-danger">Tidak Aktif</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('logout') }}" 
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                           class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
@endsection