<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_admin_and_instructor_can_access_assessment_analytics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Data Science Live Workshop',
            'slug' => 'data-science-live-workshop',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 300000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        // Admin access -> 200 OK
        $adminRes = $this->actingAs($admin)->get(route('assessments.analytics', $course->id));
        $adminRes->assertStatus(200);

        // Instructor (Course Owner) access -> 200 OK
        $instRes = $this->actingAs($instructor)->get(route('assessments.analytics', $course->id));
        $instRes->assertStatus(200);

        // Student access -> 403 Forbidden
        $studentRes = $this->actingAs($student)->get(route('assessments.analytics', $course->id));
        $studentRes->assertStatus(403);
    }

    public function test_assessment_analytics_calculates_averages_item_analysis_and_at_risk_students()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        
        $course = Course::create([
            'title' => 'AI Engineer Masterclass',
            'slug' => 'ai-engineer-masterclass',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 500000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        // Create 2 Students
        $goodStudent = User::factory()->create(['name' => 'Siswa Pintar', 'email' => 'good@drastha.com', 'role' => 'student']);
        $strugglingStudent = User::factory()->create(['name' => 'Siswa Kesulitan', 'email' => 'struggling@drastha.com', 'role' => 'student']);

        Enrollment::create(['user_id' => $goodStudent->id, 'course_id' => $course->id, 'status' => 'active']);
        Enrollment::create(['user_id' => $strugglingStudent->id, 'course_id' => $course->id, 'status' => 'active']);

        // Create Pre-Test
        $preTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-Test AI',
            'duration_minutes' => 15,
            'passing_score' => 70,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        $q1 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $preTest->id,
            'question_text' => 'Apa itu Neural Network?',
            'options' => ['Model AI', 'Bahasa Pemrograman', 'Sistem Operasi', 'Database'],
            'correct_answer' => 'Model AI',
            'points' => 50,
            'order_number' => 1,
        ]);

        $q2 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $preTest->id,
            'question_text' => 'Fungsi mana yang digunakan untuk backpropagation?',
            'options' => ['optimizer.step()', 'loss.backward()', 'torch.no_grad()', 'model.eval()'],
            'correct_answer' => 'loss.backward()',
            'points' => 50,
            'order_number' => 2,
        ]);

        // Good student completes Pre-Test: score 100 (Passed)
        $attempt1 = WorkshopAssessmentAttempt::create([
            'user_id' => $goodStudent->id,
            'assessment_id' => $preTest->id,
            'attempt_number' => 1,
            'total_score' => 100,
            'is_passed' => true,
            'status' => 'completed',
            'submitted_at' => now(),
        ]);

        WorkshopAssessmentUserAnswer::create(['attempt_id' => $attempt1->id, 'question_id' => $q1->id, 'selected_answer' => 'Model AI', 'is_correct' => true]);
        WorkshopAssessmentUserAnswer::create(['attempt_id' => $attempt1->id, 'question_id' => $q2->id, 'selected_answer' => 'loss.backward()', 'is_correct' => true]);

        // Struggling student fails Pre-Test 3 times
        for ($i = 1; $i <= 3; $i++) {
            $failAttempt = WorkshopAssessmentAttempt::create([
                'user_id' => $strugglingStudent->id,
                'assessment_id' => $preTest->id,
                'attempt_number' => $i,
                'total_score' => 0,
                'is_passed' => false,
                'status' => 'completed',
                'submitted_at' => now(),
            ]);

            // Both questions answered wrong by struggling student
            WorkshopAssessmentUserAnswer::create(['attempt_id' => $failAttempt->id, 'question_id' => $q1->id, 'selected_answer' => 'Sistem Operasi', 'is_correct' => false]);
            WorkshopAssessmentUserAnswer::create(['attempt_id' => $failAttempt->id, 'question_id' => $q2->id, 'selected_answer' => 'torch.no_grad()', 'is_correct' => false]);
        }

        // Fetch Analytics Dashboard via Inertia
        $response = $this->actingAs($instructor)->get(route('assessments.analytics', $course->id));
        $response->assertStatus(200);

        // Check Inertia Props
        $page = $response->inertiaPage();
        $props = $page['props'];

        // 1. Pre-Test average score calculation: (100 + 0 + 0 + 0) / 4 = 25
        $this->assertEquals(25, $props['preTest']['avg_score']);

        // 2. At-Risk Student Flagging: strugglingStudent detected
        $this->assertCount(1, $props['atRiskStudents']);
        $this->assertEquals('Siswa Kesulitan', $props['atRiskStudents'][0]['name']);
        $this->assertEquals(3, $props['atRiskStudents'][0]['failed_attempts']);

        // 3. Item Analysis: Questions with highest wrong answers
        $hardest = $props['preTest']['hardest_questions'];
        $this->assertGreaterThan(0, count($hardest));
        $this->assertEquals(3, $hardest[0]['wrong_answers_count']); // Struggling student answered wrong 3 times
    }
}
