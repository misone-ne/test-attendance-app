@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@section('header-nav')
<nav class="header__nav">
    <a href="{{ route('admin.attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="{{ route('admin.staff.list') }}" class="header__nav-link">スタッフ一覧</a>
    <a href="{{ route('request.index') }}" class="header__nav-link">申請一覧</a>

    <form method="POST" action="{{ route('admin.logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">
            ログアウト
        </button>
    </form>
</nav>
@endsection

@section('content')
<div class="attendance-list">
    <h2 class="page-title">
        {{ $date->format('Y年n月j日') }}の勤怠
    </h2>

    <div class="attendance-list__nav">
        <a href="{{ route('admin.attendance.list', ['date' => $previousDate->format('Y-m-d')]) }}" class="attendance-list__link">
            <img src="{{ asset('images/矢印.png') }}" alt="" class="attendance-list__arrow-icon">
            <span>前日</span>
        </a>

        <div class="attendance-list__month-center">
            <img src="{{ asset('images/カレンダー.png') }}" alt="" class="attendance-list__calendar-icon">
            <p class="attendance-list__table-title">
                {{ $date->format('Y/m/d') }}
            </p>
        </div>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}" class="attendance-list__link">
            <span>翌日</span>
            <img src="{{ asset('images/矢印.png') }}" alt="" class="attendance-list__arrow-icon attendance-list__arrow-icon--next">
        </a>
    </div>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th class="attendance-list__header">名前</th>
                <th class="attendance-list__header">出勤</th>
                <th class="attendance-list__header">退勤</th>
                <th class="attendance-list__header">休憩</th>
                <th class="attendance-list__header">合計</th>
                <th class="attendance-list__header">詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($attendances as $attendance)
            <tr>
                <td class="attendance-list__cell">{{ $attendance->user->name }}</td>
                <td class="attendance-list__cell">{{ $attendance->clock_in?->format('H:i') }}</td>
                <td class="attendance-list__cell">{{ $attendance->clock_out?->format('H:i') }}</td>
                <td class="attendance-list__cell">{{ $attendance->formatted_break_time }}</td>
                <td class="attendance-list__cell">{{ $attendance->formatted_work_time }}</td>
                <td class="attendance-list__cell">
                    <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__detail-link">
                        詳細
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection