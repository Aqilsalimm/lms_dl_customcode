<?php

namespace Tests\Feature;

use App\Events\ExamAttemptSubmitted;
use App\Models\Course;
use App\Models\User;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentUserAnswer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExamReportTest extends TestCase
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

    public function test_admin_and_instructor_can_access_exam_report_dashboard_student_denied()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        // Admin Access
        $response = $this->actingAs($admin)->get(route('dashboard.reports.exam'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard/Reports/ExamReport'));

        // Instructor Access
        $responseInst = $this->actingAs($instructor)->get(route('dashboard.reports.exam'));
        $responseInst->assertStatus(200);

        // Student Access -> Redirected to dashboard with error
        $responseStudent = $this->actingAs($student)->get(route('dashboard.reports.exam'));
        $responseStudent->assertRedirect(route('dashboard'));
    }

    public function test_exam_report_calculates_kpi_metrics_and_detects_anomalies()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student1 = User::factory()->create(['role' => 'student', 'name' => 'Alice Student']);
        $student2 = User::factory()->create(['role' => 'student', 'name' => 'Bob Student']);

        $course = Course::create([
            'title' => 'Laravel Masterclass',
            'slug' => 'laravel-masterclass',
            'instructor_id' => $instructor->id,
            'course_type' => 'workshop',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'title' => 'Post-Test Assessment',
            'type' => 'post_test',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        // Attempt 1: Normal Passed (Score 80%)
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student1->id,
            'status' => 'completed',
            'total_score' => 80.0,
            'is_passed' => true,
            'attempt_number' => 1,
            'started_at' => Carbon::now()->subMinutes(10),
            'completed_at' => Carbon::now()->subMinutes(2),
        ]);

        // Attempt 2: Rapid & Low Score Anomaly (Completed in 15 seconds, Score 30%)
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student2->id,
            'status' => 'completed',
            'total_score' => 30.0,
            'is_passed' => false,
            'attempt_number' => 1,
            'started_at' => Carbon::now()->subSeconds(15),
            'completed_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.reports.exam'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Reports/ExamReport')
            ->where('kpiMetrics.total_volume', 2)
            ->where('kpiMetrics.pass_rate', 50)
            ->where('kpiMetrics.avg_score', 55)
            ->where('kpiMetrics.flagged_count', 1)
            ->has('anomalyFlags', 1)
        );
    }

    public function test_submitting_assessment_attempt_dispatches_realtime_broadcast_event()
    {
        Event::fake([ExamAttemptSubmitted::class]);

        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Vue.js Realtime Workshop',
            'slug' => 'vue-realtime-workshop',
            'instructor_id' => $instructor->id,
            'course_type' => 'workshop',
            'price' => 200000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'title' => 'Pre-Test Assessment',
            'type' => 'pre_test',
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $question = WorkshopAssessmentQuestion::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Berapa versi terbaru Vue.js?',
            'options' => ['v2', 'v3', 'v4'],
            'correct_answer' => 'v3',
            'points' => 10,
        ]);

        $attempt = WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'in_progress',
            'attempt_number' => 1,
            'started_at' => Carbon::now()->subMinutes(5),
        ]);

        $submitUrl = route('attempts.submit', $attempt->id);

        $response = $this->actingAs($student)->postJson($submitUrl, [
            'answers' => [
                $question->id => 'v3',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Assessment submitted successfully',
            'is_passed' => true,
            'score' => 100,
        ]);

        // Assert Real-time Broadcast Event Dispatched
        Event::assertDispatched(ExamAttemptSubmitted::class, function ($event) use ($attempt) {
            return $event->attemptId === $attempt->id &&
                   $event->isPassed === true &&
                   $event->score === 100.0;
        });
    }
}
