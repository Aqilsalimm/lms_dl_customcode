<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure mock Google client credentials for test environment
        Config::set('services.google.client_id', 'mock-client-id');
        Config::set('services.google.client_secret', 'mock-client-secret');
        Config::set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    public function test_google_redirects_successfully_if_configured(): void
    {
        $response = $this->get(route('auth.google'));

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_google_redirects_to_callback_with_error_if_not_configured(): void
    {
        // Clear configuration
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);

        $response = $this->get(route('auth.google'));

        $response->assertViewIs('auth.google-callback');
        $this->assertFalse($response['authData']['success']);
        $this->assertStringContainsString('Client Secret belum di-konfigurasi', $response['authData']['error']);
    }

    public function test_google_callback_creates_and_authenticates_new_user(): void
    {
        $googleUser = $this->createMock(SocialiteUser::class);
        $googleUser->method('getId')->willReturn('123456');
        $googleUser->method('getEmail')->willReturn('newuser@example.com');
        $googleUser->method('getName')->willReturn('New Google User');
        $googleUser->method('getAvatar')->willReturn('https://avatar.url');
        $googleUser->token = 'google-token-123';

        $provider = $this->createMock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->method('user')->willReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'google_id' => '123456',
        ]);

        $this->assertAuthenticated();
        $response->assertViewIs('auth.google-callback');
        $this->assertTrue($response['authData']['success']);
    }

    public function test_google_callback_authenticates_existing_linked_user(): void
    {
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'google_id' => '123456',
            'google_token' => 'old-token',
            'role' => 'student',
            'status' => 'active',
        ]);

        $googleUser = $this->createMock(SocialiteUser::class);
        $googleUser->method('getId')->willReturn('123456');
        $googleUser->token = 'new-token';

        $provider = $this->createMock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->method('user')->willReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'google_token' => 'new-token',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertViewIs('auth.google-callback');
        $this->assertTrue($response['authData']['success']);
    }

    public function test_google_callback_links_existing_standard_user_by_email(): void
    {
        $user = User::create([
            'name' => 'Standard User',
            'email' => 'standard@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $googleUser = $this->createMock(SocialiteUser::class);
        $googleUser->method('getId')->willReturn('123456');
        $googleUser->method('getEmail')->willReturn('standard@example.com');
        $googleUser->token = 'new-token';

        $provider = $this->createMock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->method('user')->willReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'standard@example.com',
            'google_id' => '123456',
            'google_token' => 'new-token',
        ]);

        $this->assertAuthenticatedAs($user);
        // Verify password was NOT wiped
        $this->assertNotNull($user->fresh()->password);
        $response->assertViewIs('auth.google-callback');
        $this->assertTrue($response['authData']['success']);
    }
}
