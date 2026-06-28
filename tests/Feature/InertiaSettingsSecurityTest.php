<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InertiaSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some public and sensitive settings
        Setting::create(['key' => 'midtrans_client_key', 'value' => 'Mid-client-XXXXX']);
        Setting::create(['key' => 'course_logo', 'value' => '/images/logo.png']);
        Setting::create(['key' => 'admin_email', 'value' => 'admin@drasthabest.com']);
        Setting::create(['key' => 'admin_name', 'value' => 'Super Admin']);
        Setting::create(['key' => 'revenue_share_admin', 'value' => '30']);
    }

    public function test_shared_inertia_props_only_contain_whitelisted_settings()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Inertia shared props are attached to the 'page' key in views
        $inertiaSettings = $response->original->getData()['page']['props']['settings'] ?? [];

        // Assert whitelisted settings are present
        $this->assertArrayHasKey('midtrans_client_key', $inertiaSettings);
        $this->assertEquals('Mid-client-XXXXX', $inertiaSettings['midtrans_client_key']);
        $this->assertArrayHasKey('course_logo', $inertiaSettings);
        $this->assertEquals('/images/logo.png', $inertiaSettings['course_logo']);

        // Assert sensitive/unlisted settings are NOT present
        $this->assertArrayNotHasKey('admin_email', $inertiaSettings);
        $this->assertArrayNotHasKey('admin_name', $inertiaSettings);
        $this->assertArrayNotHasKey('revenue_share_admin', $inertiaSettings);
    }
}
