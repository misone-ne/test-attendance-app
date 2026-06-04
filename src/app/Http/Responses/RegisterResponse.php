<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * 登録後の遷移先を指定
     */
    public function toResponse($request)
    {
        return redirect()->route('verification.notice');
    }
}
