<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingCacheAndNPlusOneTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that Setting::getValue relies on Cache and does not run DB queries repeatedly.
     */
    public function test_setting_get_value_uses_cache_and_avoids_n_plus_one_queries(): void
    {
        Cache::flush();
        Setting::setValue('site_title', 'Drastha Learning LMS');

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Initial call populates cache
        $firstCall = Setting::getValue('site_title');
        $this->assertEquals('Drastha Learning LMS', $firstCall);

        // Reset query log
        DB::flushQueryLog();

        // 100 consecutive reads from cache
        for ($i = 0; $i < 100; $i++) {
            Setting::getValue('site_title');
        }

        $cachedQueryCount = collect(DB::getQueryLog())->filter(function ($q) {
            return !str_contains($q['query'], '"cache"') && !str_contains($q['query'], 'cache');
        })->count();

        $this->assertEquals(0, $cachedQueryCount, "Setting::getValue must be cached and not hit database on subsequent calls.");
    }
}
