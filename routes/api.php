<?php

use App\Http\Controllers\PredictiveDialingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('predictive')->group(function () {
        Route::post('{campaign}/start', [PredictiveDialingController::class, 'start']);
        Route::post('{campaign}/stop', [PredictiveDialingController::class, 'stop']);
        Route::get('{campaign}/stats', [PredictiveDialingController::class, 'stats']);
    });
});