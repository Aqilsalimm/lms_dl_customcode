<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\User;
use App\Models\Course;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since MidtransConfig uses Setting::getValue, we need to ensure the Setting model works.
        // We'll mock the configuration values directly instead of using Setting model to simplify tests.
        // Also disable LicenseMiddleware for payment tests
        $this->withoutMiddleware([\App\Http\Middleware\LicenseMiddleware::class]);
    }

    public function test_webhook_returns_503_in_production_when_not_configured()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
        ]);

        Config::set('midtrans.server_key', 'placeholder');
        app()->detectEnvironment(fn() => 'production');
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $payload = [
            'order_id' => "DRSTH-{$order->id}-" . time(),
            'status_code' => '200',
            'gross_amount' => '100.00',
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson('/payment/notification', $payload);

        $response->assertStatus(503);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_webhook_aborts_with_invalid_signature()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
        ]);

        Config::set('midtrans.server_key', 'valid-server-key');

        $payload = [
            'order_id' => "DRSTH-{$order->id}-" . time(),
            'status_code' => '200',
            'gross_amount' => '100.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature'
        ];

        $response = $this->postJson('/payment/notification', $payload);

        $response->assertStatus(403);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_webhook_processes_valid_signature()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
        ]);

        Config::set('midtrans.server_key', 'valid-server-key');

        $orderIdField = "DRSTH-{$order->id}-" . time();
        $statusCode = '200';
        $grossAmount = '100.00';
        $serverKey = 'valid-server-key';

        $signatureKey = hash('sha512', $orderIdField . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderIdField,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
            'signature_key' => $signatureKey
        ];

        $response = $this->postJson('/payment/notification', $payload);

        $response->assertStatus(200);
        $this->assertEquals('completed', $order->fresh()->status);
    }
}
