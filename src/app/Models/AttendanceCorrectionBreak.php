<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionBreak extends Model
{
    protected $fillable = [
        'attendance_correction_request_id',
        'requested_break_start',
        'requested_break_end',
        'break_order',
    ];

    /**
     * モデル属性のキャスト設定を定義する。
     *
     * @return array<string, string> 属性ごとのキャスト設定
     */
    protected function casts(): array
    {
        return [
            'requested_break_start' => 'datetime',
            'requested_break_end' => 'datetime',
        ];
    }

    /**
     * 休憩時間の修正内容に紐づく勤怠修正申請を取得する。
     *
     * @return BelongsTo 勤怠修正申請とのリレーション
     */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class
        );
    }
}
