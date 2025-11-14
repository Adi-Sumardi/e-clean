<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log incoming request
        Log::channel('daily')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
        ]);

        $response = $next($request);

        // Calculate response time
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // in milliseconds

        // Log response
        Log::channel('daily')->info('API Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        // Log slow requests (> 1 second)
        if ($duration > 1000) {
            Log::channel('daily')->warning('Slow API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => $duration,
            ]);
        }

        return $response;
    }
}
