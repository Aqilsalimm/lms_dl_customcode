<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
    }

    public function test_preserves_no_store_when_already_set_by_previous_middleware(): void
    {
        $request = Request::create('/', 'GET');
        
        $response = new Response('<html>Welcome</html>', 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
        ]);

        $handledResponse = $this->middleware->handle($request, fn () => $response);

        $this->assertStringContainsString('no-store', (string) $handledResponse->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public, max-age=60', (string) $handledResponse->headers->get('Cache-Control'));
        $this->assertEquals('DENY', $handledResponse->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $handledResponse->headers->get('X-Content-Type-Options'));
    }

    public function test_enforces_no_store_for_inertia_ajax_requests(): void
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $response = new Response('{"component":"Welcome"}', 200);

        $handledResponse = $this->middleware->handle($request, fn () => $response);

        $this->assertStringContainsString('no-store', (string) $handledResponse->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public, max-age=60', (string) $handledResponse->headers->get('Cache-Control'));
    }

    public function test_enforces_no_store_for_auth_and_dashboard_routes(): void
    {
        $routes = ['/dashboard', '/login', '/register', '/cart', '/checkout'];

        foreach ($routes as $uri) {
            $request = Request::create($uri, 'GET');
            $response = new Response('content', 200);

            $handledResponse = $this->middleware->handle($request, fn () => $response);

            $this->assertStringContainsString(
                'no-store',
                (string) $handledResponse->headers->get('Cache-Control'),
                "Failed asserting no-store for route {$uri}"
            );
        }
    }

    public function test_applies_public_cache_only_for_purely_public_non_inertia_responses(): void
    {
        $request = Request::create('/public-api-endpoint', 'GET');
        $response = new Response('{"status":"ok"}', 200);

        $handledResponse = $this->middleware->handle($request, fn () => $response);

        $cacheControl = (string) $handledResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=60', $cacheControl);
    }
}
