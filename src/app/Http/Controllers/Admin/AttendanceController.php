<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
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

    public function show($id): View
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        $hasPendingRequest = $attendance->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists();

        return view('admin.attendance.show', compact('attendance', 'hasPendingRequest'));
    }
}
