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

    public function test_correct_option_index_is_concealed_from_students()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Course with Quiz',
            'instructor_id' => $instructor->id,
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
            'slug' => 'course-with-quiz',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Quiz Module',
            'sort_order' => 0,
        ]);

        $quiz = Quiz::create([
            'module_id' => $module->id,
            'title' => 'Security Quiz',
            'time_limit_minutes' => 10,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is 1 + 1?',
            'options' => ['1', '2', '3', '4'],
            'correct_option_index' => 1,
            'sort_order' => 0,
        ]);

        // 1. Check public show page does not disclose key
        $response = $this->get(route('courses.show', $course->slug));
        $response->assertStatus(200);
        
        // Assert correct_option_index is hidden in raw Inertia response data
        $pageData = $response->original->getData();
        $inertiaData = $pageData['page'] ?? [];
        $props = $inertiaData['props'] ?? [];
        $quizQuestions = $props['course']['modules'][0]['quizzes'][0]['questions'] ?? [];
        
        $this->assertNotEmpty($quizQuestions);
        $this->assertArrayNotHasKey('correct_option_index', $quizQuestions[0]);

        // Enroll student to allow access to learn page
        \App\Models\Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // 2. Check classroom learn page does not disclose key
        $response = $this->actingAs($student)->get(route('courses.learn', $course->slug));
        $response->assertStatus(200);

        $pageData = $response->original->getData();
        $inertiaData = $pageData['page'] ?? [];
        $props = $inertiaData['props'] ?? [];
        $learnQuestions = $props['course']['modules'][0]['quizzes'][0]['questions'] ?? [];
        
        $this->assertNotEmpty($learnQuestions);
        $this->assertArrayNotHasKey('correct_option_index', $learnQuestions[0]);
    }

    public function test_ziggy_routes_are_filtered_by_role()
    {
        // 1. Guest access should only see public routes in the HTML view output
        $response = $this->get('/');
        $response->assertStatus(200);
        $html = $response->getContent();
        
        // Assert we see public routes in Ziggy config
        $this->assertStringContainsString('courses.show', $html);
        $this->assertStringContainsString('login', $html);
        
        // Assert we DO NOT see admin or course-builder routes
        $this->assertStringNotContainsString('dashboard.settings.course-builder.courses', $html);
        $this->assertStringNotContainsString('dashboard.users.manage', $html);

        // 2. Student access
        $student = User::factory()->create(['role' => 'student']);
        $response = $this->actingAs($student)->get('/');
        $response->assertStatus(200);
        $html = $response->getContent();
        
        $this->assertStringContainsString('dashboard.enrolled-courses', $html);
        $this->assertStringNotContainsString('dashboard.settings.course-builder.courses', $html);
        $this->assertStringNotContainsString('dashboard.users.manage', $html);

        // 3. Admin access
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/');
        $response->assertStatus(200);
        $html = $response->getContent();
        
        $this->assertStringContainsString('dashboard.settings.course-builder.courses', $html);
        $this->assertStringContainsString('dashboard.users.manage', $html);
    }

    public function test_security_headers_are_present_on_successful_responses()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_are_present_on_blocked_user_agents()
    {
        $response = $this->withHeaders([
            'User-Agent' => 'curl/7.68.0',
            'X-Test-Force-UA-Block' => '1'
        ])->get('/');
        
        $response->assertStatus(404);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
