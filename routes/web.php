<?php

use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Unit\DashboardApiController;
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

Route::get('/unit/api/latest-data', [DashboardApiController::class, 'getLatestData'])->middleware('auth');
