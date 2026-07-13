<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログイン画面を表示する。
     *
     * @return View 管理者ログイン画面
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * 管理者のログイン認証を行い、認証成功後に管理者用勤怠一覧画面へ遷移する。
     *
     * @param AdminLoginRequest $request 管理者のログイン情報を含むリクエスト
     * @return RedirectResponse 認証結果に応じた画面へのリダイレクト
     */
    public function store(AdminLoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('admin')->attempt(
            $request->only('email', 'password')
        )) {

            return back()
                ->withErrors([
                    'email' => 'ログイン情報が登録されていません',
                ])
                ->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('admin.attendance.list');
    }

    /**
     * 管理者をログアウトし、セッションを無効化する。
     *
     * @param Request $request 現在のセッション情報を含むリクエスト
     * @return RedirectResponse 管理者ログイン画面へのリダイレクト
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
