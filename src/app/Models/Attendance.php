<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'note',
    ];

    /**
     * モデル属性のキャスト設定を定義する。
     *
     * @return array<string, string> 属性ごとのキャスト設定
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    /**
     * 勤怠情報に紐づく一般ユーザーを取得する。
     *
     * @return BelongsTo 一般ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤怠情報に紐づく休憩情報を取得する。
     *
     * @return HasMany 休憩情報とのリレーション
     */
    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 勤怠情報に紐づく勤怠修正申請を取得する。
     *
     * @return HasMany 勤怠修正申請とのリレーション
     */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * 勤怠情報に紐づく休憩時間の合計を分単位で計算する。
     *
     * @return int 合計休憩時間（分）
     */
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

    /**
     * 合計休憩時間を「時:分」形式の文字列へ変換する。
     *
     * @return string 表示用の合計休憩時間
     */
    public function getFormattedBreakTimeAttribute(): string
    {
        $hours = floor($this->break_minutes / 60);
        $minutes = $this->break_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 出退勤時間から合計休憩時間を差し引き、勤務時間を分単位で計算する。
     *
     * @return int 勤務時間（分）
     */
    public function getWorkMinutesAttribute(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        return $this->clock_in->diffInMinutes($this->clock_out) - $this->break_minutes;
    }

    /**
     * 勤務時間を「時:分」形式の文字列へ変換する。
     *
     * @return string 表示用の勤務時間
     */
    public function getFormattedWorkTimeAttribute(): string
    {
        $hours = floor($this->work_minutes / 60);
        $minutes = $this->work_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
