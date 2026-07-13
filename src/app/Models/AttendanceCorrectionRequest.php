<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceCorrectionRequest extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in',
        'requested_clock_out',
        'note',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * モデル属性のキャスト設定を定義する。
     *
     * @return array<string, string> 属性ごとのキャスト設定
     */
    protected function casts(): array
    {
        return [
            'requested_clock_in' => 'datetime',
            'requested_clock_out' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * 勤怠修正申請に紐づく勤怠情報を取得する。
     *
     * @return BelongsTo 勤怠情報とのリレーション
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 勤怠修正申請を行った一般ユーザーを取得する。
     *
     * @return BelongsTo 一般ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤怠修正申請を承認した管理者を取得する。
     *
     * @return BelongsTo 管理者とのリレーション
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    /**
     * 勤怠修正申請に紐づく休憩時間の修正内容を取得する。
     *
     * @return HasMany 休憩時間の修正内容とのリレーション
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionBreak::class);
    }
}
