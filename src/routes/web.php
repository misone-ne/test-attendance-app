<?php

use App\Http\Controllers\Auth\EmailVerificationPromptController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationPromptController::class, 'index'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('attendance.index');
    })->middleware(['signed'])->name('verification.verify');
});

// 仮
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', function () {
        return '勤怠画面';
    })->name('attendance.index');
});
