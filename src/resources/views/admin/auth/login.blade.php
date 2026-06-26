@extends('layouts.app')

@section('body-class', 'app-body--plain')

@section('title', '管理者ログイン')

@section('content')
<div class="auth-form">
    <h2 class="auth-form__title">管理者ログイン</h2>

    <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
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

        <button type="submit" class="auth-form__button">
            管理者ログインする
        </button>
    </form>
</div>
@endsection