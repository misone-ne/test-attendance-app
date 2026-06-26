<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{

    public function index(Request $request): View
    {
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : today();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $date)
            ->orderBy('user_id')
            ->get();

        $previousDate = $date->copy()->subDay();
        $nextDate = $date->copy()->addDay();

        return view('admin.attendance.index', compact(
            'date',
            'attendances',
            'previousDate',
            'nextDate'
        ));
    }


    public function staffList(): View
    {
        $users = User::orderBy('id')->get();

        return view('admin.staff.index', compact('users'));
    }


    public function showByDate(int $user_id, string $date)
    {
        $attendance = Attendance::firstOrCreate([
            'user_id' => $user_id,
            'work_date' => $date,
        ]);

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
    }


    public function show($id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        $hasPendingRequest = $attendance->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists();

        return view('admin.attendance.show', compact('attendance', 'hasPendingRequest'));
    }


    public function staffAttendanceList(Request $request, $id): View
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        $dates = collect();

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('admin.staff.attendance', compact('user', 'currentMonth', 'previousMonth', 'nextMonth', 'attendances', 'dates'));
    }


    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        if (
            $attendance->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists()
        ) {
            return back();
        }

        $workDate = $attendance->work_date->format('Y-m-d');

        $attendance->update([
            'clock_in' => $workDate . ' ' . $request->clock_in,
            'clock_out' => $workDate . ' ' . $request->clock_out,
            'note' => $request->note,
        ]);

        foreach ($request->input('breaks', []) as $index => $break) {

            // 休憩開始・終了どちらも空ならスキップ
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            $breakTime = $attendance->breakTimes[$index] ?? null;

            if ($breakTime) {

                $breakTime->update([
                    'break_start' => $workDate . ' ' . $break['break_start'],
                    'break_end' => $workDate . ' ' . $break['break_end'],
                ]);
            } else {

                $attendance->breakTimes()->create([
                    'break_start' => $workDate . ' ' . $break['break_start'],
                    'break_end' => $workDate . ' ' . $break['break_end'],
                ]);
            }
        }

        return back();
    }


    public function requestList(): View
    {
        $status = request('status', 'pending');

        $requestStatus = $status === 'approved'
            ? AttendanceCorrectionRequest::STATUS_APPROVED
            : AttendanceCorrectionRequest::STATUS_PENDING;

        $requests = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('status', $requestStatus)
            ->latest()
            ->get();

        return view('admin.request.index', compact('requests', 'status'));
    }

    public function approveShow(int $attendance_correct_request_id): View
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.user',
            'breaks',
        ])->findOrFail($attendance_correct_request_id);

        return view('admin.request.approve', compact('correctionRequest'));
    }


    public function approve(int $attendance_correct_request_id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.breakTimes',
            'breaks',
        ])->findOrFail($attendance_correct_request_id);

        if ($correctionRequest->status === AttendanceCorrectionRequest::STATUS_APPROVED) {
            return redirect()->route('admin.request.approve.show', ['attendance_correct_request_id' => $correctionRequest->id,]);
        }

        DB::transaction(function () use ($correctionRequest) {
            $attendance = $correctionRequest->attendance;

            $attendance->update([
                'clock_in' => $correctionRequest->requested_clock_in,
                'clock_out' => $correctionRequest->requested_clock_out,
                'note' => $correctionRequest->note,
            ]);

            $attendance->breakTimes()->delete();

            foreach ($correctionRequest->breaks as $break) {
                $attendance->breakTimes()->create([
                    'break_start' => $break->requested_break_start,
                    'break_end' => $break->requested_break_end,
                ]);
            }

            $correctionRequest->update([
                'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
                'approved_by' => auth('admin')->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('admin.request.approve.show', ['attendance_correct_request_id' => $correctionRequest->id,]);
    }
}
