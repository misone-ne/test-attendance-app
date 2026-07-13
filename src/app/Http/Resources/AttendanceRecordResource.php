<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * 勤怠情報をAPIレスポンス用の配列形式へ変換する。
     *
     * @param Request $request 現在のHTTPリクエスト
     * @return array<string, mixed> APIレスポンス用の勤怠情報
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn() => $this->user->name),
            'date' => $this->work_date?->format('Y-m-d'),
            'clock_in' => $this->clock_in?->format('H:i:s'),
            'clock_out' => $this->clock_out?->format('H:i:s'),
            'total_time' => $this->formatted_work_time,
            'total_break_time' => $this->formatted_break_time,
            'comment' => $this->note,

            'breaks' => $this->when(
                $request->routeIs('attendance-records.show'),
                function () {
                    return $this->breakTimes->map(function ($breakTime) {
                        return [
                            'id' => $breakTime->id,
                            'break_in' => $breakTime->break_start?->format('H:i:s'),
                            'break_out' => $breakTime->break_end?->format('H:i:s'),
                        ];
                    });
                }
            ),

            'applications' => $this->when(
                $request->routeIs('attendance-records.show'),
                function () {
                    return $this->correctionRequests->map(function ($correctionRequest) {
                        return [
                            'id' => $correctionRequest->id,
                            'requested_clock_in' => $correctionRequest->requested_clock_in?->format('H:i:s'),
                            'requested_clock_out' => $correctionRequest->requested_clock_out?->format('H:i:s'),
                            'comment' => $correctionRequest->note,
                            'status' => $correctionRequest->status,
                        ];
                    });
                }
            ),
        ];
    }
}
