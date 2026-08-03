<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // -----------------------------------------------------------------
        // Force Predis client (pure PHP) on shared hosting environments
        // -----------------------------------------------------------------
        // Many shared hosting providers do NOT include the `ext-redis`
        // (phpredis) PHP extension. When Laravel tries to instantiate
        // the phpredis client the request fails with:
        //   "Unable to load dynamic library 'redis' (...)"
        // Predis is a pure-PHP Redis client bundled via Composer and
        // works on any host that has PHP 8.2+ — no native extension
        // required. We force the client here, BEFORE the Redis
        // facade / config is resolved, so the boot path never tries
        // to load the missing `redis.so` shared object.
        //
        // Reference: config/database.php line 148
        //   'client' => env('REDIS_CLIENT', extension_loaded('redis')
        //       ? 'phpredis' : 'predis')
        //
        // If your hosting DOES provide ext-redis and you want to use
        // it, simply set REDIS_CLIENT=phpredis in your .env and remove
        // (or invert) the line below.
        if (! env('REDIS_CLIENT')) {
            config(['database.redis.client' => 'predis']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('quiz-submit', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(20)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

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

        // -----------------------------------------------------------------
        // Redis Pool Tuning (Shared Hosting: 1 vCPU / 2GB RAM / 40 workers)
        // -----------------------------------------------------------------
        // On a single-server deployment, Laravel opens a fresh TCP
        // connection to Redis for every PHP-FPM worker. With 40 workers
        // the default phpredis pool can grow unbounded and quickly exhaust
        // the 2GB RAM budget or hit Redis' `maxclients` limit.
        //
        // The setting below caps the per-request read timeout so a slow
        // Redis server cannot tie up a PHP-FPM worker indefinitely.
        if (config('database.redis.client') === 'phpredis') {
            try {
                Redis::connection('default')->client()->setOption(
                    \Redis::OPT_READ_TIMEOUT,
                    (string) (int) env('REDIS_READ_TIMEOUT', 1.5)
                );
            } catch (\Throwable $e) {
                // Redis may be temporarily unavailable during deploy.
                // The first real request will trigger a reconnect.
            }
        }
    }
}
