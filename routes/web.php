<?php

use App\Http\Controllers\PondController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ponds', [PondController::class, 'index'])->middleware('auth');
Route::post('/ponds', [PondController::class, 'store'])->middleware('auth');
