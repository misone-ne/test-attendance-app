<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
