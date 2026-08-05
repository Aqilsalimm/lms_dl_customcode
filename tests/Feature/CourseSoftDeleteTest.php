<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    private function createCourseForInstructor($instructorId)
    {
        return Course::create([
            'instructor_id' => $instructorId,
            'title' => 'Test Course ' . uniqid(),
            'slug' => 'test-course-' . uniqid(),
            'level' => 'Umum',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    public function test_instructor_can_soft_delete_own_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor->id);

        $response = $this->actingAs($instructor)
            ->delete(route('course-builder.destroy', $course));

        $response->assertRedirect();
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_instructor_cannot_soft_delete_other_course()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor2->id);

        $response = $this->actingAs($instructor1)
            ->delete(route('course-builder.destroy', $course));

        $response->assertStatus(403);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'deleted_at' => null]);
    }

    public function test_instructor_can_view_trashed_courses()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor->id);
        $course->delete();

        $response = $this->actingAs($instructor)
            ->get(route('course-builder.trashed'));

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_instructor_can_restore_trashed_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor->id);
        $course->delete();

        $response = $this->actingAs($instructor)
            ->post(route('course-builder.restore', $course->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'deleted_at' => null]);
    }

    public function test_instructor_can_force_delete_trashed_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor->id);
        $course->delete();

        $response = $this->actingAs($instructor)
            ->delete(route('course-builder.force-delete', $course->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }
    public function test_catalog_cache_is_invalidated_when_course_soft_deleted()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->createCourseForInstructor($instructor->id);

        $this->get(route('courses.index'))->assertSee($course->title);  // warm cache

        $this->actingAs($instructor)->delete(route('course-builder.destroy', $course));

        $this->get(route('courses.index'))->assertDontSee($course->title);
    }
}
