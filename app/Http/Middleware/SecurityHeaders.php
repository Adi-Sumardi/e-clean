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

        // Restrict feature permissions
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');

        // Enable HSTS for HTTPS connections
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' data:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
