@extends('layouts.app')

@section('header-nav')
<nav class="header__nav">
    <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a>
    <a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="#" class="header__nav-link">申請</a>

    <form method="POST" action="{{ route('logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">
            ログアウト
        </button>
    </form>
</nav>
@endsection

@section('content')
<div class="attendance-list">
    <h2 class="page-title">勤怠一覧</h2>

    <div class="attendance-list__month-nav">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="attendance-list__month-link">
            <img src="{{ asset('images/矢印.png') }}" alt="" class="attendance-list__arrow-icon">
            <span>前月</span></a>

        <div class="attendance-list__month-center">
            <img src="{{ asset('images/カレンダー.png') }}" alt="" class="attendance-list__calendar-icon">
            <p class="attendance-list__month">
                {{ $currentMonth->format('Y/m') }}
            </p>
        </div>

        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-list__month-link">
            <span>翌月</span>
            <img src="{{ asset('images/矢印.png') }}" alt="" class="attendance-list__arrow-icon attendance-list__arrow-icon--next">
        </a>
    </div>

    <table class="attendance-list__table">
        <thead class="attendance-list__thead">
            <tr class="attendance-list__row">

                <th class="attendance-list__header">
                    日付
                </th>

                <th class="attendance-list__header">
                    出勤
                </th>

                <th class="attendance-list__header">
                    退勤
                </th>

                <th class="attendance-list__header">
                    休憩
                </th>

                <th class="attendance-list__header">
                    合計
                </th>

                <th class="attendance-list__header">
                    詳細
                </th>

            </tr>
        </thead>

        <tbody class="attendance-list__tbody">
            @foreach ($dates as $date)
            @php
            $attendance = $attendances->get($date->format('Y-m-d'));
            @endphp

            <tr class="attendance-list__row">
                <td class="attendance-list__cell">
                    {{ $date->isoFormat('MM/DD(ddd)') }}
                </td>

                <td class="attendance-list__cell">
                    {{ $attendance?->clock_in?->format('H:i') }}
                </td>

                <td class="attendance-list__cell">
                    {{ $attendance?->clock_out?->format('H:i') }}
                </td>

                <td class="attendance-list__cell">
                    {{ $attendance?->formatted_break_time }}
                </td>

                <td class="attendance-list__cell">
                    {{ $attendance?->formatted_work_time }}
                </td>

                <td class="attendance-list__cell">
                    @if ($attendance)
                    <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="attendance-list__detail-link">
                        詳細
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection