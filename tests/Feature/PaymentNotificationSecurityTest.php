<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentNotificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock LicenseService validation globally to allow access to premium routes
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    /**
     * Test callback with a valid signature key is processed successfully
     */
    public function test_midtrans_callback_with_valid_signature_processes_successfully()
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Test Midtrans Course',
            'instructor_id' => 1,
            'course_type' => 'regular',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $serverKey = 'SB-Mid-server-myrealtestkey';
        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => $serverKey]);

        $orderIdField = 'DRSTH-' . $order->id . '-123456';
        $statusCode = '200';
        $grossAmount = '150000.00';
        $signatureKey = hash('sha512', $orderIdField . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderIdField,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
            'signature_key' => $signatureKey,
        ];

        $response = $this->postJson(route('payment.notification'), $payload);

        $response->assertStatus(200);
        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    /**
     * Test callback with an invalid signature key is rejected with a 403 Forbidden
     */
    public function test_midtrans_callback_with_invalid_signature_is_rejected()
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Test Midtrans Course 2',
            'instructor_id' => 1,
            'course_type' => 'regular',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $serverKey = 'SB-Mid-server-myrealtestkey';
        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => $serverKey]);

        $orderIdField = 'DRSTH-' . $order->id . '-123456';
        $payload = [
            'order_id' => $orderIdField,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
            'signature_key' => 'invalid_signature_hash_123',
        ];

        $response = $this->postJson(route('payment.notification'), $payload);

        $response->assertStatus(403);
        $this->assertEquals('pending', $order->fresh()->status);
    }

    /**
     * Test callback with placeholder server key bypasses signature verification
     */
    public function test_midtrans_callback_with_placeholder_server_key_bypasses_signature()
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Test Midtrans Course 3',
            'instructor_id' => 1,
            'course_type' => 'regular',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 150000,
            'status' => 'pending',
        ]);

        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'SB-Mid-server-placeholder']);

        $orderIdField = 'DRSTH-' . $order->id . '-123456';
        $payload = [
            'order_id' => $orderIdField,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
            'signature_key' => 'bad_signature_key',
        ];

        $response = $this->postJson(route('payment.notification'), $payload);

        $response->assertStatus(200);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /**
     * Test settings keys are masked in fetch and protected against overwrite in updateSettings
     */
    public function test_settings_api_keys_are_masked_and_protected_in_lms_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'SB-Mid-server-highly-confidential-key-1234']);

        // Fetch
        $response = $this->actingAs($admin)->get(route('dashboard.settings'));
        $response->assertStatus(200);
        
        $settings = $response->original->getData()['page']['props']['settings'];
        $this->assertNotEquals('SB-Mid-server-highly-confidential-key-1234', $settings['midtrans_server_key']);
        $this->assertStringContainsString('SB-M', $settings['midtrans_server_key']);
        $this->assertStringContainsString('1234', $settings['midtrans_server_key']);
        $this->assertStringContainsString('****', $settings['midtrans_server_key']);

        // Update with masked value (should keep original unmasked value in database)
        $updatePayload = [
            'settings' => [
                'midtrans_server_key' => $settings['midtrans_server_key'],
                'site_name' => 'Drastha Learning Updated',
            ]
        ];

        $response = $this->actingAs($admin)->post(route('dashboard.settings.update'), $updatePayload);
        $response->assertRedirect();
        
        $this->assertEquals(
            'SB-Mid-server-highly-confidential-key-1234',
            Setting::where('key', 'midtrans_server_key')->value('value')
        );

        // Update with new unmasked value (should change key in database)
        $updatePayload['settings']['midtrans_server_key'] = 'SB-Mid-server-completely-new-key-5678';
        $response = $this->actingAs($admin)->post(route('dashboard.settings.update'), $updatePayload);
        $response->assertRedirect();

        $this->assertEquals(
            'SB-Mid-server-completely-new-key-5678',
            Setting::where('key', 'midtrans_server_key')->value('value')
        );
    }

    /**
     * Test ecommerce settings keys are masked in fetch and protected against overwrite in updateSettings
     */
    public function test_settings_api_keys_are_masked_and_protected_in_ecommerce_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'SB-Mid-server-confidential-ecom-9999']);
        Setting::updateOrCreate(['key' => 'midtrans_client_key'], ['value' => 'SB-Mid-client-confidential-ecom-8888']);

        // Fetch
        $response = $this->actingAs($admin)->get(route('dashboard.ecommerce.settings'));
        $response->assertStatus(200);

        $settings = $response->original->getData()['page']['props']['settings'];
        $this->assertNotEquals('SB-Mid-server-confidential-ecom-9999', $settings['midtrans_server_key']);
        $this->assertStringContainsString('SB-M', $settings['midtrans_server_key']);
        $this->assertStringContainsString('9999', $settings['midtrans_server_key']);
        
        $this->assertNotEquals('SB-Mid-client-confidential-ecom-8888', $settings['midtrans_client_key']);
        $this->assertStringContainsString('SB-M', $settings['midtrans_client_key']);
        $this->assertStringContainsString('8888', $settings['midtrans_client_key']);

        // Update with masked values
        $updatePayload = [
            'midtrans_server_key' => $settings['midtrans_server_key'],
            'midtrans_client_key' => $settings['midtrans_client_key'],
            'midtrans_sandbox_mode' => true,
            'auto_complete_ecommerce_orders' => false,
            'abandoned_cart_reminder_enabled' => false,
            'abandoned_cart_reminder_delay' => 60,
            'abandoned_cart_email_subject' => 'Subject',
            'abandoned_cart_email_body' => 'Body',
        ];

        $response = $this->actingAs($admin)->post(route('dashboard.ecommerce.settings.update'), $updatePayload);
        $response->assertRedirect();

        $this->assertEquals(
            'SB-Mid-server-confidential-ecom-9999',
            Setting::where('key', 'midtrans_server_key')->value('value')
        );
        $this->assertEquals(
            'SB-Mid-client-confidential-ecom-8888',
            Setting::where('key', 'midtrans_client_key')->value('value')
        );

        // Update with new values
        $updatePayload['midtrans_server_key'] = 'SB-Mid-server-newecom-7777';
        $updatePayload['midtrans_client_key'] = 'SB-Mid-client-newecom-6666';

        $response = $this->actingAs($admin)->post(route('dashboard.ecommerce.settings.update'), $updatePayload);
        $response->assertRedirect();

        $this->assertEquals(
            'SB-Mid-server-newecom-7777',
            Setting::where('key', 'midtrans_server_key')->value('value')
        );
        $this->assertEquals(
            'SB-Mid-client-newecom-6666',
            Setting::where('key', 'midtrans_client_key')->value('value')
        );
    }

    /**
     * Test sensitive keys are completely removed from Inertia global shared props and gemini_api_key is removed
     */
    public function test_sensitive_keys_and_gemini_key_are_not_shared_globally_via_inertia()
    {
        // Set sensitive settings keys
        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'SB-Mid-server-secret123']);
        Setting::updateOrCreate(['key' => 'xendit_secret_key'], ['value' => 'xendit_secret_456']);
        Setting::updateOrCreate(['key' => 'license_key'], ['value' => 'DRSTHA-MASTER-789']);
        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'Drastha Learning Platform']);

        // Set Gemini config key
        config(['services.gemini.key' => 'gemini-secret-api-key']);

        // Fetch home page (or any public page)
        $response = $this->get('/');
        $response->assertStatus(200);

        // Get shared Inertia props
        $page = $response->original->getData()['page'];
        $props = $page['props'];

        // Assert gemini_api_key is NOT in props
        $this->assertArrayNotHasKey('gemini_api_key', $props);

        // Assert sensitive settings keys are NOT in settings prop
        $settings = $props['settings'];
        $this->assertArrayNotHasKey('midtrans_server_key', $settings);
        $this->assertArrayNotHasKey('xendit_secret_key', $settings);
        $this->assertArrayNotHasKey('license_key', $settings);

        // Assert public settings keys ARE in settings prop
        $this->assertArrayHasKey('site_name', $settings);
        $this->assertEquals('Drastha Learning Platform', $settings['site_name']);
    }

    /**
     * Test Gemini chat proxy route works as expected
     */
    public function test_gemini_chat_proxy_route_succeeds()
    {
        // 1. Test offline/default fallback when key is not configured
        config(['services.gemini.key' => '']);
        
        $response = $this->postJson(route('api.gemini.chat'), [
            'message' => 'Halo CS'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply']);
        $this->assertStringContainsString('Halo!', $response->json('reply'));

        // 2. Test input validation
        $response = $this->postJson(route('api.gemini.chat'), [
            'message' => ''
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test that all required security headers are set in the response
     */
    public function test_security_headers_are_present_on_web_responses()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Content-Security-Policy');
        
        $response->assertHeaderMissing('X-Powered-By');
        $response->assertHeaderMissing('x-powered-by');
    }

    /**
     * Test that requesting .htaccess returns 404 Not Found
     */
    public function test_htaccess_access_returns_404()
    {
        $response = $this->get('/.htaccess');
        $response->assertStatus(404);
    }

    /**
     * Test that XSRF-TOKEN cookie SameSite attribute is configured to 'strict'
     */
    public function test_xsrf_cookie_samesite_attribute_is_strict()
    {
        // Set config same_site to strict to test it
        config(['session.same_site' => 'strict']);
        
        $response = $this->get('/');
        $response->assertStatus(200);
        
        $cookies = $response->headers->getCookies();
        $xsrfCookieFound = false;
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $xsrfCookieFound = true;
                $this->assertEquals('strict', $cookie->getSameSite());
            }
        }
        
        $this->assertTrue($xsrfCookieFound, 'XSRF-TOKEN cookie was not set in the response.');
    }

    /**
     * Test that command-line and scraper user-agents are blocked when requested
     */
    public function test_scraper_user_agents_are_blocked()
    {
        // 1. Test curl is blocked
        $response = $this->withHeaders([
            'X-Test-Force-UA-Block' => '1',
            'User-Agent' => 'curl/7.68.0'
        ])->get('/');
        $response->assertStatus(404);

        // 2. Test empty User-Agent is blocked
        $response = $this->withHeaders([
            'X-Test-Force-UA-Block' => '1',
            'User-Agent' => ''
        ])->get('/');
        $response->assertStatus(404);

        // 3. Test normal browser is allowed
        $response = $this->withHeaders([
            'X-Test-Force-UA-Block' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test CORS preflight (OPTIONS) request does not allow arbitrary origins
     */
    public function test_cors_preflight_request_does_not_allow_all_origins()
    {
        $response = $this->call('OPTIONS', '/api/gemini/chat', [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST'
        ]);

        $response->assertStatus(204);
        
        $allowOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $this->assertNotEquals('*', $allowOrigin);
        $this->assertNotEquals('https://evil.com', $allowOrigin);
    }
}

