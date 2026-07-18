<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class WorkshopAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    /**
     * Test guest cannot access the Zoom live link.
     */
    public function test_guest_cannot_access_live_link(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
            'meeting_url' => 'https://zoom.us/j/123456',
        ]);

        $response = $this->get(route('courses.live-link', $course->id));
        $response->assertRedirect();
    }

    /**
     * Test enrolled student is blocked from Live Zoom link if Pre-Test is incomplete.
     */
    public function test_student_blocked_from_live_link_if_pre_test_incomplete(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
            'meeting_url' => 'https://zoom.us/j/123456',
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Wajib Pre-test',
            'passing_score' => 60,
            'is_published' => true,
        ]);

        $response = $this->actingAs($student)
            ->get(route('courses.live-link', $course->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Anda wajib menyelesaikan Pre-test terlebih dahulu untuk mengakses tautan Zoom/pertemuan live.');
    }

    /**
     * Test enrolled student is allowed to access Live Zoom link if Pre-Test is completed.
     */
    public function test_student_allowed_live_link_if_pre_test_completed(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
            'meeting_url' => 'https://zoom.us/j/123456',
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Wajib Pre-test',
            'passing_score' => 60,
            'is_published' => true,
        ]);

        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 80.00,
            'is_passed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('courses.live-link', $course->id));

        $response->assertRedirect('https://zoom.us/j/123456');
    }

    /**
     * Test Admin and Instructor bypass the pre-test lock.
     */
    public function test_admin_and_instructor_bypass_pre_test_lock(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
            'meeting_url' => 'https://zoom.us/j/123456',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Wajib Pre-test',
            'passing_score' => 60,
            'is_published' => true,
        ]);

        // Instructor bypass
        $response = $this->actingAs($instructor)
            ->get(route('courses.live-link', $course->id));
        $response->assertRedirect('https://zoom.us/j/123456');

        // Admin bypass
        $response2 = $this->actingAs($admin)
            ->get(route('courses.live-link', $course->id));
        $response2->assertRedirect('https://zoom.us/j/123456');
    }

    /**
     * Test instructor can manage assessments and questions through builder routes.
     */
    public function test_instructor_can_manage_assessments_and_questions(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $this->actingAs($instructor);

        // 1. Create/Update Assessment
        $response = $this->post(route('course-builder.assessments.store', $course->id), [
            'type' => 'pre_test',
            'title' => 'Ujian Pre-Test',
            'description' => 'Kerjakan dengan jujur',
            'duration_minutes' => 30,
            'passing_score' => 70,
            'is_published' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('workshop_assessments', [
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Ujian Pre-Test',
            'passing_score' => 70,
        ]);

        $assessmentId = $response->json('assessment.id');

        // 2. Add Question
        $responseQ = $this->post(route('course-builder.assessments.questions.store', $assessmentId), [
            'question_text' => 'Berapa hasil dari 5 + 5?',
            'options' => ['8', '10', '12', '15'],
            'correct_answer' => '10',
            'points' => 20,
            'order_number' => 1,
        ]);

        $responseQ->assertStatus(200);
        $this->assertDatabaseHas('workshop_assessment_questions', [
            'assessment_id' => $assessmentId,
            'question_text' => 'Berapa hasil dari 5 + 5?',
            'correct_answer' => '10',
            'points' => 20,
        ]);

        $questionId = $responseQ->json('question.id');

        // 3. Update Question
        $responseU = $this->put(route('course-builder.assessments.questions.update', $questionId), [
            'question_text' => 'Berapa hasil 5 + 5? (Updated)',
            'options' => ['8', '10', '12'],
            'correct_answer' => '10',
            'points' => 25,
            'order_number' => 1,
        ]);

        $responseU->assertStatus(200);
        $this->assertDatabaseHas('workshop_assessment_questions', [
            'id' => $questionId,
            'question_text' => 'Berapa hasil 5 + 5? (Updated)',
            'points' => 25,
        ]);

        // 4. Delete Question
        $responseD = $this->delete(route('course-builder.assessments.questions.destroy', $questionId));
        $responseD->assertStatus(200);
        $this->assertDatabaseMissing('workshop_assessment_questions', [
            'id' => $questionId,
        ]);
    }

    /**
     * Test non-instructor/students cannot access builder endpoints.
     */
    public function test_non_instructor_blocked_from_builder_endpoints(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($student)
            ->post(route('course-builder.assessments.store', $course->id), [
                'type' => 'pre_test',
                'title' => 'Malicious Pre-Test',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test student taking assessment and submitting with automatic score calculation.
     */
    public function test_student_can_take_and_submit_assessment(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'passing_score' => 60,
            'is_published' => true,
        ]);

        $q1 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Pertanyaan 1',
            'options' => ['A', 'B', 'C'],
            'correct_answer' => 'A',
            'points' => 10,
        ]);

        $q2 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'Pertanyaan 2',
            'options' => ['A', 'B', 'C'],
            'correct_answer' => 'B',
            'points' => 10,
        ]);

        $this->actingAs($student);

        // 1. Start attempt
        $responseStart = $this->post(route('assessments.start', $assessment->id));
        $responseStart->assertStatus(200);
        $attemptId = $responseStart->json('attempt.id');

        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $attemptId,
            'status' => 'in_progress',
        ]);

        // 2. Submit attempt (1 correct, 1 incorrect)
        $responseSubmit = $this->post(route('attempts.submit', $attemptId), [
            'answers' => [
                $q1->id => 'A', // Correct
                $q2->id => 'C', // Incorrect
            ]
        ]);

        $responseSubmit->assertStatus(200);
        $responseSubmit->assertJsonFragment([
            'score' => 50, // 10 out of 20 points
            'is_passed' => false, // 50 < passing score 60
        ]);

        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $attemptId,
            'status' => 'completed',
            'total_score' => 50,
            'is_passed' => false,
        ]);

        $this->assertDatabaseHas('workshop_assessment_user_answers', [
            'attempt_id' => $attemptId,
            'question_id' => $q1->id,
            'selected_answer' => 'A',
            'is_correct' => true,
        ]);
    }

    /**
     * Test attempt fails outside specified time windows.
     */
    public function test_attempt_blocked_outside_time_window(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        // Closed test (end_time in the past)
        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'is_published' => true,
            'start_time' => Carbon::now()->subDays(2),
            'end_time' => Carbon::now()->subDay(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assessments.start', $assessment->id));

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Tes ini sudah ditutup.']);
    }

    /**
     * Test student cannot retake if they have already passed in a previous attempt.
     */
    public function test_student_blocked_from_retaking_if_already_passed(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'passing_score' => 60,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        // Passed attempt
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 80,
            'is_passed' => true,
            'attempt_number' => 1,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assessments.start', $assessment->id));

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Anda sudah lulus tes ini dan tidak perlu mengulangnya.']);
    }

    /**
     * Test student is blocked from starting a new attempt if the maximum attempts are exceeded.
     */
    public function test_student_blocked_from_retaking_if_max_attempts_exceeded(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'passing_score' => 60,
            'max_attempts' => 2,
            'is_published' => true,
        ]);

        // 2 failed attempts
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 40,
            'is_passed' => false,
            'attempt_number' => 1,
            'completed_at' => now(),
        ]);

        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 50,
            'is_passed' => false,
            'attempt_number' => 2,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assessments.start', $assessment->id));

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Batas maksimal percobaan pengerjaan tes ini sudah habis.']);
    }

    /**
     * Test student can retake if maximum attempts are not exceeded.
     */
    public function test_student_can_retake_if_max_attempts_not_exceeded(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'passing_score' => 60,
            'max_attempts' => 2,
            'is_published' => true,
        ]);

        // 1 failed attempt
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 40,
            'is_passed' => false,
            'attempt_number' => 1,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assessments.start', $assessment->id));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Attempt started successfully',
        ]);
        $this->assertEquals(2, $response->json('attempt.attempt_number'));
    }

    /**
     * Test student can retake unlimited times if max_attempts is set to 0.
     */
    public function test_student_can_retake_unlimited_times_if_max_attempts_is_zero(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Live Class',
            'course_type' => 'live_class',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $assessment = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-test Fisika',
            'passing_score' => 60,
            'max_attempts' => 0, // unlimited
            'is_published' => true,
        ]);

        // 2 failed attempts
        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 40,
            'is_passed' => false,
            'attempt_number' => 1,
            'completed_at' => now(),
        ]);

        WorkshopAssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'total_score' => 50,
            'is_passed' => false,
            'attempt_number' => 2,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->post(route('assessments.start', $assessment->id));

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('attempt.attempt_number'));
    }
}
