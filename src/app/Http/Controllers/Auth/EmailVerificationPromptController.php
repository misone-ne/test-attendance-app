<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * メール認証誘導画面を表示する。
     *
     * @return View メール認証誘導画面
     */
    public function index(): View
    {
        return view('auth.verify-email');
    }
}
