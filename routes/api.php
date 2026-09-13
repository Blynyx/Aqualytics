<?php

use App\Http\Controllers\Api\ReadingController;
use Illuminate\Support\Facades\Route;

Route::post('/readings', [ReadingController::class, 'store']);
