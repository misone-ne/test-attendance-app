@extends('layouts.app')

@section('title', 'スタッフ一覧')

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
        スタッフ一覧
    </h2>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th class="attendance-list__header">名前</th>
                <th class="attendance-list__header">メールアドレス</th>
                <th class="attendance-list__header">月次勤怠</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($users as $user)
            <tr>
                <td class="attendance-list__cell">{{ $user->name }}</td>
                <td class="attendance-list__cell">{{ $user->email }}</td>
                <td class="attendance-list__cell">
                    <a href="{{ route('admin.staff.attendance.list', ['id' => $user->id]) }}" class="attendance-list__detail-link">
                        詳細
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection