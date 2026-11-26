<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $contentSecurityPolicy = "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'";
        if (app()->environment(['local', 'testing'])) {
            $contentSecurityPolicy .= '; script-src-elem \'self\' \'unsafe-inline\' http://*:*; style-src-elem \'self\' \'unsafe-inline\' http://*:*; connect-src \'self\' http://*:* ws://*:*';
        }
        $response->headers->set('Content-Security-Policy', $contentSecurityPolicy);
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->user() && ($request->is('admin') || $request->is('admin/*') || $request->is('account/*'))) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        return $response;
    }
}
