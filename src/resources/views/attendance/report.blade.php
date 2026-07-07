@extends('layouts.app')

@section('body-class', 'app-body--plain')

@section('title', 'マイ勤怠レポート')

@section('header-nav')
<nav class="header__nav">
    <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a>
    <a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="{{ route('request.index') }}" class="header__nav-link">申請</a>
    <a href="{{ route('attendance.report') }}" class="header__nav-link">レポート</a>

    <form method="POST" action="{{ route('logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">
            ログアウト
        </button>
    </form>
</nav>
@endsection

@section('content')
<div class="attendance-report">
    <div class="attendance-report__container">

        <h2 class="attendance-report__title">マイ勤怠レポート</h2>
        <p class="attendance-report__text">過去6ヶ月の勤怠データから集計しています。</p>

        <section class="attendance-report__card">
            <h3 class="attendance-report__card-title">基本サマリー</h3>

            <div class="attendance-report__stats">
                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">総労働時間</p>
                    <p class="attendance-report__stats-value">{{ $summary['total_work_time'] }}</p>
                </div>

                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">総残業時間</p>
                    <p class="attendance-report__stats-value">{{ $summary['total_overtime'] }}</p>
                </div>

                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">平均労働時間 / 日</p>
                    <p class="attendance-report__stats-value">{{ $summary['average_work_time'] }}</p>
                </div>
            </div>
        </section>

        <section class="attendance-report__card">
            <h3 class="attendance-report__card-title">月別推移（過去６ヶ月）</h3>

            <table class="attendance-report__table">
                <thead>
                    <tr>
                        <th class="attendance-report__header attendance-report__header--month">月</th>
                        <th class="attendance-report__header attendance-report__header--work">労働時間</th>
                        <th class="attendance-report__header attendance-report__header--overtime">残業時間</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($monthlyReports as $monthlyReport)
                    <tr class="{{ $loop->last ? 'attendance-report__last-row' : '' }}">
                        <td class="attendance-report__cell attendance-report__cell--month">{{ $monthlyReport['month'] }}</td>
                        <td class="attendance-report__cell attendance-report__cell--work">{{ $monthlyReport['work_time'] }}</td>
                        <td class="attendance-report__cell attendance-report__cell--overtime">{{ $monthlyReport['overtime'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="attendance-report__card">
            <h3 class="attendance-report__card-title">今月の異常検知</h3>
            <p class="attendance-report__anomaly-text">基準: 始業 09:00 / 終業 18:00 / 長時間労働は1日10時間超</p>

            <div class="attendance-report__stats">
                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">遅刻回数</p>
                    <p class="attendance-report__stats-value">{{ $anomalies['late_count'] }}回</p>
                </div>

                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">早退回数</p>
                    <p class="attendance-report__stats-value">{{ $anomalies['early_leave_count'] }}回</p>
                </div>

                <div class="attendance-report__stats-item">
                    <p class="attendance-report__stats-label">長時間労働回数</p>
                    <p class="attendance-report__stats-value">{{ $anomalies['long_work_count'] }}回</p>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection