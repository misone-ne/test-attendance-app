@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@section('content')
<div class="admin-attendance-list">
    <h2 class="page-title">
        {{ $date->format('Y年n月j日') }}の勤怠
    </h2>

    <div class="admin-attendance-list__date-nav">
        <a href="{{ route('admin.attendance.list', ['date' => $previousDate->format('Y-m-d')]) }}">
            ← 前日
        </a>

        <span>{{ $date->format('Y/m/d') }}</span>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}">
            翌日 →
        </a>
    </div>

    <table class="admin-attendance-list__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->clock_in?->format('H:i') }}</td>
                <td>{{ $attendance->clock_out?->format('H:i') }}</td>
                <td>{{ $attendance->formatted_break_time }}</td>
                <td>{{ $attendance->formatted_work_time }}</td>
                <td><a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection