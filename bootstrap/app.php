<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\RestrictKidsAccess::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\RestrictKidsAccess::class,
        ]);

        // CSRF Protection Configuration
        // Note: CSRF is disabled for API routes because we use Sanctum's token-based
        // authentication for the SPA. Sanctum handles API authentication via cookies
        // (EnsureFrontendRequestsAreStateful) which includes CSRF protection through
        // the session cookie. The sanctum/csrf-cookie endpoint must be called first
        // to get the CSRF token which axios includes automatically.
        //
        // SECURITY CONSIDERATION: If moving to a purely stateless API with Bearer tokens,
        // this configuration is correct. However, the current setup uses cookie-based
        // auth (withCredentials: true), so ensure the frontend calls /sanctum/csrf-cookie
        // before making mutating requests.
        //
        // For stricter security with cookie auth, consider:
        // $middleware->validateCsrfTokens(except: ['sanctum/csrf-cookie']);
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
