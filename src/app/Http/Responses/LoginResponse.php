<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * 一般ユーザーのログイン後に勤怠登録画面へ遷移する。
     *
     * @param mixed $request ログイン時のリクエスト
     * @return RedirectResponse 勤怠登録画面へのリダイレクト
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('attendance.index');
    }
}
