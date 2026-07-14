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
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * 指定日の全スタッフの勤怠情報を取得し、管理者用勤怠一覧画面を表示する。
     *
     * @param Request $request 表示対象日を含むリクエスト
     * @return View 管理者用勤怠一覧画面
     */
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

    /**
     * 一般ユーザーを一覧で取得し、スタッフ一覧画面を表示する。
     *
     * @return View スタッフ一覧画面
     */
    public function staffList(): View
    {
        $users = User::orderBy('id')->get();

        return view('admin.staff.index', compact('users'));
    }

    /**
     * 指定ユーザー・指定日の勤怠情報を取得または作成し、管理者用勤怠詳細画面へ遷移する。
     *
     * @param int $user_id 対象ユーザーのID
     * @param string $date 対象日
     * @return \Illuminate\Http\RedirectResponse 管理者用勤怠詳細画面へのリダイレクト
     */
    public function showByDate(int $user_id, string $date)
    {
        $attendance = Attendance::firstOrCreate([
            'user_id' => $user_id,
            'work_date' => $date,
        ]);

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
    }

    /**
     * 指定された勤怠情報を取得し、管理者用勤怠詳細画面を表示する。
     *
     * @param int $id 対象の勤怠ID
     * @return View 管理者用勤怠詳細画面
     */
    public function show($id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        $hasPendingRequest = $attendance->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists();

        return view('admin.attendance.show', compact('attendance', 'hasPendingRequest'));
    }

    /**
     * 指定スタッフの指定月の勤怠情報を取得し、スタッフ別勤怠一覧画面を表示する。
     *
     * @param Request $request 表示対象月を含むリクエスト
     * @param int $id 対象スタッフのID
     * @return View スタッフ別勤怠一覧画面
     */
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

    /**
     * 指定された勤怠情報と休憩情報を管理者操作によって更新する。
     *
     * @param AdminAttendanceUpdateRequest $request 更新内容を含むリクエスト
     * @param int $id 対象の勤怠ID
     * @return \Illuminate\Http\RedirectResponse 更新元画面へのリダイレクト
     */
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

    /**
     * 全ユーザーの勤怠修正申請をステータス別に取得し、申請一覧画面を表示する。
     *
     * @return View 管理者用申請一覧画面
     */
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

    /**
     * 指定された勤怠修正申請を取得し、承認画面を表示する。
     *
     * @param int $attendance_correct_request_id 対象の勤怠修正申請ID
     * @return View 勤怠修正申請の承認画面
     */
    public function approveShow(int $attendance_correct_request_id): View
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'attendance.user',
            'breaks',
        ])->findOrFail($attendance_correct_request_id);

        return view('admin.request.approve', compact('correctionRequest'));
    }

    /**
     * 指定された勤怠修正申請を承認し、申請内容を勤怠情報へ反映する。
     *
     * @param int $attendance_correct_request_id 対象の勤怠修正申請ID
     * @return \Illuminate\Http\RedirectResponse 承認画面へのリダイレクト
     */
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

    /**
     * 指定スタッフの指定月の勤怠情報をCSV形式で出力する。
     *
     * @param Request $request 出力対象月を含むリクエスト
     * @param int $id 対象スタッフのID
     * @return StreamedResponse CSVファイルのダウンロードレスポンス
     */
    public function staffAttendanceCsv(Request $request, int $id): StreamedResponse
    {
        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        $dates = collect();

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates->push($date->copy());
        }

        $fileName = 'attendance_' . $id . '_' . $currentMonth->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($dates, $attendances) {
            $stream = fopen('php://output', 'w');

            // Excelで文字化けしないようにUTF-8 BOMを付与
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

            $dates->each(function ($date) use ($stream, $attendances) {
                $attendance = $attendances->get($date->format('Y-m-d'));

                fputcsv($stream, [
                    $date->isoFormat('MM/DD(ddd)'),
                    $attendance?->clock_in?->format('H:i') ?? '',
                    $attendance?->clock_out?->format('H:i') ?? '',
                    $attendance?->formatted_break_time ?? '',
                    $attendance?->formatted_work_time ?? '',
                ]);
            });

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
