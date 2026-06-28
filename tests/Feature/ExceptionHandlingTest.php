<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class ExceptionHandlingTest extends TestCase
{
    public function test_json_requests_to_non_existent_endpoints_returns_sanitized_404_response()
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/non-existent-endpoint-route-test');

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
        
        $data = $response->json();
        $this->assertArrayNotHasKey('exception', $data);
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('line', $data);
        $this->assertArrayNotHasKey('trace', $data);
    }

    public function test_json_requests_handling_internal_exceptions_returns_sanitized_500_response()
    {
        Route::middleware('api')->get('/test-route-throwing-exception', function () {
            throw new \RuntimeException('Secret server exception details!');
        });

        config(['app.debug' => false]);

        $response = $this->getJson('/test-route-throwing-exception');

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Server Error']);
        
        $data = $response->json();
        $this->assertArrayNotHasKey('exception', $data);
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('line', $data);
        $this->assertArrayNotHasKey('trace', $data);
    }
}
