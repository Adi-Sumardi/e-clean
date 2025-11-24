<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitKey = 'api'): Response
    {
        // Define rate limits
        $this->configureRateLimits();

        // Execute rate limiting
        $key = $this->resolveRequestSignature($request, $limitKey);

        if (RateLimiter::tooManyAttempts($key, $this->getMaxAttempts($limitKey))) {
            return $this->buildTooManyAttemptsResponse($key, $limitKey);
        }

        RateLimiter::hit($key, $this->getDecaySeconds($limitKey));

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key, $limitKey);
    }

    /**
     * Configure rate limits
     */
    protected function configureRateLimits(): void
    {
        // API Rate Limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many requests',
                        'message' => 'Rate limit exceeded. Please try again later.',
                    ], 429);
                });
        });

        // WhatsApp API Rate Limit: 10 requests per minute
        RateLimiter::for('whatsapp', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many WhatsApp requests',
                        'message' => 'WhatsApp rate limit exceeded. Please wait before sending more messages.',
                    ], 429);
                });
        });

        // File Upload Rate Limit: 20 uploads per hour
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many upload requests',
                        'message' => 'Upload rate limit exceeded. Please try again later.',
                    ], 429);
                });
        });

        // Login Rate Limit: 5 attempts per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->input('email') . $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many login attempts',
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        // Export Rate Limit: 5 exports per hour
        RateLimiter::for('export', function (Request $request) {
            return Limit::perHour(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many export requests',
                        'message' => 'Export rate limit exceeded. Please try again later.',
                    ], 429);
                });
        });
    }

    /**
     * Resolve request signature
     */
    protected function resolveRequestSignature(Request $request, string $limitKey): string
    {
        if ($request->user()) {
            return sha1($limitKey . '|' . $request->user()->id);
        }

        return sha1($limitKey . '|' . $request->ip());
    }

    /**
     * Get max attempts untuk rate limiter
     */
    protected function getMaxAttempts(string $limitKey): int
    {
        return match ($limitKey) {
            'api' => 60,
            'whatsapp' => 10,
            'uploads' => 20,
            'login' => 5,
            'export' => 5,
            default => 60,
        };
    }

    /**
     * Get decay seconds (time window)
     */
    protected function getDecaySeconds(string $limitKey): int
    {
        return match ($limitKey) {
            'api' => 60,          // 1 minute
            'whatsapp' => 60,     // 1 minute
            'uploads' => 3600,    // 1 hour
            'login' => 60,        // 1 minute
            'export' => 3600,     // 1 hour
            default => 60,
        };
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildTooManyAttemptsResponse(string $key, string $limitKey): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again in ' . $retryAfter . ' seconds.',
            'retry_after' => $retryAfter,
            'limit' => $this->getMaxAttempts($limitKey),
            'limit_type' => $limitKey,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $this->getMaxAttempts($limitKey),
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, string $key, string $limitKey): Response
    {
        $maxAttempts = $this->getMaxAttempts($limitKey);
        $remaining = max(0, $maxAttempts - RateLimiter::attempts($key));
        $retryAfter = RateLimiter::availableIn($key);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }

    /**
     * Clear rate limiter for specific key (useful for testing)
     */
    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }
}
