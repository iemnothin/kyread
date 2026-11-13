<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan bahasa antarmuka per request:
 * - API (aplikasi Android): header X-Locale.
 * - Web: pilihan pengguna di session, lalu cookie (bertahan setelah logout).
 * Tanpa pilihan, dipakai bahasa default (APP_LOCALE, Indonesia).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $candidates = [
            $request->header('X-Locale'),
            $request->hasSession() ? $request->session()->get(Locales::COOKIE) : null,
            $request->cookie(Locales::COOKIE),
        ];

        $locale = collect($candidates)->first(fn ($c) => Locales::supports($c)) ?? config('app.default_locale');
        Locales::apply($locale);

        return $next($request);
    }
}
