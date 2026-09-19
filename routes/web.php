<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InternalNotificationController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\PondReadingHistoryController;
use App\Http\Controllers\PondThresholdController;
use App\Http\Controllers\UserController;
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
Route::get('/ponds/create', [PondController::class, 'create'])
    ->middleware(['auth', 'role:admin'])
    ->name('ponds.create');
Route::get('/ponds/{pond}', [PondController::class, 'show'])
    ->middleware('auth')
    ->name('ponds.show');
Route::get('/ponds/{pond}/readings/history', PondReadingHistoryController::class)
    ->middleware('auth')
    ->name('ponds.readings.history');
Route::post('/ponds', [PondController::class, 'store'])
    ->middleware(['auth', 'role:admin']);
Route::post('/ponds/{pond}/devices', [DeviceController::class, 'store'])
    ->middleware(['auth', 'role:admin'])
    ->name('ponds.devices.store');
Route::post('/ponds/{pond}/thresholds', [PondThresholdController::class, 'store'])
    ->middleware(['auth', 'role:admin'])
    ->name('ponds.thresholds.store');
Route::post('/devices', [DeviceController::class, 'store'])
    ->middleware(['auth', 'role:admin']);
Route::post('/devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken'])
    ->middleware(['auth', 'role:admin'])
    ->name('devices.regenerate-token');
Route::post('/alerts/{alert}/assign', [AlertController::class, 'assign'])
    ->middleware('auth')
    ->name('alerts.assign');
Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])
    ->middleware('auth')
    ->name('alerts.resolve');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [InternalNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [InternalNotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('/alerts/{alert}/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::post('/incidents/{incident}/assign', [IncidentController::class, 'assign'])->name('incidents.assign');
    Route::post('/incidents/{incident}/start', [IncidentController::class, 'start'])->name('incidents.start');
    Route::post('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::post('/incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
});
