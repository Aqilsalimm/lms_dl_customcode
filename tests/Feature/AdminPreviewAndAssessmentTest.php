<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPreviewAndAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_dev_admin_can_preview_draft_course_detail_by_slug_and_id()
    {
        // 1. Create Dev Admin User
        $admin = User::firstOrCreate([
            'email' => 'admin@drastha.com'
        ], [
            'name' => 'Admin Drastha',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Draft Live Class Course
        $draftCourse = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Live Workshop Masterclass',
            'slug' => 'live-workshop-masterclass-draft',
            'price' => 250000,
            'level' => 'Umum',
            'course_type' => 'live_class',
            'status' => 'draft',
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(5),
            'meeting_url' => 'https://zoom.us/j/123456789',
        ]);

        // 3. Admin previews course using slug
        $responseSlug = $this->actingAs($admin)->get("/courses/{$draftCourse->slug}?preview=true");
        $responseSlug->assertStatus(200);

        // 4. Admin previews course using numeric ID
        $responseId = $this->actingAs($admin)->get("/courses/{$draftCourse->id}?preview=true");
        $responseId->assertStatus(200);
    }

    public function test_dev_admin_can_run_pre_test_and_post_test_flow()
    {
        // 1. Create Dev Admin User
        $admin = User::firstOrCreate([
            'email' => 'admin@drastha.com'
        ], [
            'name' => 'Admin Drastha',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Live Class Course
        $course = Course::create([
            'instructor_id' => $admin->id,
            'title' => 'Python Live Workshop',
            'slug' => 'python-live-workshop',
            'price' => 150000,
            'level' => 'Umum',
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        // 3. Create Pre-Test Assessment
        $preTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'pre_test',
            'title' => 'Pre-Test Evaluasi Awal',
            'duration_minutes' => 15,
            'passing_score' => 70,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        $q1 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $preTest->id,
            'question_text' => 'Apa output dari print(2 + 2)?',
            'options' => ['3', '4', '5', '6'],
            'correct_answer' => '4',
            'points' => 10,
            'order_number' => 1,
        ]);

        // 4. Create Post-Test Assessment
        $postTest = WorkshopAssessment::create([
            'course_id' => $course->id,
            'type' => 'post_test',
            'title' => 'Post-Test Ujian Akhir',
            'duration_minutes' => 30,
            'passing_score' => 80,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        $q2 = WorkshopAssessmentQuestion::create([
            'assessment_id' => $postTest->id,
            'question_text' => 'Fungsi mana yang digunakan untuk membuat list baru?',
            'options' => ['dict()', 'list()', 'set()', 'tuple()'],
            'correct_answer' => 'list()',
            'points' => 10,
            'order_number' => 1,
        ]);

        // 5. Admin starts Pre-Test attempt
        $startPreResponse = $this->actingAs($admin)->post(route('assessments.start', $preTest->id));
        $startPreResponse->assertStatus(200);
        $attemptId = $startPreResponse->json('attempt.id');
        $this->assertNotNull($attemptId);

        // 6. Admin submits Pre-Test with correct answer
        $submitPreResponse = $this->actingAs($admin)->post(route('attempts.submit', $attemptId), [
            'answers' => [
                $q1->id => '4'
            ]
        ]);
        $submitPreResponse->assertStatus(200);
        $this->assertEquals(100, $submitPreResponse->json('score'));
        $this->assertTrue($submitPreResponse->json('is_passed'));

        // 7. Admin starts Post-Test attempt
        $startPostResponse = $this->actingAs($admin)->post(route('assessments.start', $postTest->id));
        $startPostResponse->assertStatus(200);
        $postAttemptId = $startPostResponse->json('attempt.id');
        $this->assertNotNull($postAttemptId);

        // 8. Admin submits Post-Test with correct answer
        $submitPostResponse = $this->actingAs($admin)->post(route('attempts.submit', $postAttemptId), [
            'answers' => [
                $q2->id => 'list()'
            ]
        ]);
        $submitPostResponse->assertStatus(200);
        $this->assertEquals(100, $submitPostResponse->json('score'));
        $this->assertTrue($submitPostResponse->json('is_passed'));
    }
}
