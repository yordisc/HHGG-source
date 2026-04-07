<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $scriptSrc = "'self' 'unsafe-inline'";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            $devHosts = implode(' ', [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'http://0.0.0.0:5173',
                'https://*.app.github.dev',
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
                'ws://0.0.0.0:5173',
                'wss://*.app.github.dev',
            ]);

            $scriptSrc .= ' '.$devHosts;
            $styleSrc .= ' '.$devHosts;
            $connectSrc .= ' '.$devHosts;
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data: https:; style-src {$styleSrc}; font-src 'self' https://fonts.gstatic.com data:; script-src {$scriptSrc}; connect-src {$connectSrc}; frame-ancestors 'self';"
        );

        return $response;
    }
}
