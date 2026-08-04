<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class DiagnosticRoutesTest extends TestCase
{
    /**
     * Pastikan rute diagnostik tidak terdaftar di environment production.
     * Kita menggunakan perintah artisan route:list dengan env=production.
     *
     * @return void
     */
    public function test_diagnostic_routes_hidden_in_production()
    {
        $output = shell_exec('APP_ENV=production php artisan route:list');
        
        $this->assertStringNotContainsString('test-users', $output);
        $this->assertStringNotContainsString('seed-db', $output);
        $this->assertStringNotContainsString('test-login-debug', $output);
        $this->assertStringNotContainsString('clear-app-cache', $output);
        $this->assertStringNotContainsString('test-recaptcha', $output);
        $this->assertStringNotContainsString('test-session-put', $output);
        $this->assertStringNotContainsString('test/reset-db', $output);
    }
}
