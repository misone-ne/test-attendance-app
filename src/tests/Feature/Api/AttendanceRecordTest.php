<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID17
     * GET /api/v1/attendance-records で勤怠一覧が JSON で取得できる
     */
    public function test_GET_api_v1_attendance_recordsで勤怠一覧がJSONで取得できる(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '通常勤務',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $attendance->id);
        $response->assertJsonPath('data.0.user_id', $user->id);
        $response->assertJsonPath('data.0.user_name', '山田 太郎');
        $response->assertJsonPath('data.0.date', '2026-07-10');
        $response->assertJsonPath('data.0.clock_in', '09:00:00');
        $response->assertJsonPath('data.0.clock_out', '18:00:00');
        $response->assertJsonPath('data.0.comment', '通常勤務');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'user_name',
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                ],
            ],
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);
    }

    /**
     * ID17
     * GET /api/v1/attendance-records/{attendanceRecord} で勤怠詳細が JSON で取得できる
     */
    public function test_GET_api_v1_attendance_records_idで勤怠詳細がJSONで取得できる(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '通常勤務',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $attendance->id);
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.user_name', '山田 太郎');
        $response->assertJsonPath('data.date', '2026-07-10');
        $response->assertJsonPath('data.clock_in', '09:00:00');
        $response->assertJsonPath('data.clock_out', '18:00:00');
        $response->assertJsonPath('data.comment', '通常勤務');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user_name',
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks',
                'applications',
            ],
        ]);
    }

    /**
     * ID17
     */
    public function test_存在しないIDでは404とエラーJSONが返る(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/999999');

        $response->assertNotFound();

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * ID18
     * POST /api/v1/attendance-records で勤怠が作成される
     */
    public function test_POST_api_v1_attendance_recordsで勤怠が作成される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response->assertCreated();

        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.date', '2026-07-11');
        $response->assertJsonPath('data.clock_in', '09:00:00');
        $response->assertJsonPath('data.clock_out', '18:00:00');
        $response->assertJsonPath('data.comment', '通常勤務');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-11 00:00:00',
            'note' => '通常勤務',
        ]);
    }

    /**
     * ID18
     */
    public function test_バリデーションエラー時に422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'date',
            'clock_in',
        ]);

        $response->assertJsonPath(
            'errors.date.0',
            '勤怠日は必須です。'
        );

        $response->assertJsonPath(
            'errors.clock_in.0',
            '出勤時刻は必須です。'
        );
    }

    /**
     * ID18
     * PUT /api/v1/attendance-records/{attendanceRecord} で勤怠が更新される
     * 正常系
     */
    public function test_PUT_api_v1_attendance_records_attendanceRecordで勤怠が更新される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '更新前',
        ]);

        $response = $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'date' => '2026-07-11',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '更新後',
        ]);

        $response->assertOk();

        $response->assertJsonPath('data.id', $attendance->id);
        $response->assertJsonPath('data.date', '2026-07-11');
        $response->assertJsonPath('data.clock_in', '10:00:00');
        $response->assertJsonPath('data.clock_out', '19:00:00');
        $response->assertJsonPath('data.comment', '更新後');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'work_date' => '2026-07-11 00:00:00',
            'note' => '更新後',
        ]);
    }

    /**
     * ID18
     * PUT /api/v1/attendance-records/{attendanceRecord} で勤怠が更新される
     * 存在しないID
     */
    public function test_PUTで存在しないIDを指定すると404が返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/attendance-records/999999', [
            'date' => '2026-07-11',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '更新後',
        ]);

        $response->assertNotFound();

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * ID18
     * DELETE /api/v1/attendance-records/{attendanceRecord} で勤怠が削除される
     * 正常系
     */
    public function test_DELETE_api_v1_attendance_records_attendanceRecordで勤怠が削除される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '削除対象',
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /**
     * ID18
     * DELETE /api/v1/attendance-records/{attendanceRecord} で勤怠が削除される
     * 存在しないID
     */
    public function test_DELETEで存在しないIDを指定すると404が返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/999999'
        );

        $response->assertNotFound();

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * ID19
     */
    public function test_未認証時に書き込み系APIで401が返る(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => 'テスト勤怠',
        ]);

        $postResponse = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-11',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '新規登録',
        ]);

        $postResponse->assertUnauthorized();
        $postResponse->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'date' => '2026-07-10',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '更新後',
            ]
        );

        $putResponse->assertUnauthorized();
        $putResponse->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $deleteResponse->assertUnauthorized();
        $deleteResponse->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * ID19
     */
    public function test_認証済みユーザーは自分の勤怠を更新・削除できる(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $updateAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '更新前',
        ]);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$updateAttendance->id}",
            [
                'date' => '2026-07-10',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '更新後',
            ]
        );

        $putResponse->assertOk();

        $deleteAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-11',
            'clock_in' => '2026-07-11 09:00:00',
            'clock_out' => '2026-07-11 18:00:00',
            'note' => '削除対象',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$deleteAttendance->id}"
        );

        $deleteResponse->assertNoContent();
    }

    /**
     * ID19
     */
    public function test_他ユーザーの勤怠を更新・削除しようとすると403が返る(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $attendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-10',
            'clock_in' => '2026-07-10 09:00:00',
            'clock_out' => '2026-07-10 18:00:00',
            'note' => '他ユーザーの勤怠',
        ]);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'date' => '2026-07-10',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '不正更新',
            ]
        );

        $putResponse->assertForbidden();

        $putResponse->assertJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $deleteResponse->assertForbidden();

        $deleteResponse->assertJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);
    }
}
