<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 管理者によるすべての勤怠情報操作を許可する。
     *
     * @param User|Admin $user 認証ユーザーまたは管理者
     * @param string $ability 実行する認可アクション
     * @return bool|null 管理者の場合はtrue、それ以外は後続の認可判定を行うためnull
     */
    public function before(User|Admin $user, string $ability): bool|null
    {
        if ($user instanceof Admin) {
            return true;
        }

        return null;
    }

    /**
     * 一般ユーザーが本人の勤怠情報を更新できるか判定する。
     *
     * @param User $user 認証ユーザー
     * @param Attendance $attendanceRecord 対象の勤怠情報
     * @return bool 更新を許可する場合はtrue
     */
    public function update(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 一般ユーザーが本人の勤怠情報を削除できるか判定する。
     *
     * @param User $user 認証ユーザー
     * @param Attendance $attendanceRecord 対象の勤怠情報
     * @return bool 削除を許可する場合はtrue
     */
    public function delete(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
