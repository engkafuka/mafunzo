<?php

use App\Http\Controllers\Api\Licensing\ChangeRequestController;
use App\Http\Controllers\Api\Licensing\NominationController;
use App\Http\Controllers\Api\Licensing\TrainedStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('licensing.api')->prefix('v1/licensing')->group(function () {
    Route::get('/trained-staff', [TrainedStaffController::class, 'index']);
    Route::get('/nominations', [NominationController::class, 'index']);
    Route::post('/nominations', [NominationController::class, 'store']);
    Route::get('/nominations/{nomination}', [NominationController::class, 'show']);
    Route::post('/nominations/{nomination}/issue', [NominationController::class, 'issue']);
    Route::post('/nominations/{nomination}/cancel', [NominationController::class, 'cancel']);

    Route::get('/nominations/{nomination}/change-requests', [ChangeRequestController::class, 'index']);
    Route::post('/nominations/{nomination}/change-requests', [ChangeRequestController::class, 'store']);
    Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show']);
    Route::post('/change-requests/{changeRequest}/cancel', [ChangeRequestController::class, 'cancel']);
});
