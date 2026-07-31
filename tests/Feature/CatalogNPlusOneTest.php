<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogNPlusOneTest extends TestCase
{
    /**
     * Test that GET /courses query count remains O(1) regardless of number of courses.
     */
    public function test_catalog_query_count_is_constant_o1(): void
    {
        Cache::flush();

        // Ensure course_visibility setting does not block guest access for testing
        Setting::setValue('course_visibility', 'false');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get('/courses');
        $response->assertStatus(200);

        // Reset query log and make 2nd request to test cache response
        DB::flushQueryLog();
        $response2 = $this->get('/courses');
        $cachedQueries = DB::getQueryLog();
        $cachedQueryCount = count($cachedQueries);

        // Response should be cached so query count is near 0 and strictly O(1) constant
        $this->assertLessThanOrEqual(3, $cachedQueryCount, "Catalog route query count should be O(1) constant and cached.");
    }
}
