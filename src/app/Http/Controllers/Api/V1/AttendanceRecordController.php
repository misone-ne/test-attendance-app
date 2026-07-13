<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    /**
     * 検索条件に一致する勤怠情報を一覧で取得する。
     *
     * @param IndexAttendanceRecordRequest $request 検索条件を含むリクエスト
     * @return AnonymousResourceCollection 勤怠情報一覧
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 20);

        $attendances = Attendance::with(['user'])
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('work_date', $request->date);
            })
            ->when($request->filled('month'), function ($query) use ($request) {
                $query->whereMonth('work_date', substr($request->month, 5, 2))
                    ->whereYear('work_date', substr($request->month, 0, 4));
            })
            ->latest('work_date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($attendances);
    }

    /**
     * 認証ユーザーの勤怠情報を新規登録する。
     *
     * @param StoreAttendanceRecordRequest $request 登録する勤怠情報を含むリクエスト
     * @return JsonResponse 登録した勤怠情報を含むJSONレスポンス
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendanceRecord = $request->user()
            ->attendances()
            ->create([
                'work_date' => $validated['date'],
                'clock_in' => $validated['clock_in'],
                'clock_out' => $validated['clock_out'] ?? null,
                'note' => $validated['comment'] ?? null,
            ]);

        $attendanceRecord->load([
            'user',
            'breakTimes',
        ]);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 指定された勤怠情報の詳細を取得する。
     *
     * @param Attendance $attendanceRecord 対象の勤怠情報
     * @return AttendanceRecordResource 勤怠詳細情報
     */
    public function show(Attendance $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定された勤怠情報を更新する。
     *
     * @param UpdateAttendanceRecordRequest $request 更新する勤怠情報を含むリクエスト
     * @param Attendance $attendanceRecord 対象の勤怠情報
     * @return AttendanceRecordResource 更新後の勤怠情報
     */
    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();

        $attendanceRecord->update([
            'work_date' => $validated['date'],
            'clock_in' => $validated['clock_in'],
            'clock_out' => $validated['clock_out'] ?? null,
            'note' => $validated['comment'] ?? null,
        ]);

        $attendanceRecord->load([
            'user',
            'breakTimes',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定された勤怠情報を削除する。
     *
     * @param Attendance $attendanceRecord 対象の勤怠情報
     * @return Response 内容を含まないレスポンス
     */
    public function destroy(Attendance $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
