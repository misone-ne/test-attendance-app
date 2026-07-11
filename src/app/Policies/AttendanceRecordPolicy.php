<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 管理者にはすべての操作を許可する
     */
    public function before(User|Admin $user, string $ability): bool|null
    {
        if ($user instanceof Admin) {
            return true;
        }

        return null;
    }

    /**
     * 一般ユーザーは本人の勤怠のみ更新できる
     */
    public function update(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 一般ユーザーは本人の勤怠のみ削除できる
     */
    public function delete(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
