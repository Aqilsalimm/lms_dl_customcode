<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheLifecycleTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    /**
     * Test Setting cache invalidates when setting model is updated or deleted.
     */
    public function test_setting_cache_invalidates_on_update(): void
    {
        Cache::flush();

        Setting::setValue('test_cache_key', 'initial_value');
        $this->assertEquals('initial_value', Setting::getValue('test_cache_key'));

        // Update setting
        Setting::setValue('test_cache_key', 'updated_value');
        $this->assertEquals('updated_value', Setting::getValue('test_cache_key'));

        // Delete setting
        $setting = Setting::where('key', 'test_cache_key')->first();
        if ($setting) {
            $setting->delete();
        }
        $this->assertNull(Setting::getValue('test_cache_key'));
    }

    /**
     * Test Course duration/session cache invalidation when model touches.
     */
    private function createCourseForTest()
    {
        $instructor = \App\Models\User::factory()->create(['role' => 'instructor']);
        return \App\Models\Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course ' . uniqid(),
            'slug' => 'test-course-' . uniqid(),
            'level' => 'Umum',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    public function test_course_cache_invalidates_on_model_update(): void
    {
        Cache::flush();

        $course = $this->createCourseForTest();

        // Access duration and sessions to warm up cache
        $initialDuration = $course->duration;
        $initialSessions = $course->sessions;

        $this->assertTrue(Cache::has("course_{$course->id}_duration"));
        $this->assertTrue(Cache::has("course_{$course->id}_sessions"));

        // Touch course model to fire saving/saved hooks
        $course->touch();

        $this->assertFalse(Cache::has("course_{$course->id}_duration"));
        $this->assertFalse(Cache::has("course_{$course->id}_sessions"));
    }

    public function test_course_duration_cache_invalidated_when_lesson_added(): void
    {
        Cache::flush();

        $course = $this->createCourseForTest();
        $module = \App\Models\Module::create([
            'course_id' => $course->id,
            'title' => 'Test Module ' . uniqid(),
            'order' => 1
        ]);

        // Access duration and sessions to warm up cache
        $initialDuration = $course->duration;
        $initialSessions = $course->sessions;

        $this->assertTrue(Cache::has("course_{$course->id}_duration"));
        
        \App\Models\Lesson::create([
            'module_id' => $module->id,
            'title' => 'New Test Lesson',
            'order' => 99,
            'duration' => 15,
            'is_free' => false,
        ]);

        $this->assertFalse(Cache::has("course_{$course->id}_duration"));
    }
}
