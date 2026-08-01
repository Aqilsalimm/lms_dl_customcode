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

    /**
     * Test parent category filtering returns courses in subcategories.
     */
    public function test_parent_category_filter_includes_subcategories(): void
    {
        $parentCategory = Category::create(['name' => 'IT & Software', 'slug' => 'it-software']);
        $subCategory = Category::create([
            'name' => 'Web Development', 
            'slug' => 'web-development', 
            'parent_id' => $parentCategory->id
        ]);
        
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Web Dev Subcategory Class',
            'slug' => 'web-dev-subcategory-class',
            'instructor_id' => $instructor->id,
            'category_id' => $subCategory->id,
            'status' => 'published',
            'level' => 'Umum',
            'price' => 200000,
            'course_type' => 'async'
        ]);

        // Filter by parent category slug
        $response = $this->get('/courses?category=it-software');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Web Dev Subcategory Class')
        );

        // Filter via AJAX API
        $ajaxResponse = $this->get('/api/courses/search?category=it-software');
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonFragment([
            'title' => 'Web Dev Subcategory Class'
        ]);
    }

    /**
     * Test that filtering by category and level of the same concept uses OR logic
     * and returns courses matching either category (including children) or level.
     */
    public function test_conceptual_or_filtering_for_category_and_level(): void
    {
        $workshopCategory = Category::create(['name' => 'Workshop', 'slug' => 'workshop']);
        $subWorkshopCategory = Category::create([
            'name' => 'Advanced Workshop',
            'slug' => 'advanced-workshop',
            'parent_id' => $workshopCategory->id
        ]);
        
        $otherCategory = Category::create(['name' => 'Other Category', 'slug' => 'other-category']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        // Course 1: Category is subcategory of workshop, level is 'Umum' (should match)
        $course1 = Course::create([
            'title' => 'Advanced Coding Workshop',
            'slug' => 'advanced-coding-workshop',
            'instructor_id' => $instructor->id,
            'category_id' => $subWorkshopCategory->id,
            'status' => 'published',
            'level' => 'Umum',
            'price' => 150000,
            'course_type' => 'async'
        ]);

        // Course 2: Level is 'Workshop', category is 'Other Category' (should match)
        $course2 = Course::create([
            'title' => 'General Level Workshop Course',
            'slug' => 'general-level-workshop-course',
            'instructor_id' => $instructor->id,
            'category_id' => $otherCategory->id,
            'status' => 'published',
            'level' => 'Workshop',
            'price' => 250000,
            'course_type' => 'async'
        ]);

        // Course 3: Neither matches (should NOT match)
        $course3 = Course::create([
            'title' => 'SD Mathematics Course',
            'slug' => 'sd-mathematics-course',
            'instructor_id' => $instructor->id,
            'category_id' => $otherCategory->id,
            'status' => 'published',
            'level' => 'Kelas SD',
            'price' => 100000,
            'course_type' => 'async'
        ]);

        // Filter by category=workshop and level=Workshop (same concept)
        $response = $this->get('/courses?category=workshop&level=Workshop');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courses/Index')
            ->has('courses.data', 2)
            ->where('courses.data', function ($coursesData) use ($course1, $course2) {
                $titles = collect($coursesData)->pluck('title')->all();
                return in_array($course1->title, $titles) && in_array($course2->title, $titles);
            })
        );

        // Filter via AJAX API
        $ajaxResponse = $this->get('/api/courses/search?category=workshop&level=Workshop');
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJsonFragment(['title' => 'Advanced Coding Workshop'])
                    ->assertJsonFragment(['title' => 'General Level Workshop Course'])
                    ->assertJsonMissing(['title' => 'SD Mathematics Course']);
    }
}
