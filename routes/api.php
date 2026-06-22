<?php

use App\Http\Controllers\CoachProgressController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\ProgressPhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Measurements
    Route::post('/measurements', [MeasurementController::class, 'store']);
    Route::get('/measurements', [MeasurementController::class, 'index']);
    Route::put('/measurements/{measurement}', [MeasurementController::class, 'update']);
    Route::delete('/measurements/{measurement}', [MeasurementController::class, 'destroy']);

    // Progress photos
    Route::post('/photos', [ProgressPhotoController::class, 'store']);
    Route::get('/photos', [ProgressPhotoController::class, 'index']);
    Route::delete('/photos/{photo}', [ProgressPhotoController::class, 'destroy']);

    // Coach endpoints
    Route::get('/coach/clients/{user}/measurements', [CoachProgressController::class, 'clientMeasurements']);
    Route::get('/coach/clients/{user}/photos', [CoachProgressController::class, 'clientPhotos']);

});
