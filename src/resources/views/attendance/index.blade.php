@extends('layouts.app')

@section('header-nav')
<nav class="header__nav">
    @if ($status === 'finished')
    <a href="{{ route('attendance.list') }}" class="header__nav-link">今月勤怠一覧</a>
    <a href="{{ route('request.index') }}" class="header__nav-link">申請一覧</a>
    @else
    <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a>
    <a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="{{ route('request.index') }}" class="header__nav-link">申請</a>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">ログアウト</button>
    </form>
</nav>
@endsection

@section('content')
<div class="attendance">
    <p class="attendance__status">
        @if ($status === 'off')
        勤務外
        @elseif ($status === 'working')
        出勤中
        @elseif ($status === 'break')
        休憩中
        @elseif ($status === 'finished')
        退勤済
        @endif
    </p>

    <p class="attendance__date">
        {{ $now->isoFormat('YYYY年M月D日(ddd)') }}
    </p>

    <p class="attendance__time">
        {{ $now->format('H:i') }}
    </p>

    @if ($status === 'off')
    <div class="attendance__actions">
        <form method="POST" action="{{ route('attendance.clock-in') }}">
            @csrf
            <button type="submit" class="attendance__button">
                出勤
            </button>
        </form>
    </div>
    @elseif ($status === 'working')
    <div class="attendance__actions">
        <form method="POST" action="{{ route('attendance.clock-out') }}">
            @csrf
            <button type="submit" class="attendance__button">
                退勤
            </button>
        </form>
        <form method="POST" action="{{ route('attendance.break-start') }}">
            @csrf
            <button type="submit" class="attendance__button attendance__button--secondary">
                休憩入
            </button>
        </form>
    </div>
    @elseif ($status === 'break')
    <div class="attendance__actions">
        <form method="POST" action="{{ route('attendance.break-end') }}">
            @csrf
            <button type="submit" class="attendance__button">
                休憩戻
            </button>
        </form>
    </div>
    @elseif ($status === 'finished')
    <p class="attendance__message">
        お疲れ様でした。
    </p>
    @endif
</div>
@endsection