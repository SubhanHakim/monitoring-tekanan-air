<?php

use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Auth\LoginController;
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
