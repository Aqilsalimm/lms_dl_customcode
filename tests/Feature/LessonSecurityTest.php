<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class LessonSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_lesson_content_is_stripped_from_inertia_props_and_served_encrypted()
    {
        // 1. Setup Models
        $student = User::factory()->create(['role' => 'student']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        
        $course = Course::create([
            'title' => 'Secure Coding Course',
            'slug' => 'secure-coding-course',
            'instructor_id' => $instructor->id,
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Introduction',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1.1: Web Security Principles',
            'content' => 'This is a highly secret learning material containing SQL Injection details.',
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_minutes' => 15,
            'sort_order' => 1,
        ]);

        // 2. Enroll student to course
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // 3. Test Inertia Learn Page
        $response = $this->actingAs($student)
            ->get(route('courses.learn', $course->slug));

        $response->assertStatus(200);

        // Assert that the lesson properties like content/video_url are null or not present in the course prop
        $response->assertInertia(function (Assert $page) {
            $page->component('Courses/Learn')
                ->has('decryptionKey')
                ->missing('course.modules.0.lessons.0.content')
                ->missing('course.modules.0.lessons.0.video_url');
        });

        // 4. Test secure endpoint returns encrypted content
        $decryptionKey = session('lesson_decryption_key');
        $this->assertNotEmpty($decryptionKey);

        $endpointResponse = $this->actingAs($student)
            ->get(route('courses.lessons.content', [$course->slug, $lesson->id]));

        $endpointResponse->assertStatus(200);
        $endpointResponse->assertJsonStructure(['ciphertext', 'iv']);

        // Let's decrypt and assert the original content is correct
        $json = $endpointResponse->json();
        $ciphertext = base64_decode($json['ciphertext']);
        $iv = hex2bin($json['iv']);
        $key = hash('sha256', $decryptionKey, true);
        
        $decryptedJson = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $decryptedData = json_decode($decryptedJson, true);

        $this->assertEquals('This is a highly secret learning material containing SQL Injection details.', $decryptedData['content']);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $decryptedData['video_url']);
    }

    public function test_unenrolled_user_cannot_access_secure_lesson_content()
    {
        $student = User::factory()->create(['role' => 'student']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        
        $course = Course::create([
            'title' => 'Secure Coding Course',
            'slug' => 'secure-coding-course',
            'instructor_id' => $instructor->id,
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Introduction',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Lesson 1.1: Web Security Principles',
            'content' => 'Secret.',
            'video_url' => 'https://youtube.com',
            'duration_minutes' => 10,
        ]);

        // Access learn page (should redirect or deny access)
        $response = $this->actingAs($student)
            ->get(route('courses.learn', $course->slug));
        
        // As course visibility might allow show page, but learn page requires subscription
        $response->assertRedirect();

        // Direct fetch API endpoint should return 403 Forbidden
        $endpointResponse = $this->actingAs($student)
            ->get(route('courses.lessons.content', [$course->slug, $lesson->id]));

        $endpointResponse->assertStatus(403);
    }
}
