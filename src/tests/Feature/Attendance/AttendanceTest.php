<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // =========================
    // 日時取得・ステータス確認
    // =========================

    /**
     * ID4
     */
    public function test_現在の日時情報が出力されている(): void
    {
        Carbon::setTestNow('2026-07-08 09:30:00');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);

        $response->assertSee('2026年7月8日(水)');
        $response->assertSee('09:30');

        Carbon::setTestNow();
    }

    /**
     * ID5
     */
    public function test_勤務外の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * ID5
     */
    public function test_出勤中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * ID5
     */
    public function test_休憩中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * ID5
     */
    public function test_退勤済の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    // =========================
    // 出勤
    // =========================

    /**
     * ID6
     */
    public function test_出勤ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('attendance.clock-in'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-08 00:00:00',
            'clock_in' => '2026-07-08 09:00:00',
        ]);
    }

    /**
     * ID6
     */
    public function test_出勤は一日一回のみできる(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertDontSee('>出勤</button>', false);
    }

    /**
     * ID6
     */
    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('09:00');
    }

    // =========================
    // 休憩
    // =========================

    /**
     * ID7
     */
    public function test_休憩ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-07-08 12:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('attendance.break-start'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => null,
        ]);
    }

    /**
     * ID7
     */
    public function test_休憩は一日に何回でもできる(): void
    {
        Carbon::setTestNow('2026-07-08 15:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(6),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(3),
            'break_end' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /**
     * ID7
     */
    public function test_休憩戻ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-07-08 13:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(4),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHour(),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('attendance.break-end'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);
    }

    /**
     * ID7
     */
    public function test_休憩戻は一日に何回でもできる(): void
    {
        Carbon::setTestNow('2026-07-08 15:30:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(6),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(3),
            'break_end' => now()->subHours(2),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subMinutes(30),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    /**
     * ID7
     */
    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow('2026-07-08 13:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '2026-07-08 09:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('1:00');
    }

    // =========================
    // 退勤
    // =========================

    /**
     * ID8
     */
    public function test_退勤ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-07-08 18:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(9),
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->post(route('attendance.clock-out'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-08 00:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);
    }

    /**
     * ID8
     */
    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow('2026-07-08 18:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('18:00');
    }

    // =========================
    // 勤怠一覧
    // =========================

    /**
     * ID9
     */
    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08 12:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-01',
            'clock_in' => '2026-07-01 09:00:00',
            'clock_out' => '2026-07-01 18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-01 12:00:00',
            'break_end' => '2026-07-01 13:00:00',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-02',
            'clock_in' => '2026-07-02 10:00:00',
            'clock_out' => '2026-07-02 19:00:00',
        ]);

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-03',
            'clock_in' => '2026-07-03 08:30:00',
            'clock_out' => '2026-07-03 17:30:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertOk();

        $response->assertSee('07/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        $response->assertSee('07/02');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertDontSee('08:30');
        $response->assertDontSee('17:30');

        Carbon::setTestNow();
    }

    /**
     * ID9
     */
    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertOk();

        $response->assertSee('2026/07');

        Carbon::setTestNow();
    }

    /**
     * ID9
     */
    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.list', [
                'month' => '2026-06',
            ]));

        $response->assertOk();

        $response->assertSee('2026/06');

        Carbon::setTestNow();
    }

    /**
     * ID9
     */
    public function test_「翌月」を押下した時に表示月の前月の情報が表示される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.list', [
                'month' => '2026-08',
            ]));

        $response->assertOk();

        $response->assertSee('2026/08');

        Carbon::setTestNow();
    }

    // =========================
    // 勤怠詳細
    // =========================

    /**
     * ID9
     */
    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-01',
            'clock_in' => '2026-07-01 09:00:00',
            'clock_out' => '2026-07-01 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertOk();

        // 詳細リンクが表示されている
        $response->assertSee(route('attendance.show', ['id' => $attendance->id]));

        // 遷移先が正常に表示される
        $this->actingAs($user)
            ->get(route('attendance.show', ['id' => $attendance->id]))
            ->assertOk();
    }

    /**
     * ID10
     */
    public function test_勤怠詳細画面の「名前」がログインユーザーの氏名になっている(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('山田 太郎');
    }

    /**
     * ID10
     */
    public function test_勤怠詳細画面の「日付」が選択した日付になっている(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('2026年');
        $response->assertSee('7月8日');
    }

    /**
     * ID10
     */
    public function test_「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    /**
     * ID10
     */
    public function test_「休憩」にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertOk();
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    // =========================
    // 修正申請
    // =========================

    /**
     * ID11
     */
    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.show', ['id' => $attendance->id]))
            ->post(route('attendance.correction.store', ['id' => $attendance->id]), [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'note' => '修正申請します',
            ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間が不適切な値です',
        ]);
    }

    /**
     * ID11
     */
    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.show', ['id' => $attendance->id]))
            ->post(route('attendance.correction.store', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '08:00',
                        'break_end' => '12:00',
                    ],
                ],
                'note' => '修正申請します',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * ID11
     */
    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.show', ['id' => $attendance->id]))
            ->post(route('attendance.correction.store', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '17:00',
                        'break_end' => '19:00',
                    ],
                ],
                'note' => '修正申請します',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * ID11
     */
    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.show', ['id' => $attendance->id]))
            ->post(route('attendance.correction.store', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'note' => '',
            ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    /**
     * ID11
     */
    public function test_修正申請処理が実行される(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.correction.store', ['id' => $attendance->id]), [
                'clock_in' => '10:00',
                'clock_out' => '19:00',
                'breaks' => [
                    [
                        'break_start' => '13:00',
                        'break_end' => '14:00',
                    ],
                ],
                'note' => '電車遅延のため修正申請',
            ]);

        $response->assertRedirect(route('attendance.show', ['id' => $attendance->id]));

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 10:00:00',
            'requested_clock_out' => '2026-07-08 19:00:00',
            'note' => '電車遅延のため修正申請',
            'status' => 0,
        ]);

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertDatabaseHas('attendance_correction_breaks', [
            'attendance_correction_request_id' => $correctionRequest->id,
            'requested_break_start' => '2026-07-08 13:00:00',
            'requested_break_end' => '2026-07-08 14:00:00',
            'break_order' => 1,
        ]);
    }

    /**
     * ID11
     */
    public function test_「承認待ち」にログインユーザーが行った申請が全て表示されていること(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $otherUser = User::factory()->create([
            'name' => '佐藤 花子',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
        ]);

        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-09',
        ]);

        $pendingRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '自分の承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 10:00:00',
            'requested_clock_out' => '2026-07-08 19:00:00',
            'note' => '自分の承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $otherUser->id,
            'requested_clock_in' => '2026-07-09 09:00:00',
            'requested_clock_out' => '2026-07-09 18:00:00',
            'note' => '他ユーザーの承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->get(route('request.index', [
                'status' => 'pending',
            ]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('2026/07/08');
        $response->assertSee('自分の承認待ち申請');
        $response->assertSee(route('attendance.show', [
            'id' => $pendingRequest->attendance_id,
        ]));

        $response->assertDontSee('自分の承認済み申請');
        $response->assertDontSee('他ユーザーの承認待ち申請');
        $response->assertDontSee('佐藤 花子');
    }

    /**
     * ID11
     */
    public function test_「承認済み」に管理者が承認した修正申請が全て表示されている(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $otherUser = User::factory()->create([
            'name' => '佐藤 花子',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
        ]);

        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-09',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 09:00:00',
            'requested_clock_out' => '2026-07-08 18:00:00',
            'note' => '自分の承認待ち申請',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $approvedRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 10:00:00',
            'requested_clock_out' => '2026-07-08 19:00:00',
            'note' => '自分の承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $otherUser->id,
            'requested_clock_in' => '2026-07-09 10:00:00',
            'requested_clock_out' => '2026-07-09 19:00:00',
            'note' => '他ユーザーの承認済み申請',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($user)
            ->get(route('request.index', [
                'status' => 'approved',
            ]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('2026/07/08');
        $response->assertSee('自分の承認済み申請');
        $response->assertSee(route('attendance.show', [
            'id' => $approvedRequest->attendance_id,
        ]));

        $response->assertDontSee('自分の承認待ち申請');
        $response->assertDontSee('他ユーザーの承認済み申請');
        $response->assertDontSee('佐藤 花子');
    }

    /**
     * ID11
     */
    public function test_各申請の「詳細」を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-08 10:00:00',
            'requested_clock_out' => '2026-07-08 19:00:00',
            'note' => '修正申請テスト',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->get(route('request.index', [
                'status' => 'pending',
            ]));

        $response->assertOk();

        $response->assertSee(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $this->actingAs($user)
            ->get(route('attendance.show', [
                'id' => $attendance->id,
            ]))
            ->assertOk();
    }
}
