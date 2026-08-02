<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Setting;
use App\Models\WorkshopAssessmentUserAnswer;
use App\Models\WorkshopAssessmentAttempt;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/test-users', function() { 
    return User::select('id', 'email', 'role', 'status')->get(); 
});

Route::post('/seed-db', function() { 
    Artisan::call('db:seed'); 
    return 'seeded'; 
});

Route::get('/test-login-debug', function() {
    Auth::attempt(['email' => 'student@drastha.com', 'password' => 'password']);
    return [
        'authenticated' => Auth::check(),
        'user_id' => Auth::id(),
    ];
});

Route::post('/clear-cache', function() { 
    Artisan::call('cache:clear'); 
    return 'cleared'; 
});

Route::get('/test-recaptcha', function() { 
    return Setting::where('key', 'fraud_protection_enabled')->value('value'); 
});

Route::post('/test-session-put', function() {
    session(['test_key' => 'test_value']);
    return redirect('/test-session-get');
});

Route::get('/test-session-get', function() {
    return session()->all();
});

Route::post('/test/reset-db', function() {
    Schema::disableForeignKeyConstraints();
    WorkshopAssessmentUserAnswer::truncate();
    WorkshopAssessmentAttempt::truncate();
    DB::table('sessions')->truncate();
    Setting::updateOrCreate(['key' => 'prerequisites_enabled'], ['value' => 'true']);
    Cache::forget('settings.prerequisites_enabled');
    Schema::enableForeignKeyConstraints();
    return 'reset';
});
