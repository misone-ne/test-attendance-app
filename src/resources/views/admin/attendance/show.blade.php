@extends('layouts.app')

@section('title', '勤怠詳細')

@section('header-nav')
<nav class="header__nav">
    <a href="{{ route('admin.attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="{{ route('admin.staff.list') }}" class="header__nav-link">スタッフ一覧</a>
    <a href="#" class="header__nav-link">申請一覧</a>

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

    <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" class="attendance-detail__form">
        @csrf
        @method('PUT')

        <div class="attendance-detail__card">
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    名前
                </div>

                <div class="attendance-detail__value">
                    {{ $attendance->user->name }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    日付
                </div>

                <div class="attendance-detail__value">
                    {{ $attendance->work_date->isoFormat('YYYY年') }}
                </div>

                <div class="attendance-detail__value">
                    {{ $attendance->work_date->isoFormat('M月D日') }}
                </div>
            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    出勤・退勤
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__time-group">
                        <input type="time" name="clock_in" value="{{ old('clock_in', $attendance->clock_in?->format('H:i')) }}" class="attendance-detail__time-input">
                        <span class="attendance-detail__separator">〜</span>
                        <input type="time" name="clock_out" value="{{ old('clock_out', $attendance->clock_out?->format('H:i')) }}" class="attendance-detail__time-input">
                    </div>

                    @error('clock_in')
                    <p class="form-error">{{ $message }}</p>
                    @enderror

                    @error('clock_out')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @foreach ($attendance->breakTimes as $index => $breakTime)
            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    休憩{{ $index + 1 }}
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__time-group">
                        <input type="time" name="breaks[{{ $index }}][break_start]" value="{{ old('breaks.' . $index . '.break_start', $breakTime->break_start?->format('H:i')) }}" class="attendance-detail__time-input">
                        <span class="attendance-detail__separator">〜</span>
                        <input type="time" name="breaks[{{ $index }}][break_end]" value="{{ old('breaks.' . $index . '.break_end', $breakTime->break_end?->format('H:i')) }}" class="attendance-detail__time-input">
                    </div>
                    @error("breaks.$index.break_start")
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                    @error("breaks.$index.break_end")
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endforeach

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    休憩{{ $attendance->breakTimes->count() + 1 }}
                </div>

                <div class="attendance-detail__input-area">
                    <div class="attendance-detail__time-group">
                        <input type="time" name="breaks[{{ $attendance->breakTimes->count() }}][break_start]" value="{{ old('breaks.' . $attendance->breakTimes->count() . '.break_start') }}" class="attendance-detail__time-input">
                        <span class="attendance-detail__separator">〜</span>
                        <input type="time" name="breaks[{{ $attendance->breakTimes->count() }}][break_end]" value="{{ old('breaks.' . $attendance->breakTimes->count() . '.break_end') }}" class="attendance-detail__time-input">
                    </div>
                    @error("breaks." . $attendance->breakTimes->count() . ".break_start")
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                    @error("breaks." . $attendance->breakTimes->count() . ".break_end")
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="attendance-detail__row">
                <div class="attendance-detail__label">
                    備考
                </div>

                <div class="attendance-detail__input-area">
                    <textarea name="note" class="attendance-detail__textarea">{{ old('note', $attendance->note) }}</textarea>
                    @error('note')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="attendance-detail__actions">
            @if ($hasPendingRequest)
            <p class="form-error">*承認待ちのため修正はできません。</p>
            @else
            <button type="submit" class="attendance-detail__button">
                修正
            </button>
            @endif
        </div>

    </form>

</div>
@endsection