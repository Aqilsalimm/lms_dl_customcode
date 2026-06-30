<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Programmatically remove X-Powered-By from global PHP headers
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
            header_remove('x-powered-by');
        }

        // Block command-line tools and scrapers to prevent direct page scraping/crawling
        if (!app()->environment('testing') || $request->headers->has('X-Test-Force-UA-Block')) {
            $userAgent = $request->header('User-Agent');
            if (empty($userAgent) || preg_match('/(curl|wget|python|guzzle|httpclient|postman)/i', $userAgent)) {
                $response = response('Not Found', 404);
                return $this->applyHeaders($response, $request);
            }
        }

        $response = $next($request);

        return $this->applyHeaders($response, $request);
    }

    /**
     * Apply security headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function applyHeaders(Response $response, Request $request): Response
    {
        // Remove X-Powered-By from Laravel/Symfony response headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('x-powered-by');

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        // Only apply HSTS if secure (HTTPS) request
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Prevent browser caching for auth, login, and dashboard pages to avoid stale state and blank pages
        if ($request->is('dashboard*') || $request->is('login*') || $request->is('register*') || $request->is('forgot-password*') || $request->is('reset-password*') || $request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        // Stricter but functional Content Security Policy (CSP)
        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://app.sandbox.midtrans.com https://app.midtrans.com; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
             . "img-src 'self' data: https: blob:; "
             . "font-src 'self' data: https://fonts.gstatic.com; "
             . "frame-src 'self' https://app.sandbox.midtrans.com https://app.midtrans.com; "
             . "connect-src 'self' https://app.sandbox.midtrans.com https://app.midtrans.com https://api.sandbox.midtrans.com https://api.midtrans.com;";
        
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
