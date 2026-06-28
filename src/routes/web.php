<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// メール認証
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationPromptController::class, 'index'])
        ->name('verification.notice');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back();
    })->middleware(['throttle:6,1'])->name('verification.send');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('attendance.index');
    })->middleware(['signed'])->name('verification.verify');
});

// 一般ユーザログイン
Route::middleware('guest')->group(function () {
    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});


// 一般ユーザ用画面
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');

    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.break-start');

    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.break-end');

    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');


    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');

    // 勤怠詳細（日付指定）
    Route::get('/attendance/detail/date/{date}', [AttendanceController::class, 'showByDate'])
        ->name('attendance.show-by-date');

    // 勤怠詳細
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    // 勤怠修正依頼登録
    Route::post('/attendance/detail/{id}/correction', [AttendanceController::class, 'storeCorrectionRequest'])
        ->name('attendance.correction.store');
});


// 管理者ログイン
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'create'])
        ->name('admin.login');

    Route::post('/admin/login', [AdminLoginController::class, 'store'])
        ->name('admin.login.store');
});


// 管理者側
Route::middleware('auth:admin')->group(function () {
    // 当日勤怠一覧
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.list');

    // スタッフ一覧
    Route::get('/admin/staff/list', [AdminAttendanceController::class, 'staffList'])
        ->name('admin.staff.list');

    // スタッフ別勤怠一覧
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendanceList'])
        ->name('admin.staff.attendance.list');

    // CSV出力
    Route::get('/admin/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'staffAttendanceCsv'])
        ->name('admin.staff.attendance.csv');

    // 勤怠詳細
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    // 勤怠詳細（日付指定）
    Route::get('/admin/attendance/staff/{user_id}/date/{date}', [AdminAttendanceController::class, 'showByDate'])
        ->name('admin.attendance.show-by-date');

    // 勤怠修正
    Route::put('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    // 修正申請承認画面
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approveShow'])
        ->name('admin.request.approve.show');

    // 修正申請承認
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approve'])
        ->name('admin.request.approve');

    Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])
        ->name('admin.logout');
});

// 申請一覧画面
Route::get('/stamp_correction_request/list', function () {
    if (auth('admin')->check()) {
        return app(AdminAttendanceController::class)->requestList();
    }

    if (auth()->check()) {
        return app(AttendanceController::class)->requestList();
    }

    return redirect()->route('login');
})->name('request.index');
