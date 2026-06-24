@extends('layouts.app')

@section('title', '勤怠詳細')

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
<div class="attendance-detail">

    <h2 class="page-title">勤怠詳細</h2>

    <form method="POST" action="{{ route('admin.request.approve', ['attendance_correct_request_id' => $correctionRequest->id]) }}" class="attendance-detail__form">
        @csrf

        <div class="attendance-detail__card">
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    名前
                </div>

                <div class="attendance-detail__value">
                    {{ $correctionRequest->user->name }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    日付
                </div>

                <div class="attendance-detail__value">
                    {{ $correctionRequest->attendance->work_date->isoFormat('YYYY年') }}
                </div>

                <div class="attendance-detail__value">
                    {{ $correctionRequest->attendance->work_date->isoFormat('M月D日') }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    出勤・退勤
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__time-group">
                        <div class="attendance-detail__time-text">
                            {{ $correctionRequest->requested_clock_in?->format('H:i') }}
                        </div>

                        <span class="attendance-detail__separator">〜</span>

                        <div class="attendance-detail__time-text">
                            {{ $correctionRequest->requested_clock_out?->format('H:i') }}
                        </div>
                    </div>
                </div>
            </div>

            @foreach ($correctionRequest->breaks as $index => $break)
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    休憩{{ $index + 1 }}
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__time-group">
                        <div class="attendance-detail__time-text">
                            {{ $break->requested_break_start?->format('H:i') }}
                        </div>

                        <span class="attendance-detail__separator">〜</span>

                        <div class="attendance-detail__time-text">
                            {{ $break->requested_break_end?->format('H:i') }}
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    備考
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__note-text">
                        {{ $correctionRequest->note }}
                    </div>
                </div>
            </div>
        </div>

        <div class="attendance-detail__actions">
            @if ($correctionRequest->status === \App\Models\AttendanceCorrectionRequest::STATUS_APPROVED)
            <button type="button" class="attendance-detail__button" disabled>
                承認済み
            </button>
            @else
            <button type="submit" class="attendance-detail__button">
                承認
            </button>
            @endif
        </div>

    </form>

</div>
@endsection