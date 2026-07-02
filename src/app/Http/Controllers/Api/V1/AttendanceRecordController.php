<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexAttendanceRecordRequest $request)
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
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceRecordRequest $request)
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
     * Display the specified resource.
     */
    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'breakTimes',
            'correctionRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord)
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
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
