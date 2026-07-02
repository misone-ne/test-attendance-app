<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['index', 'show']);

    Route::apiResource('attendance-records', AttendanceRecordController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('auth:sanctum');
});
