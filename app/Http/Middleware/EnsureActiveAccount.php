<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if ($request->user()?->isActive()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tài khoản hiện không thể sử dụng.'], 403);
        }

        return redirect()->route('login')->withErrors(['email' => 'Tài khoản hiện không thể sử dụng.']);
    }
}
