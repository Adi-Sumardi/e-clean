<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     * Add security headers to all responses
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // Enable XSS protection (for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict feature permissions - Allow camera and geolocation for this app
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // Enable HSTS for HTTPS connections
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy (More secure - removed unsafe-eval)
        // Note: Filament requires some unsafe-inline for styles, but we removed unsafe-eval
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline'; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' data: https://fonts.gstatic.com; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
