@extends('layouts.app')

@section('body-class', 'app-body--plain')

@section('title', 'メール認証')

@section('content')
<div class="verify-email">
    <p class="verify-email__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <div class="erify-email__button-wrapper">
        <a href="http://localhost:8025" target="_blank" class="verify-email__button">
            認証はこちらから
        </a>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify-email__resend">
            認証メールを再送する
        </button>
    </form>
</div>
@endsection