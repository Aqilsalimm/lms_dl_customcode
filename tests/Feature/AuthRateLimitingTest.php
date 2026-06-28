<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class AuthRateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_auth_routes_have_correct_rate_limiting_middleware()
    {
        $routes = [
            'login'            => ['POST', '/login', 'throttle:3,1'],
            'register'         => ['POST', '/register', 'throttle:5,1'],
            'password.email'   => ['POST', '/forgot-password', 'throttle:3,1'],
            'password.store'   => ['POST', '/reset-password', 'throttle:5,1'],
            'login.otp.verify' => ['POST', '/login/otp', 'throttle:5,1'],
            'login.otp.resend' => ['POST', '/login/otp/resend', 'throttle:3,1'],
            'otp.send'         => ['POST', '/otp/send', 'throttle:3,1'],
            'otp.verify'       => ['POST', '/otp/verify', 'throttle:5,1'],
        ];

        foreach ($routes as $routeName => $config) {
            [$method, $uri, $expectedMiddleware] = $config;
            
            $request = Request::create($uri, $method);
            try {
                $route = Route::getRoutes()->match($request);
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $route = null;
            }
            
            $this->assertNotNull($route, "Route [{$method} {$uri}] is not defined.");
            
            $middlewares = $route->gatherMiddleware();
            
            $this->assertTrue(
                in_array($expectedMiddleware, $middlewares),
                "Route [{$method} {$uri}] does not have expected middleware [{$expectedMiddleware}]. Found: " . implode(', ', $middlewares)
            );
        }
    }
}
