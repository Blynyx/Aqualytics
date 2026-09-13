<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\PondThresholdController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/ponds', [PondController::class, 'index'])
    ->middleware('auth')
    ->name('ponds.index');
Route::post('/ponds', [PondController::class, 'store'])->middleware('auth');
Route::post('/ponds/{pond}/thresholds', [PondThresholdController::class, 'store'])
    ->middleware('auth');
Route::post('/devices', [DeviceController::class, 'store'])->middleware('auth');
