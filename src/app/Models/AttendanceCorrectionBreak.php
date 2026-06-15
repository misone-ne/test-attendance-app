<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionBreak extends Model
{
    protected $fillable = [
        'attendance_correction_request_id',
        'requested_break_start',
        'requested_break_end',
        'break_order',
    ];

    protected function casts(): array
    {
        return [
            'requested_break_start' => 'datetime',
            'requested_break_end' => 'datetime',
        ];
    }

    public function correctionRequest()
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class
        );
    }
}
