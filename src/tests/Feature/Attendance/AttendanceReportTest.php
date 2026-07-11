<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID20
     */
    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    /**
     * ID20
     */
    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        $user = User::factory()->create();

        $this->actingAs($user);

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-01',
            'clock_in' => '2026-07-01 09:00:00',
            'clock_out' => '2026-07-01 18:00:00',
            'note' => '通常勤務',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-01 12:00:00',
            'break_end' => '2026-07-01 13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-02',
            'clock_in' => '2026-07-02 09:30:00',
            'clock_out' => '2026-07-02 20:30:00',
            'note' => '残業',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-07-02 12:00:00',
            'break_end' => '2026-07-02 13:00:00',
        ]);

        $attendance3 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-06-10',
            'clock_in' => '2026-06-10 09:00:00',
            'clock_out' => '2026-06-10 17:00:00',
            'note' => '早退',
        ]);

        $response = $this->get('/attendance/report');

        $response->assertOk();

        $response->assertViewHas('summary', [
            'total_work_time' => '26h 0m',
            'total_overtime' => '2h 0m',
            'average_work_time' => '8h 40m',
        ]);

        $response->assertViewHas('monthlyReports', function ($monthlyReports) {
            return $monthlyReports->contains([
                'month' => '2026-06',
                'work_time' => '8h 0m',
                'overtime' => '0h 0m',
            ])
                && $monthlyReports->contains([
                    'month' => '2026-07',
                    'work_time' => '18h 0m',
                    'overtime' => '2h 0m',
                ]);
        });

        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);

        Carbon::setTestNow();
    }

    /**
     * ID20
     */
    public function test_勤怠記録がないユーザーで安全に処理される(): void
    {
        Carbon::setTestNow('2026-07-15 12:00:00');

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/attendance/report');

        $response->assertOk();

        $response->assertViewHas('summary', [
            'total_work_time' => '0h 0m',
            'total_overtime' => '0h 0m',
            'average_work_time' => '0h 0m',
        ]);

        $response->assertViewHas('monthlyReports', function ($monthlyReports) {
            return $monthlyReports->count() === 6
                && $monthlyReports->every(function ($monthlyReport) {
                    return $monthlyReport['work_time'] === '0h 0m'
                        && $monthlyReport['overtime'] === '0h 0m';
                });
        });

        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);

        Carbon::setTestNow();
    }
}
