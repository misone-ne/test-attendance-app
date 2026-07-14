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

    /**
     * 勤怠登録画面を表示し、当日の勤怠情報から現在の勤務ステータスを判定する。
     *
     * @return View 勤怠登録画面
     */
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

    /**
     * ログインユーザーの当日の出勤時刻を登録する。
     *
     * @return \Illuminate\Http\RedirectResponse 勤怠登録画面へのリダイレクト
     */
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

    /**
     * ログインユーザーの当日の休憩開始時刻を登録する。
     *
     * @return \Illuminate\Http\RedirectResponse 勤怠登録画面へのリダイレクト
     */
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

    /**
     * ログインユーザーの進行中の休憩に終了時刻を登録する。
     *
     * @return \Illuminate\Http\RedirectResponse 勤怠登録画面へのリダイレクト
     */
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

    /**
     * ログインユーザーの当日の退勤時刻を登録する。
     *
     * @return \Illuminate\Http\RedirectResponse 勤怠登録画面へのリダイレクト
     */
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

    /**
     * ログインユーザーの指定月の勤怠情報を取得し、勤怠一覧画面を表示する。
     *
     * @param Request $request 表示対象月を含むリクエスト
     * @return View 勤怠一覧画面
     */
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

    /**
     * 指定日の勤怠情報を取得または作成し、勤怠詳細画面へ遷移する。
     *
     * @param string $date 対象日
     * @return \Illuminate\Http\RedirectResponse 勤怠詳細画面へのリダイレクト
     */
    public function showByDate(string $date)
    {
        $attendance = Attendance::firstOrCreate([
            'user_id' => Auth::id(),
            'work_date' => $date,
        ]);

        return redirect()->route('attendance.show', ['id' => $attendance->id]);
    }

    /**
     * ログインユーザーの指定された勤怠情報を取得し、勤怠詳細画面を表示する。
     *
     * @param int $id 対象の勤怠ID
     * @return View 勤怠詳細画面
     */
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

    /**
     * 指定された勤怠情報に対する修正申請と休憩時間の修正内容を登録する。
     *
     * @param AttendanceCorrectionFormRequest $request 修正内容を含むリクエスト
     * @param int $id 対象の勤怠ID
     * @return \Illuminate\Http\RedirectResponse 勤怠詳細画面へのリダイレクト
     */
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

    /**
     * ログインユーザーの修正申請をステータス別に取得し、申請一覧画面を表示する。
     *
     * @return View 修正申請一覧画面
     */
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

    /**
     * 過去6か月の勤怠情報を集計し、基本サマリー、月次推移、異常検知結果を表示する。
     *
     * @return View マイ勤怠レポート画面
     */
    public function report(): View
    {
        $userId = Auth::id();

        // 過去6ヶ月の集計対象期間を設定
        $startMonth = now()->copy()->startOfMonth()->subMonths(5);
        $endMonth = now()->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$startMonth, $endMonth])
            ->get();


        // 基本サマリーを計算
        $totalWorkMinutes = $attendances->sum('work_minutes');

        $totalOvertimeMinutes = $attendances->sum(function ($attendance) {
            return max($attendance->work_minutes - 480, 0);
        });

        $workedDays = $attendances->filter(fn($attendance) => $attendance->work_minutes > 0)->count();

        $averageWorkMinutes = $workedDays > 0
            ? floor($totalWorkMinutes / $workedDays)
            : 0;


        // 月別の労働時間・残業時間を集計
        $monthlyReports = collect(range(0, 5))
            ->map(function (int $monthOffset) use ($startMonth, $attendances) {
                $month = $startMonth->copy()->addMonths($monthOffset);

                $monthlyAttendances = $attendances->filter(
                    fn($attendance) =>
                    $attendance->work_date->format('Y-m') === $month->format('Y-m')
                );

                $monthlyWorkMinutes = $monthlyAttendances->sum('work_minutes');

                $monthlyOvertimeMinutes = $monthlyAttendances->sum(
                    fn($attendance) => max($attendance->work_minutes - 480, 0)
                );

                return [
                    'month' => $month->format('Y-m'),
                    'work_time' => $this->formatMinutes($monthlyWorkMinutes),
                    'overtime' => $this->formatMinutes($monthlyOvertimeMinutes),
                ];
            });


        // 今月の異常検知データを集計
        $currentMonthAttendances = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth(),
            ])
            ->get();

        $anomalies = [
            'late_count' => $currentMonthAttendances->filter(function ($attendance) {
                return $attendance->clock_in
                    && $attendance->clock_in->format('H:i:s') > '09:00:00';
            })->count(),

            'early_leave_count' => $currentMonthAttendances->filter(function ($attendance) {
                return $attendance->clock_out
                    && $attendance->clock_out->format('H:i:s') < '18:00:00';
            })->count(),

            'long_work_count' => $currentMonthAttendances->filter(function ($attendance) {
                return $attendance->work_minutes > 600;
            })->count(),
        ];


        // 基本サマリーを表示用データに変換
        $summary = [
            'total_work_time' => $this->formatMinutes($totalWorkMinutes),
            'total_overtime' => $this->formatMinutes($totalOvertimeMinutes),
            'average_work_time' => $this->formatMinutes($averageWorkMinutes),
        ];


        return view('attendance.report', compact('summary', 'monthlyReports', 'anomalies'));
    }


    /**
     * 分単位の時間を「〇h 〇m」形式の文字列へ変換する。
     *
     * @param int $minutes 変換対象の分数
     * @return string 時間と分で表した文字列
     */
    private function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }
}
