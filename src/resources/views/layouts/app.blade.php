<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite(['resources/scss/app.scss'])
</head>

<body class="app-body">
    <header class="header">
        <div class="header__inner">
            <h1 class="header__logo-title">
                <a href="{{ route('login') }}" class="header__logo-link">
                    <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH" class="header__logo">
                </a>
            </h1>
            @yield('header-nav')
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>