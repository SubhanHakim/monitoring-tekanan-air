<?php

use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Unit\DashboardApiController;
use App\Http\Controllers\Unit\UnitDashboardController;
use App\Http\Controllers\Unit\UnitReportController;
use App\Models\Report;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect homepage ke login
Route::get('/', function () {
    return redirect('/login');
});

Route::post('/sensor-data', [SensorDataController::class, 'store']);

Route::get('/reports/{report}/download', function (Report $report) {
    if (!$report->last_generated_file || !Storage::exists($report->last_generated_file)) {
        return redirect()->back()->with('error', 'File laporan tidak ditemukan');
    }

    // Tambahkan header Content-Disposition yang jelas untuk memaksa download
    return Storage::download(
        $report->last_generated_file,
        'Laporan_' . $report->name . '_' . now()->format('YmdHis') . '.pdf',
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Laporan_' . $report->name . '_' . now()->format('YmdHis') . '.pdf"'
        ]
    );
})->name('reports.download');

Route::prefix('unit/api')->middleware(['auth', 'unit'])->group(function () {
    Route::get('/latest-data', [App\Http\Controllers\Unit\DashboardApiController::class, 'getLatestData'])
        ->name('unit.api.latest-data');

    Route::post('/manual-input', [App\Http\Controllers\Unit\DashboardApiController::class, 'manualInput'])
        ->name('unit.api.manual-input');

    Route::get('/device-stats', [App\Http\Controllers\Unit\DashboardApiController::class, 'getDeviceStats'])
        ->name('unit.api.device-stats');

    Route::get('/device-list', [App\Http\Controllers\Unit\DashboardApiController::class, 'getDeviceList'])
        ->name('unit.api.device-list');

    Route::get('/device/{deviceId}/data', [App\Http\Controllers\Unit\DashboardApiController::class, 'getDeviceData'])
        ->name('unit.api.device-data');
});

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    // Route untuk generate report
    Route::get('/reports/{report}/generate', [ReportController::class, 'generate'])
        ->name('reports.generate');

    Route::get('/reports/{report}/download', [ReportController::class, 'download'])
        ->name('reports.download');

    Route::get('/reports/{report}/preview', [ReportController::class, 'preview'])
        ->name('reports.preview');
});

Route::prefix('unit-manage')->middleware(['auth', 'verified'])->name('unit.manage.')->group(function () {
    // Unit Management Dashboard
    Route::get('/dashboard', [UnitDashboardController::class, 'index'])
        ->name('dashboard');
    
    // Unit Reports
    Route::get('/reports', [UnitReportController::class, 'index'])
        ->name('reports.index');
    
    Route::get('/reports/create', [UnitReportController::class, 'create'])
        ->name('reports.create');
    
    Route::post('/reports', [UnitReportController::class, 'store'])
        ->name('reports.store');
    
    Route::get('/reports/{report}/generate', [UnitReportController::class, 'generate'])
        ->name('reports.generate');
    
    Route::get('/reports/{report}/download', [UnitReportController::class, 'download'])
        ->name('reports.download');
    
    Route::get('/reports/{report}/preview', [UnitReportController::class, 'preview'])
        ->name('reports.preview');
});

Route::get('/unit/api/latest-data', [DashboardApiController::class, 'getLatestData'])->middleware('auth');
