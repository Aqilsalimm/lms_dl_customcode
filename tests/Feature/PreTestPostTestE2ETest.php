<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\UserCertificate;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreTestPostTestE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock license validation to allow accessing routes
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    /**
     * Skenario 1: Gating Logic (Alur Pemblokiran) - Pre-Test
     * Ensure Syllabus (Lessons) is blocked if Pre-Test is not passed.
     */
    public function test_skenario_1_pre_test_gating_logic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        // 1. Setup Admin Config
        $course = Course::create([
            'title' => 'E2E Validation Course',
            'course_type' => 'video',
            'instructor_id' => $admin->id,
            'price' => 0,
            'level' => 'Umum',
            'status' => 'published',
            'enforce_prerequisites' => true, // Ensure sequential locking is enabled
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Bab 1',
            'order' => 1,
            'enable_assessment' => true,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Sesi 1',
            'order' => 1,
            'content' => 'Test Content',
        ]);

        // Pre-Test Assessment configured by Admin
        $preTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'module_id' => $module->id, // Module-level pre-test
            'type' => 'pre_test',
            'title' => 'Pre-Test Evaluasi',
            'passing_score' => 70,
            'duration_minutes' => 30,
            'is_published' => true,
        ]);
        $question = WorkshopAssessmentQuestion::create([
            'assessment_id' => $preTest->id,
            'question_text' => 'Berapa 1+1?',
            'question_type' => 'multiple_choice',
            'options' => json_encode(['1', '2', '3', '4']),
            'correct_answer' => '1', // Index 1 is '2'
            'points' => 100,
        ]);

        // Student Enrolls
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 0,
            'status' => 'completed',
        ]);

        // 2. Uji Coba Siswa (Kondisi Terkunci)
        // Accessing Syllabus (Lesson 1) directly
        $this->actingAs($student);
        $response = $this->get(route('courses.learn', ['course' => $course->slug, 'lesson' => $lesson->id]));
        
        // Render Learn.vue check: Module is_pre_completed should be false. 
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Courses/Learn')
            ->where('course.modules.0.is_pre_completed', false)
        );

        // 3. Siswa Mengerjakan Pre-Test (Syllabus Unlocked)
        // Start Assessment
        $startResponse = $this->post(route('assessments.start', $preTest->id));
        $attemptId = $startResponse->json('attempt.id');
        $this->assertNotNull($attemptId);

        // Submit Assessment with correct answer
        $this->post(route('attempts.submit', $attemptId), [
            'answers' => [
                $question->id => 1 // Correct index
            ]
        ]);

        // Verify Syllabus Unlocked
        $responseAfter = $this->get(route('courses.learn', ['course' => $course->slug, 'lesson' => $lesson->id]));
        $responseAfter->assertStatus(200);
        $responseAfter->assertInertia(fn ($page) => $page
            ->component('Courses/Learn')
            ->where('course.modules.0.is_pre_completed', true)
        );
        
        // DB validation
        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $attemptId,
            'user_id' => $student->id,
            'is_passed' => true,
        ]);
    }

    /**
     * Skenario 2: Post-Test Completion & Certificate
     */
    public function test_skenario_2_post_test_and_certificate(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        
        $course = Course::create([
            'title' => 'Certificate Course',
            'course_type' => 'video',
            'instructor_id' => 1,
            'price' => 0,
            'level' => 'Umum',
            'status' => 'published',
            'enforce_prerequisites' => false,
        ]);

        $postTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'post_test',
            'title' => 'Post-Test Kelulusan',
            'passing_score' => 80, // High passing score
            'is_published' => true,
        ]);
        $question = WorkshopAssessmentQuestion::create([
            'assessment_id' => $postTest->id,
            'question_text' => 'Post question?',
            'question_type' => 'multiple_choice',
            'options' => json_encode(['A', 'B']),
            'correct_answer' => '0',
            'points' => 100,
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($student);

        // 1. Gagal Post-Test (Nilai < KKM)
        $startFail = $this->post(route('assessments.start', $postTest->id));
        $failAttemptId = $startFail->json('attempt.id');
        $this->post(route('attempts.submit', $failAttemptId), [
            'answers' => [$question->id => 1] // Wrong answer
        ]);

        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $failAttemptId,
            'is_passed' => false,
        ]);
        $this->assertDatabaseMissing('user_certificates', ['user_id' => $student->id, 'course_id' => $course->id]);

        // 2. Lulus Post-Test (Nilai >= KKM)
        $startPass = $this->post(route('assessments.start', $postTest->id));
        $passAttemptId = $startPass->json('attempt.id');
        $this->post(route('attempts.submit', $passAttemptId), [
            'answers' => [$question->id => 0] // Correct answer
        ]);

        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $passAttemptId,
            'is_passed' => true,
        ]);
        
        // User should have a certificate generated automatically upon passing the Post Test
        $this->assertDatabaseHas('user_certificates', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
        
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Skenario 3: Backend Validation untuk Integritas (Multiple Tab / Duplicate Submission Prevention)
     */
    public function test_skenario_3_resiliency_and_invalid_submission(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        
        $course = Course::create([
            'title' => 'Resiliency Course',
            'course_type' => 'video',
            'instructor_id' => 1,
            'status' => 'published',
        ]);

        $postTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'post_test',
            'title' => 'Resiliency Test',
            'passing_score' => 50,
            'is_published' => true,
        ]);

        $question = WorkshopAssessmentQuestion::create([
            'assessment_id' => $postTest->id,
            'question_text' => 'Q1?',
            'question_type' => 'multiple_choice',
            'options' => json_encode(['A']),
            'correct_answer' => '0',
            'points' => 100,
        ]);

        $this->actingAs($student);
        
        $startResp = $this->post(route('assessments.start', $postTest->id));
        $attemptId = $startResp->json('attempt.id');

        // Submit the first time (Success)
        $this->post(route('attempts.submit', $attemptId), [
            'answers' => [$question->id => 0]
        ])->assertStatus(200);

        // Submit the second time for the SAME attempt (Simulating stale multiple tab)
        // Should be rejected by backend because status is already 'completed'
        $this->postJson(route('attempts.submit', $attemptId), [
            'answers' => [$question->id => 0]
        ])->assertStatus(403)
          ->assertJson(['message' => 'Anda sudah menyelesaikan tes ini.']);
    }
}
