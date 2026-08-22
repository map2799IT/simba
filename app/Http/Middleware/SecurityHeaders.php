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

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        $response->headers->set(
            'X-XSS-Protection',
            '1; mode=block'
        );

        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // HSTS only for HTTPS - Vite dev server runs on HTTP
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        /*
         * Content-Security-Policy:
         * - Vite dev server: http://127.0.0.1:5173 + ws://127.0.0.1:5173
         * - Local assets: http://localhost (same machine, different path)
         * - IPv6 [::1] NOT supported in CSP - use 127.0.0.1
         */
        $vite  = "http://127.0.0.1:5173 http://localhost:5173";
        $wsVite = "ws://127.0.0.1:5173 ws://localhost:5173";
        $local = "http://localhost";

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$vite}; "
            . "script-src-elem 'self' 'unsafe-inline' {$vite}; "
            . "style-src 'self' 'unsafe-inline' {$vite} {$local}; "
            . "style-src-elem 'self' 'unsafe-inline' {$vite} {$local}; "
            . "font-src 'self' data: {$vite} {$local}; "
            . "img-src 'self' data: blob: {$local} https:; "
            . "connect-src 'self' {$vite} {$wsVite}; "
            . "manifest-src 'self' {$local}; "
            . "frame-src 'self'; "
            . "worker-src 'self' blob:; "
            . "frame-ancestors 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self';"
        );

        // Sembunyikan X-Powered-By
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
