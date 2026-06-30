<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (request()->secure() || str_contains(request()->header('X-Forwarded-Proto', ''), 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Configure Mailpit in local environment to bypass Brevo API completely
        if (app()->environment('local')) {
            config(['mail.default' => 'smtp']);
            config(['mail.mailers.smtp.host' => 'mailpit']);
            config(['mail.mailers.smtp.port' => 1025]);
        }

        \Illuminate\Support\Facades\Mail::extend('brevo', function (array $config) {
            if (app()->environment('local')) {
                return new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport('mailpit', 1025);
            }
            return new \App\Mail\Transports\BrevoApiTransport($config['key'] ?? env('BREVO_API_KEY'));
        });

        // Automatically ensure a dedicated dev admin account exists in local development
        if (app()->environment('local') && !app()->runningInConsole()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                    \App\Models\User::firstOrCreate([
                        'email' => 'dev-admin@drasthabest.com'
                    ], [
                        'name' => 'Dev Admin',
                        'role' => 'admin',
                        'password' => bcrypt('password'),
                        'status' => 'active'
                    ]);
                }
            } catch (\Exception $e) {
                // Ignore database connection/migration errors during boot
            }
        }
    }
}
