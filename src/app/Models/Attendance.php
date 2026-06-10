<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    // 休憩時間計算
    public function getBreakMinutesAttribute(): int
    {
        return $this->breakTimes->sum(function ($breakTime) {

            if (!$breakTime->break_end) {
                return 0;
            }

            return $breakTime->break_start->diffInMinutes(
                $breakTime->break_end
            );
        });
    }

    // 休憩時間表示変換
    public function getFormattedBreakTimeAttribute(): string
    {
        $hours = floor($this->break_minutes / 60);
        $minutes = $this->break_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    // 勤務時間計算
    public function getWorkMinutesAttribute(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        return $this->clock_in->diffInMinutes($this->clock_out) - $this->break_minutes;
    }

    // 勤務時間表示変換
    public function getFormattedWorkTimeAttribute(): string
    {
        $hours = floor($this->work_minutes / 60);
        $minutes = $this->work_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
