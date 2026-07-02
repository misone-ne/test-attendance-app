<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function update(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    public function delete(User $user, Attendance $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
