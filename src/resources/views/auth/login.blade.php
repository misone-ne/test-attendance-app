@extends('layouts.app')

@section('body-class', 'app-body--plain')

@section('title', 'ログイン')

@section('content')
<div class="auth-form">
    <h2 class="auth-form__title">ログイン</h2>

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        <div class="auth-form__group">
            <label class="auth-form__label">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email') }}" class="auth-form__input">
            @error('email')
            <p class="auth-form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label">パスワード</label>
            <input type="password" name="password" class="auth-form__input">
            @error('password')
            <p class="auth-form__error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-form__button">ログインする</button>

        <a href="{{ route('register') }}" class="auth-form__link">会員登録はこちら</a>
    </form>
</div>
@endsection