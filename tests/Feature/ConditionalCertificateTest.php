<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_session_certificate_unlocks_only_when_prerequisite_module_is_completed()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Laravel Masterclass',
            'slug' => 'laravel-masterclass',
            'instructor_id' => $instructor->id,
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $module1 = Module::create(['course_id' => $course->id, 'title' => 'Sesi 1: Dasar', 'sort_order' => 0]);
        $module2 = Module::create(['course_id' => $course->id, 'title' => 'Sesi 2: Lanjutan', 'sort_order' => 1]);

        $lesson1 = Lesson::create(['module_id' => $module1->id, 'title' => 'Lesson 1', 'duration_minutes' => 10, 'sort_order' => 0]);
        $lesson2 = Lesson::create(['module_id' => $module2->id, 'title' => 'Lesson 2', 'duration_minutes' => 10, 'sort_order' => 0]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'completed_lessons' => [],
        ]);

        // 1. Fetch certificates list (auto-generates default session certs)
        $response = $this->actingAs($student)
            ->getJson(route('courses.certificates.index', $course->slug));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'certificates'); // Cert Sesi 1, Cert Sesi 2, Cert Completion

        $certSession1Data = collect($response->json('certificates'))->firstWhere('type', 'session');
        $this->assertFalse($certSession1Data['unlocked']);
        $this->assertEquals(0, $certSession1Data['percentage']);

        // 2. Complete Lesson 1 (Module 1)
        $enrollment->update([
            'completed_lessons' => [$lesson1->id]
        ]);

        $responseAfterMod1 = $this->actingAs($student)
            ->getJson(route('courses.certificates.index', $course->slug));

        $certsAfter = collect($responseAfterMod1->json('certificates'));
        $cert1 = $certsAfter->first(fn($c) => in_array($module1->id, $c['module_ids']));
        $cert2 = $certsAfter->first(fn($c) => in_array($module2->id, $c['module_ids']) && count($c['module_ids']) === 1);

        $this->assertTrue($cert1['unlocked']);
        $this->assertFalse($cert2['unlocked']);

        // 3. Claim Session 1 Certificate
        $certObj1 = Certificate::find($cert1['id']);
        $claimResponse = $this->actingAs($student)
            ->postJson(route('courses.certificates.claim', [$course->slug, $certObj1->id]));

        $claimResponse->assertStatus(200);
        $claimResponse->assertJsonStructure(['message', 'certificate_code']);
        $this->assertDatabaseHas('user_certificates', [
            'user_id' => $student->id,
            'certificate_id' => $certObj1->id,
        ]);
    }

    public function test_module_session_certificate_fields_and_is_completed_by_method()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Mastering Vue 3',
            'slug' => 'mastering-vue-3',
            'instructor_id' => $instructor->id,
            'price' => 200000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Bab 1: Options API vs Composition API',
            'sort_order' => 0,
            'has_session_certificate' => true,
            'certificate_bg_path' => 'certificates/bg_bab1.png',
            'text_name_y_position' => 45,
            'text_title_y_position' => 55,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Pengenalan Composition API',
            'duration_minutes' => 15,
            'sort_order' => 0,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'completed_lessons' => [],
        ]);

        $this->assertTrue($module->has_session_certificate);
        $this->assertEquals('certificates/bg_bab1.png', $module->certificate_bg_path);
        $this->assertFalse($module->isCompletedBy($student));

        // Complete lesson
        $enrollment->update(['completed_lessons' => [$lesson->id]]);
        $this->assertTrue($module->fresh()->isCompletedBy($student));
    }

    public function test_instructor_can_add_and_update_module_with_session_certificate_and_background_image()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'React Mastery',
            'slug' => 'react-mastery',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $bgFile = \Illuminate\Http\UploadedFile::fake()->image('cert_bg.png', 1000, 700);

        $response = $this->actingAs($instructor)
            ->postJson("/course-builder/courses/{$course->id}/modules", [
                'title' => 'Sesi 1: React Fundamentals',
                'has_session_certificate' => '1',
                'certificate_bg' => $bgFile,
                'text_name_y_position' => 42,
                'text_title_y_position' => 58,
            ]);

        $response->assertStatus(200);
        $moduleId = $response->json('module.id');

        $this->assertDatabaseHas('modules', [
            'id' => $moduleId,
            'has_session_certificate' => 1,
            'text_name_y_position' => 42,
            'text_title_y_position' => 58,
        ]);

        $module = Module::find($moduleId);
        $this->assertNotNull($module->certificate_bg_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($module->certificate_bg_path);
    }

    public function test_user_can_download_session_certificate_pdf_when_module_is_completed()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'NodeJS Backend',
            'slug' => 'nodejs-backend',
            'instructor_id' => $instructor->id,
            'price' => 120000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Bab 1: Express REST API',
            'sort_order' => 0,
            'has_session_certificate' => true,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Routing in Express',
            'duration_minutes' => 10,
            'sort_order' => 0,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'completed_lessons' => [$lesson->id],
        ]);

        $response = $this->actingAs($student)
            ->get(route('modules.certificate.download', $module->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
