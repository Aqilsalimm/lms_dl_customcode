<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-abandoned-cart-reminders')->everyMinute();
Schedule::command('app:check-expired-enrollments')->everyMinute();
Schedule::command('app:cancel-expired-orders')->everyMinute();
Schedule::command('billing:process-monthly')->dailyAt('00:00');
Schedule::command('liveclass:remind')->hourly();
