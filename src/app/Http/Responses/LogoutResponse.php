<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * 一般ユーザーのログアウト後にログイン画面へ遷移する。
     *
     * @param mixed $request ログアウト時のリクエスト
     * @return RedirectResponse ログイン画面へのリダイレクト
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
