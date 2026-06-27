<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CoursesImport;

class CourseBuilderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_instructor_cannot_add_module_to_another_instructor_course()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($instructor2)
            ->post(route('course-builder.modules.store', $course->id), [
                'title' => 'Malicious Module',
            ]);

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_edit_another_instructor_module()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($instructor2)
            ->put(route('course-builder.modules.update', $module->id), [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_delete_another_instructor_module()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($instructor2)
            ->delete(route('course-builder.modules.destroy', $module->id));

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_add_lesson_to_another_instructor_module()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($instructor2)
            ->post(route('course-builder.lessons.store', $module->id), [
                'title' => 'Malicious Lesson',
                'duration_minutes' => 10,
            ]);

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_edit_another_instructor_lesson()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 0,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1',
            'duration_minutes' => 10,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($instructor2)
            ->put(route('course-builder.lessons.update', $lesson->id), [
                'title' => 'Hacked Lesson Title',
                'duration_minutes' => 10,
            ]);

        $response->assertStatus(403);
    }

    public function test_instructor_cannot_delete_another_instructor_lesson()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'sort_order' => 0,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1',
            'duration_minutes' => 10,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($instructor2)
            ->delete(route('course-builder.lessons.destroy', $lesson->id));

        $response->assertStatus(403);
    }

    public function test_instructor_import_forces_own_instructor_id()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $otherUser = User::factory()->create(['role' => 'instructor']);

        $import = new CoursesImport();
        
        $this->actingAs($instructor);

        $row = [
            'title' => 'Imported Course Test',
            'instructor_id' => $otherUser->id,
            'price' => 200000,
            'level' => 'SMA',
            'capacity' => 15,
            'status' => 'draft'
        ];

        $course = $import->model($row);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals($instructor->id, $course->instructor_id);
    }

    public function test_admin_import_allows_custom_instructor_id()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $import = new CoursesImport();
        
        $this->actingAs($admin);

        $row = [
            'title' => 'Admin Imported Course',
            'instructor_id' => $instructor->id,
            'price' => 300000,
            'level' => 'SMA',
            'capacity' => 30,
            'status' => 'draft'
        ];

        $course = $import->model($row);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals($instructor->id, $course->instructor_id);
    }
}
