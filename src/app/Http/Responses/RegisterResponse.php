<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * 会員登録後にメール認証誘導画面へ遷移する。
     *
     * @param Request $request 会員登録時のリクエスト
     * @return RedirectResponse メール認証誘導画面へのリダイレクト
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('verification.notice');
    }
}
