<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{

    use RefreshDatabase;

    /**
     * ID12
     */
    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user1 = User::factory()->create(['name' => '山田 太郎']);
        $user2 = User::factory()->create(['name' => '佐藤 花子']);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 10:00:00',
            'clock_out' => '2026-07-08 19:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-09',
            'clock_in' => '2026-07-09 08:00:00',
            'clock_out' => '2026-07-09 17:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list', ['date' => '2026-07-08']));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        $response->assertSee('佐藤 花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    /**
     * ID12
     */
    public function test_遷移した際に現在の日付が表示される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-08 12:00:00'));

        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list'));

        $response->assertOk();
        $response->assertSee('2026/07/08');

        Carbon::setTestNow();
    }

    /**
     * ID12
     */
    public function test_「前日」を押下した時に前の日の勤怠情報が表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list', ['date' => '2026-07-07']));

        $response->assertOk();
        $response->assertSee('2026/07/07');
    }

    /**
     * ID12
     */
    public function test_「翌日」を押下した時に次の日の勤怠情報が表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.list', ['date' => '2026-07-09']));

        $response->assertOk();
        $response->assertSee('2026/07/09');
    }

    /**
     * ID13
     */
    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
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
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-08 12:00:00',
            'break_end' => '2026-07-08 13:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', ['id' => $attendance->id]));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('2026年');
        $response->assertSee('7月8日');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * ID13
     */
    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->put(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'note' => '管理者修正',
            ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * ID13
     */
    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->put(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '08:00',
                        'break_end' => '12:00',
                    ],
                ],
                'note' => '管理者修正',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * ID13
     */
    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->put(route('admin.attendance.update', ['id' => $attendance->id]), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '17:00',
                        'break_end' => '19:00',
                    ],
                ],
                'note' => '管理者修正',
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * ID13
     */
    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', ['id' => $attendance->id]))
            ->put(route('admin.attendance.update', ['id' => $attendance->id]), [
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
     * ID14
     */
    public function test_管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user1 = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤 花子',
            'email' => 'hanako@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.list'));

        $response->assertOk();

        $response->assertSee('山田 太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee(route('admin.staff.attendance.list', [
            'id' => $user1->id,
        ]));

        $response->assertSee('佐藤 花子');
        $response->assertSee('hanako@example.com');
        $response->assertSee(route('admin.staff.attendance.list', [
            'id' => $user2->id,
        ]));
    }

    /**
     * ID14
     */
    public function test_ユーザーの勤怠情報が正しく表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $otherUser = User::factory()->create();

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

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 08:00:00',
            'clock_out' => '2026-07-08 17:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendance.list', [
                'id' => $user->id,
                'month' => '2026-07',
            ]));

        $response->assertOk();

        $response->assertSee('07/08');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    /**
     * ID14
     */
    public function test_「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendance.list', [
                'id' => $user->id,
                'month' => '2026-06',
            ]));

        $response->assertOk();
        $response->assertSee('2026/06');
    }

    /**
     * ID14
     */
    public function test_「翌月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendance.list', [
                'id' => $user->id,
                'month' => '2026-08',
            ]));

        $response->assertOk();
        $response->assertSee('2026/08');
    }

    /**
     * ID14
     */
    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-08',
            'clock_in' => '2026-07-08 09:00:00',
            'clock_out' => '2026-07-08 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.attendance.list', [
                'id' => $user->id,
                'month' => '2026-07',
            ]));

        $response->assertOk();

        $response->assertSee(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance.show', [
                'id' => $attendance->id,
            ]))
            ->assertOk();
    }
}
