<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Policies\AttendanceRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * 勤怠情報に対する認可ポリシーを登録する。
     *
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(Attendance::class, AttendanceRecordPolicy::class);
    }
}
