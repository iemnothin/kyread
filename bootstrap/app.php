<?php

use App\Http\Middleware\EnsureUserHasStore;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller' => EnsureUserHasStore::class,
        ]);
        $middleware->redirectUsersTo(fn () => route('home'));
        // Bahasa antarmuka (session/cookie di web, header X-Locale di API).
        $middleware->web(append: [SetLocale::class]);
        $middleware->api(append: [SetLocale::class]);
        $middleware->encryptCookies(except: [Locales::COOKIE]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
