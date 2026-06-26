<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionFormRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{


    public function index(): View
    {
        $now = now();

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        // 今日の勤怠情報から現在の勤務ステータスを判定
        if (!$attendance) {
            $status = 'off';
        } elseif ($attendance->clock_out) {
            $status = 'finished';
        } elseif ($attendance->breakTimes()
            ->whereNull('break_end')
            ->exists()
        ) {
            $status = 'break';
        } else {
            $status = 'working';
        }

        return view('attendance.index', compact('now', 'status'));
    }


    public function clockIn()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        // 「出勤」は1日1回のみ
        if ($attendance) {
            return redirect()->route('attendance.index');
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        return redirect()->route('attendance.index');
    }


    public function breakStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        return redirect()->route('attendance.index');
    }


    public function breakEnd()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        $breakTime = $attendance->breakTimes()
            ->whereNull('break_end')
            ->latest()
            ->first();

        if (!$breakTime) {
            return redirect()->route('attendance.index');
        }

        $breakTime->update([
            'break_end' => now(),
        ]);

        return redirect()->route('attendance.index');
    }


    public function clockOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index');
        }

        // 退勤後の再POSTを防止
        if ($attendance->clock_out) {
            return redirect()->route('attendance.index');
        }

        // 休憩中の退勤を防止
        if ($attendance->breakTimes()
            ->whereNull('break_end')
            ->exists()
        ) {
            return redirect()->route('attendance.index');
        }

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->route('attendance.index');
    }


    // 勤怠一覧
    public function list(Request $request): View
    {
        // URLのmonth有無で表示対象月を決定
        $currentMonth = $request->filled('month')
            ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)
            : now();

        // 表示対象月を基準に前後月のリンクを設定
        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 表示対象月の開始日・終了日を取得
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // ログインユーザーの対象月勤怠データ + 休憩データを日付キーで取得
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', Auth::id())
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        // 対象月の全日付を作成
        $dates = collect();

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('attendance.list', compact(
            'currentMonth',
            'previousMonth',
            'nextMonth',
            'attendances',
            'dates'
        ));
    }


    public function showByDate(string $date)
    {
        $attendance = Attendance::firstOrCreate([
            'user_id' => Auth::id(),
            'work_date' => $date,
        ]);

        return redirect()->route('attendance.show', ['id' => $attendance->id]);
    }


    public function show($id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->findOrFail($id);

        $hasPendingRequest = $attendance->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists();

        return view('attendance.show', compact('attendance', 'hasPendingRequest'));
    }


    public function storeCorrectionRequest(AttendanceCorrectionFormRequest $request, $id)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('id', $id)
            ->findOrFail($id);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'requested_clock_in' => $attendance->work_date->format('Y-m-d') . ' ' . $request->clock_in,
            'requested_clock_out' => $attendance->work_date->format('Y-m-d') . ' ' . $request->clock_out,
            'note' => $request->note,
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        foreach ($request->input('breaks', []) as $index => $break) {
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            AttendanceCorrectionBreak::create([
                'attendance_correction_request_id' => $correctionRequest->id,
                'requested_break_start' => $attendance->work_date->format('Y-m-d') . ' ' . $break['break_start'],
                'requested_break_end' => $attendance->work_date->format('Y-m-d') . ' ' . $break['break_end'],
                'break_order' => $index + 1,
            ]);
        }

        return redirect()->route('attendance.show', ['id' => $attendance->id]);
    }


    public function requestList(): View
    {
        $status = request('status', 'pending');

        $requestStatus = $status === 'approved'
            ? AttendanceCorrectionRequest::STATUS_APPROVED
            : AttendanceCorrectionRequest::STATUS_PENDING;

        $requests = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', Auth::id())
            ->where('status', $requestStatus)
            ->latest()
            ->get();

        return view('attendance.request-list', compact('requests', 'status'));
    }
}
