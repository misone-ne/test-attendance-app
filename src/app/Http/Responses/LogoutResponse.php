<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    // ログアウト後の遷移先を指定
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
