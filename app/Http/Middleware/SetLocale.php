<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set locale using session first, then browser Accept-Language header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en']);
        $fallback = config('app.fallback_locale', 'en');

        $locale = session('locale');

        if (!is_string($locale) || !in_array($locale, $supported, true)) {
            $preferred = $request->getPreferredLanguage($supported);
            $locale = is_string($preferred) ? $preferred : $fallback;
            session(['locale' => $locale]);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
