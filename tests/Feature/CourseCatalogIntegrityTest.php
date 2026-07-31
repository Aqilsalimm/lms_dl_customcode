<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseCatalogIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Setting::setValue('course_visibility', 'false');
    }

    /**
     * Test published courses appear in /courses catalog Inertia props.
     */
    public function test_published_courses_are_returned_in_catalog_props(): void
    {
        $category = Category::create(['name' => 'Pemrograman', 'slug' => 'pemrograman']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course1 = Course::create([
            'title' => 'Python Class : Pemrograman dan Perkenalan Bahasa Python',
            'slug' => 'python-class-pemrograman',
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'status' => 'published',
            'level' => 'Kelas Umum',
            'price' => 500000,
            'course_type' => 'async'
        ]);

        $course2 = Course::create([
            'title' => 'Website Class : Pemrograman Website dengan HTML dan CSS',
            'slug' => 'website-class-html-css',
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'status' => 'published',
            'level' => 'Kelas SMA',
            'price' => 450000,
            'course_type' => 'async'
        ]);

        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 2)
            ->where('courses.data', function ($coursesData) use ($course1, $course2) {
                $titles = collect($coursesData)->pluck('title')->all();
                return in_array($course1->title, $titles) && in_array($course2->title, $titles);
            })
        );
    }

    /**
     * Test filtering by level handles both short and prefixed level names (e.g. 'Kelas SMA' & 'SMA').
     */
    public function test_filtering_by_level_handles_prefixed_and_short_level_names(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        Course::create([
            'title' => 'Website Class SMA',
            'slug' => 'website-class-sma',
            'instructor_id' => $instructor->id,
            'status' => 'published',
            'level' => 'Kelas SMA',
            'price' => 450000,
        ]);

        Course::create([
            'title' => 'Audit Class Umum',
            'slug' => 'audit-class-umum',
            'instructor_id' => $instructor->id,
            'status' => 'published',
            'level' => 'Kelas Umum',
            'price' => 600000,
        ]);

        // Query by 'Kelas SMA'
        $responseSma = $this->get('/courses?level=Kelas SMA');
        $responseSma->assertStatus(200);
        $responseSma->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Website Class SMA')
        );

        // Query by 'SMA'
        $responseSmaShort = $this->get('/courses?level=SMA');
        $responseSmaShort->assertStatus(200);
        $responseSmaShort->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Website Class SMA')
        );
    }

    /**
     * Test filtering by course_type (async vs live_class).
     */
    public function test_filtering_by_course_type_handles_async_and_live_class(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        Course::create([
            'title' => 'Async Course Python',
            'slug' => 'async-course-python',
            'instructor_id' => $instructor->id,
            'status' => 'published',
            'level' => 'Umum',
            'price' => 500000,
            'course_type' => 'async',
        ]);

        Course::create([
            'title' => 'Live Workshop DevOps',
            'slug' => 'live-workshop-devops',
            'instructor_id' => $instructor->id,
            'status' => 'published',
            'level' => 'Umum',
            'price' => 750000,
            'course_type' => 'live_class',
        ]);

        // Filter type=async
        $responseAsync = $this->get('/courses?type=async');
        $responseAsync->assertStatus(200);
        $responseAsync->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Async Course Python')
        );

        // Filter type=live_class
        $responseLive = $this->get('/courses?type=live_class');
        $responseLive->assertStatus(200);
        $responseLive->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Live Workshop DevOps')
        );
    }

    /**
     * Test cache invalidation when a course status is updated to published.
     */
    public function test_cache_invalidation_when_course_status_changes(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Draft Course to be Published',
            'slug' => 'draft-course-published',
            'instructor_id' => $instructor->id,
            'status' => 'draft',
            'level' => 'Umum',
            'price' => 100000,
        ]);

        // First catalog fetch - 0 courses returned
        $response1 = $this->get('/courses');
        $response1->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 0)
        );

        // Update course to published
        $course->update(['status' => 'published']);

        // Second catalog fetch - 1 course returned (cache flushed)
        $response2 = $this->get('/courses');
        $response2->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Draft Course to be Published')
        );
    }
}
