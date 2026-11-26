<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

class StorePasswordConfirmationIntent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = Date::now()->unix() - (int) $request->session()->get('auth.password_confirmed_at', 0);

        if ($confirmedAt > (int) config('auth.password_timeout', 10800)) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('password.confirm');
        }

        return $next($request);
    }
}
