@extends('layouts.app')

@section('title', '申請一覧')

@section('header-nav')
<nav class="header__nav">
    <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a>
    <a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a>
    <a href="{{ route('request.index') }}" class="header__nav-link">申請</a>

    <form method="POST" action="{{ route('logout') }}" class="header__logout-form">
        @csrf
        <button type="submit" class="header__logout-button">
            ログアウト
        </button>
    </form>
</nav>
@endsection


@section('content')
<div class="request-list">

    <h2 class="page-title">申請一覧</h2>

    <div class="request-list__tabs">
        <a href="{{ route('request.index', ['status' => 'pending']) }}"
            class="request-list__tab {{ $status === 'pending' ? 'request-list__tab--active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('request.index', ['status' => 'approved']) }}"
            class="request-list__tab {{ $status === 'approved' ? 'request-list__tab--active' : '' }}">
            承認済み
        </a>
    </div>

    <table class="request-list__table">
        <thead>
            <tr class="request-list__header-row">
                <th class="request-list__header">状態</th>
                <th class="request-list__header">名前</th>
                <th class="request-list__header">対象日時</th>
                <th class="request-list__header">申請理由</th>
                <th class="request-list__header">申請日時</th>
                <th class="request-list__header">詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($requests as $request)
            <tr class="request-list__row">
                <td class="request-list__cell">
                    @if ($request->status === \App\Models\AttendanceCorrectionRequest::STATUS_PENDING)
                    承認待ち
                    @else
                    承認済み
                    @endif
                </td>

                <td class="request-list__cell">
                    {{ $request->user->name }}
                </td>

                <td class="request-list__cell">
                    {{ $request->attendance->work_date->format('Y/m/d') }}
                </td>

                <td class="request-list__cell">
                    {{ $request->note }}
                </td>

                <td class="request-list__cell">
                    {{ $request->created_at->format('Y/m/d') }}
                </td>

                <td class="request-list__cell">
                    <a href="{{ route('attendance.show', ['id' => $request->attendance_id]) }}" class="request-list__detail-link">
                        詳細
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection