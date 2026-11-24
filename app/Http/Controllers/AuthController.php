<?php

namespace App\Http\Controllers;

use App\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        unset($credentials['remember']);

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        $user = $request->user();

        if (! $user || $user->status !== UserStatus::Active || $user->locked_at !== null) {
            Auth::logout();

            return back()->withErrors(['email' => 'Tài khoản hiện không thể đăng nhập.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->roles()->where('name', 'admin')->exists()) {
            return redirect()->route('workspace', ['role' => 'admin']);
        }

        if ($user->roles()->where('name', 'teacher')->exists()) {
            return redirect()->route('workspace', ['role' => 'teacher']);
        }

        return redirect()->route('student.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
