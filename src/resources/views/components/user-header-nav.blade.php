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