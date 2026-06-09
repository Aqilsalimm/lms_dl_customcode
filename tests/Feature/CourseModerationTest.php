<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass license requirement
        Setting::updateOrCreate(
            ['key' => 'license_key'],
            ['value' => 'DRSTHA-DEVELOPER-BYPASS-9999']
        );

        // Create instructor and admin users
        $this->instructor = User::factory()->create([
            'role' => 'instructor',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /** @test */
    public function test_instructor_course_status_becomes_pending_when_moderation_is_enabled()
    {
        // 1. Enable course moderation
        Setting::updateOrCreate(
            ['key' => 'instructor_course_moderation'],
            ['value' => 'true']
        );

        // Create a draft course owned by the instructor
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Test Course Title',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        // 2. Act as instructor and update status to published
        $response = $this->actingAs($this->instructor)
            ->putJson(route('course-builder.update', $course->id), [
                'title' => 'Updated Course Title',
                'price' => 150000,
                'level' => 'Umum',
                'status' => 'published', // Try to publish
            ]);

        $response->assertStatus(200);

        // 3. Assert status is intercepted and set to pending
        $this->assertEquals('pending', $course->fresh()->status);
    }

    /** @test */
    public function test_instructor_course_status_can_be_published_directly_when_moderation_is_disabled()
    {
        // 1. Disable course moderation
        Setting::updateOrCreate(
            ['key' => 'instructor_course_moderation'],
            ['value' => 'false']
        );

        // Create a draft course owned by the instructor
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Test Course Title',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        // 2. Act as instructor and update status to published
        $response = $this->actingAs($this->instructor)
            ->putJson(route('course-builder.update', $course->id), [
                'title' => 'Updated Course Title',
                'price' => 150000,
                'level' => 'Umum',
                'status' => 'published',
            ]);

        $response->assertStatus(200);

        // 3. Assert status is published directly
        $this->assertEquals('published', $course->fresh()->status);
    }

    /** @test */
    public function test_admin_can_publish_course_directly_even_when_moderation_is_enabled()
    {
        // 1. Enable course moderation
        Setting::updateOrCreate(
            ['key' => 'instructor_course_moderation'],
            ['value' => 'true']
        );

        // Create a draft course owned by the instructor
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Test Course Title',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        // 2. Act as admin and update status to published
        $response = $this->actingAs($this->admin)
            ->putJson(route('course-builder.update', $course->id), [
                'title' => 'Approved Course Title',
                'price' => 150000,
                'level' => 'Umum',
                'status' => 'published',
            ]);

        $response->assertStatus(200);

        // 3. Assert status is published
        $this->assertEquals('published', $course->fresh()->status);
    }

    /** @test */
    public function test_instructor_imported_course_becomes_pending_under_moderation()
    {
        // 1. Enable course moderation
        Setting::updateOrCreate(
            ['key' => 'instructor_course_moderation'],
            ['value' => 'true']
        );

        // 2. Act as instructor and run import mapping
        $this->actingAs($this->instructor);

        $import = new \App\Imports\CoursesImport();
        $course = $import->model([
            'title' => 'Imported Course Title',
            'level' => 'Umum',
            'price' => 200000,
            'status' => 'published',
        ]);

        // 3. Assert that status is pending
        $this->assertEquals('pending', $course->status);
    }
}
