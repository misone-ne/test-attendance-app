<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID15
     */
    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user1 = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤 花子',
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-08',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-07-09',
        ]);

        $pendingRequest1 = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '山田の承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $pendingRequest2 = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance2->id,
            'user_id' => $user2->id,
            'requested_clock_in' => '2026-07-09 10:00:00',
            'requested_clock_out' => '2026-07-09 19:00:00',
            'note' => '佐藤の承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'requested_clock_in' => '2026-07-08 08:00:00',
            'requested_clock_out' => '2026-07-08 17:00:00',
            'note' => '承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('request.index', [
                'status' => 'pending',
            ]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('山田の承認待ち申請');
        $response->assertSee(route('admin.request.approve.show', [
            'attendance_correct_request_id' => $pendingRequest1->id,
        ]));

        $response->assertSee('佐藤 花子');
        $response->assertSee('佐藤の承認待ち申請');
        $response->assertSee(route('admin.request.approve.show', [
            'attendance_correct_request_id' => $pendingRequest2->id,
        ]));

        $response->assertDontSee('承認済み申請');
    }

    /**
     * ID15
     */
    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user1 = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤 花子',
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-08',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-07-09',
        ]);

        $approvedRequest1 = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '山田の承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => '2026-07-10 10:00:00',
        ]);

        $approvedRequest2 = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance2->id,
            'user_id' => $user2->id,
            'requested_clock_in' => '2026-07-09 10:00:00',
            'requested_clock_out' => '2026-07-09 19:00:00',
            'note' => '佐藤の承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => '2026-07-10 11:00:00',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'requested_clock_in' => '2026-07-08 08:00:00',
            'requested_clock_out' => '2026-07-08 17:00:00',
            'note' => '承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('request.index', [
                'status' => 'approved',
            ]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('山田の承認済み申請');
        $response->assertSee(route('admin.request.approve.show', [
            'attendance_correct_request_id' => $approvedRequest1->id,
        ]));

        $response->assertSee('佐藤 花子');
        $response->assertSee('佐藤の承認済み申請');
        $response->assertSee(route('admin.request.approve.show', [
            'attendance_correct_request_id' => $approvedRequest2->id,
        ]));

        $response->assertDontSee('承認待ち申請');
    }

    /**
     * ID15
     */
    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 08:00:00',
            'clock_out' => '2026-07-08 17:00:00',
        ]);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '打刻時間の修正をお願いします',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionBreak::create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'requested_break_start' => '2026-07-08 12:00:00',
            'requested_break_end' => '2026-07-08 13:00:00',
            'break_order' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.request.approve.show', [
                'attendance_correct_request_id' => $correctionRequest->id,
            ]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('2026年');
        $response->assertSee('7月8日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('打刻時間の修正をお願いします');
        $response->assertSee('承認');
    }

    /**
     * ID15
     */
    public function test_修正申請の承認処理が正しく行われる(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00'));

        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 08:00:00',
            'clock_out' => '2026-07-08 17:00:00',
            'note' => '修正前',
        ]);

        $oldBreak = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 11:00:00',
            'break_end' => '2026-07-08 11:30:00',
        ]);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '修正後の備考',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionBreak::create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'requested_break_start' => '2026-07-08 12:00:00',
            'requested_break_end' => '2026-07-08 13:00:00',
            'break_order' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.request.approve', [
                'attendance_correct_request_id' => $correctionRequest->id,
            ]));

        $response->assertRedirect(route('admin.request.approve.show', [
            'attendance_correct_request_id' => $correctionRequest->id,
        ]));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
            'note' => '修正後の備考',
        ]);

        $this->assertDatabaseMissing('break_times', [
            'id' => $oldBreak->id,
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => '2026-07-10 12:00:00',
        ]);

        Carbon::setTestNow();
    }
}
