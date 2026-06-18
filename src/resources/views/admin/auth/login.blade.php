@extends('layouts.app')

@section('content')
<div class="admin-login">
    <h2>管理者ログイン</h2>

    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf

        <label>メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}">
        @error('email')
        <p class="form-error">{{ $message }}</p>
        @enderror

        <label>パスワード</label>
        <input type="password" name="password">
        @error('password')
        <p class="form-error">{{ $message }}</p>
        @enderror

        <button type="submit">管理者ログインする</button>
    </form>
</div>
@endsection