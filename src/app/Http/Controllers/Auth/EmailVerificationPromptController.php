<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function index(Request $request): View
    {
        // 未認証ユーザーアクセス時、ログイン中に1回のみメール送信
        if (!$request->user()->hasVerifiedEmail() && !$request->session()->has('verification_email_sent')) {
            $request->user()->sendEmailVerificationNotification();

            $request->session()->put('verification_email_sent', true);
        }

        return view('auth.verify-email');
    }
}
