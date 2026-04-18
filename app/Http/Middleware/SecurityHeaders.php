<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Content Security Policy
        // jquery + sweetalert2 được load từ CDN bên ngoài nên cần whitelist
        $isDev = config('app.env') !== 'production';
        $viteHosts = $isDev ? 'http://localhost:5173 http://127.0.0.1:5173' : '';
        $viteWs    = $isDev ? 'ws://localhost:5173 ws://127.0.0.1:5173' : '';

        $csp = implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net" . ($viteHosts ? " $viteHosts" : ''),
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net" . ($viteHosts ? " $viteHosts" : ''),
            "font-src 'self' https://fonts.bunny.net" . ($viteHosts ? " $viteHosts" : ''),
            "img-src 'self' data: https:",
            "connect-src 'self'" . ($viteHosts ? " $viteHosts" : '') . ($viteWs ? " $viteWs" : ''),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]));
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->secure() && config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
