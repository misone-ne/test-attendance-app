<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    // ログイン後の遷移先を指定
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('attendance.index');
    }
}
