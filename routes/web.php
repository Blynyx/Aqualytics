<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\PondThresholdController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ponds', [PondController::class, 'index'])->middleware('auth');
Route::post('/ponds', [PondController::class, 'store'])->middleware('auth');
Route::post('/ponds/{pond}/thresholds', [PondThresholdController::class, 'store'])
    ->middleware('auth');
Route::post('/devices', [DeviceController::class, 'store'])->middleware('auth');
