@extends('layouts.app')

@section('body-class', 'app-body--plain')

@section('title', '会員登録')

@section('content')
<div class="auth-form">
    <h2 class="auth-form__title">会員登録</h2>

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        <div class="auth-form__group">
            <label class="auth-form__label">名前</label>
            <input type="text" name="name" value="{{ old('name') }}" class="auth-form__input">
            @error('name')
            <p class="auth-form__error">{{ $message }}</p>
            @enderror
        </div>

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

        <div class="auth-form__group">
            <label class="auth-form__label">パスワード確認</label>
            <input type="password" name="password_confirmation" class="auth-form__input">
            @error('password_confirmation')
            <p class="auth-form__error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-form__button">登録する</button>

        <a href="{{ route('login') }}" class="auth-form__link">ログインはこちら</a>
    </form>
</div>
@endsection