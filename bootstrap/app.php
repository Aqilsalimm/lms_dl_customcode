<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trusted proxies are configured in config/trustedproxy.php (driven by
        // the TRUSTED_PROXIES env var). Previously this was `at: '*'`, which
        // trusts the X-Forwarded-For header from ANY client — that lets a
        // caller spoof their IP and therefore bypass every IP-based throttle
        // (login, OTP, LoginRequest::throttleKey). Leave TRUSTED_PROXIES unset
        // when the app is not behind a proxy.

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return null;
            }
            return '/?auth_timeout=1';
        });

        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        $middleware->append(\App\Http\Middleware\SlowQueryLogger::class);

        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\LicenseMiddleware::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Only the Midtrans server-to-server webhook may skip CSRF. It is
        // authenticated by signature verification inside the controller.
        // The previous 'payment/*' wildcard also exempted payment/checkout,
        // payment/cancel and payment/mock-complete, which are browser-driven
        // and must keep CSRF protection.
        $middleware->validateCsrfTokens(except: [
            'payment/notification',
        ]);

        $middleware->alias([
            'active.subscription' => \App\Http\Middleware\EnsureActiveSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $response instanceof \Illuminate\Http\JsonResponse) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return $response;
                }
                
                if (!config('app.debug') || app()->environment('production')) {
                    $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                    
                    // Use a generic message for internal exceptions, but preserve message for HTTP exceptions (e.g. 404, 403)
                    $message = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface 
                        ? $e->getMessage() 
                        : 'Server Error';

                    return response()->json([
                        'message' => $message,
                    ], $statusCode);
                }
            }
            return $response;
        });
    })->create();
