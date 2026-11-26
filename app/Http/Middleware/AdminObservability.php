<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AdminObservability
{
    /**
     * Handle an incoming request and emit a bounded performance signal.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        if ($request->is('admin/*') || $request->is('admin')) {
            Log::info('admin.request.completed', [
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'slow' => $durationMs >= 500,
            ]);
        }

        return $response;
    }
}
