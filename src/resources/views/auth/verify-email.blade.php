@extends('layouts.app')

@section('title', 'メール認証')

@section('content')
<div class="verify-email">
    <p class="verify-email__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <form method="POST" action="{{ route('verification.notice') }}">
        @csrf
        <a href="http://localhost:8025" target="_blank" class="verify-email__button">
            認証はこちらから
        </a>
    </form>

    <form method="POST" action="{{ route('verification.notice') }}">
        @csrf
        <button type="submit" class="verify-email__resend">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection