<?php

use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect homepage ke login
Route::get('/', function () {
    return redirect('/login');
});

Route::post('/sensor-data', [SensorDataController::class, 'store']);
