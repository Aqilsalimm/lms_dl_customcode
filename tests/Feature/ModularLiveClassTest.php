<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Models\WorkshopAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModularLiveClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_instructor_can_add_live_session_settings_to_module()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $category = \App\Models\Category::create(['name' => 'Tech', 'slug' => 'tech']);
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Live Workshop Laravel',
            'slug' => 'live-workshop-laravel',
            'price' => 100000,
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        $response = $this->actingAs($instructor)->postJson("/course-builder/courses/{$course->id}/modules", [
            'title' => 'Sesi 1: Fundamental Laravel',
            'meeting_url' => 'https://zoom.us/j/123456789',
            'start_date' => '2026-08-01 09:00:00',
            'end_date' => '2026-08-01 11:00:00',
            'recording_url' => 'https://youtube.com/watch?v=sample',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('modules', [
            'course_id' => $course->id,
            'title' => 'Sesi 1: Fundamental Laravel',
            'meeting_url' => 'https://zoom.us/j/123456789',
        ]);
    }

    public function test_student_pre_test_and_prerequisite_locks_per_session()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $category = \App\Models\Category::create(['name' => 'Tech 2', 'slug' => 'tech-2']);

        $course = Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Live Workshop Advance',
            'slug' => 'live-workshop-advance',
            'price' => 150000,
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        $student->enrollments()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        // Module 1 with Pre-test & Post-test
        $module1 = Module::create([
            'course_id' => $course->id,
            'title' => 'Sesi 1: Intro',
            'sort_order' => 1,
            'meeting_url' => 'https://zoom.us/j/session1',
        ]);

        $preTest1 = WorkshopAssessment::create([
            'course_id' => $course->id,
            'module_id' => $module1->id,
            'type' => 'pre_test',
            'title' => 'Pre-Test Sesi 1',
            'is_published' => true,
        ]);

        $postTest1 = WorkshopAssessment::create([
            'course_id' => $course->id,
            'module_id' => $module1->id,
            'type' => 'post_test',
            'title' => 'Post-Test Sesi 1',
            'is_published' => true,
            'passing_score' => 70,
        ]);

        // Module 2
        $module2 = Module::create([
            'course_id' => $course->id,
            'title' => 'Sesi 2: Advanced',
            'sort_order' => 2,
            'meeting_url' => 'https://zoom.us/j/session2',
        ]);

        // 1. Student attempts session 1 meeting link BEFORE completing pre-test -> redirected with error
        $response = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // 2. Student completes Session 1 Pre-Test
        $preTest1->attempts()->create([
            'user_id' => $student->id,
            'score' => 80,
            'status' => 'completed',
            'is_passed' => true,
        ]);

        // 3. Now student attempts session 1 meeting link -> redirected to Zoom link
        $response = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $response->assertRedirect('https://zoom.us/j/session1');

        // 4. Student attempts session 2 meeting link BEFORE passing session 1 post-test -> locked by prerequisite
        $response = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module2->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // 5. Student passes Session 1 Post-Test
        $postTest1->attempts()->create([
            'user_id' => $student->id,
            'score' => 85,
            'status' => 'completed',
            'is_passed' => true,
        ]);

        // 6. Now student can access session 2 meeting link
        $response = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module2->id}");
        $response->assertRedirect('https://zoom.us/j/session2');
    }

    public function test_custom_question_points_weight_calculation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $category = \App\Models\Category::create(['name' => 'Tech Points', 'slug' => 'tech-points']);

        $course = Course::create([
            'instructor_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Weighted Question Test Course',
            'slug' => 'weighted-question-test-course',
            'price' => 100000,
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Sesi 1: Custom Points',
            'sort_order' => 1,
        ]);

        // Bulk store pre-test and post-test with custom points (Q1 = 30 points, Q2 = 70 points)
        $response = $this->actingAs($admin)->postJson("/course-builder/courses/{$course->id}/assessments-bulk", [
            'module_id' => $module->id,
            'assessments' => [
                [
                    'type' => 'pre_test',
                    'module_id' => $module->id,
                    'title' => 'Pre-Test Custom Points',
                    'description' => 'Test Description',
                    'duration_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 2,
                    'questions' => [
                        [
                            'question_text' => 'Soal 1 (30 poin)',
                            'options' => ['A', 'B', 'C', 'D'],
                            'correct_answer' => '0',
                            'points' => 30,
                        ],
                        [
                            'question_text' => 'Soal 2 (70 poin)',
                            'options' => ['A', 'B', 'C', 'D'],
                            'correct_answer' => '1',
                            'points' => 70,
                        ],
                    ],
                ],
                [
                    'type' => 'post_test',
                    'module_id' => $module->id,
                    'title' => 'Post-Test Custom Points',
                    'description' => 'Test Description',
                    'duration_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 2,
                    'questions' => [],
                ]
            ],
        ]);

        $response->assertRedirect();

        $preTest = WorkshopAssessment::where('module_id', $module->id)->where('type', 'pre_test')->first();
        $this->assertNotNull($preTest);
        $this->assertCount(2, $preTest->questions);
        $this->assertEquals(30, $preTest->questions[0]->points);
        $this->assertEquals(70, $preTest->questions[1]->points);

        // Student submits answers: Answers Q2 correctly (70 pts) and Q1 incorrectly (0 pts) -> Total score = 70%
        $q1 = $preTest->questions[0];
        $q2 = $preTest->questions[1];

        $student->enrollments()->create(['course_id' => $course->id, 'status' => 'active']);

        $attempt = $preTest->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $submitResponse = $this->actingAs($student)->postJson("/attempts/{$attempt->id}/submit", [
            'answers' => [
                $q1->id => 2, // Wrong (0 pts)
                $q2->id => 1, // Correct (70 pts out of 100 total pts)
            ],
        ]);

        $submitResponse->assertStatus(200);
        $submitResponse->assertJson([
            'is_passed' => true, // 70% >= 70 passing score
        ]);
        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $attempt->id,
            'user_id' => $student->id,
            'assessment_id' => $preTest->id,
            'total_score' => 70,
            'is_passed' => true,
        ]);
    }

    public function test_prerequisite_enforcement_restricted_and_non_restricted_modes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $category = \App\Models\Category::create(['name' => 'Tech Mode', 'slug' => 'tech-mode']);

        $course = Course::create([
            'instructor_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Mode Test Course',
            'slug' => 'mode-test-course',
            'price' => 100000,
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        $student->enrollments()->create(['course_id' => $course->id, 'status' => 'active']);

        $module1 = Module::create([
            'course_id' => $course->id,
            'title' => 'Sesi 1: Restriksi',
            'sort_order' => 1,
            'meeting_url' => 'https://zoom.us/j/restricted1',
        ]);

        WorkshopAssessment::create([
            'course_id' => $course->id,
            'module_id' => $module1->id,
            'type' => 'pre_test',
            'title' => 'Pre-Test Module 1',
            'is_published' => true,
        ]);

        // 1. In RESTRICTED mode (default: enforce_prerequisites = true), student is BLOCKED from live meeting URL
        \App\Models\Setting::updateOrCreate(['key' => 'test_builder_enforce_prerequisites'], ['value' => 'true']);

        $responseRestricted = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $responseRestricted->assertRedirect();
        $responseRestricted->assertSessionHas('error');

        // 2. Admin switches setting to NON-RESTRICTED mode (enforce_prerequisites = false)
        $updateSettingResp = $this->actingAs($admin)->post('/dashboard/settings/course-builder/test-builder', [
            'pre_passing_score' => 70,
            'post_passing_score' => 70,
            'default_duration' => 30,
            'default_max_attempts' => 3,
            'auto_enable' => true,
            'show_explanations' => true,
            'enforce_prerequisites' => false,
        ]);
        $updateSettingResp->assertRedirect();

        // 3. Now student accesses meeting link WITHOUT completing pre-test -> SUCCESS, redirected to Zoom URL!
        $responseBypass = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $responseBypass->assertRedirect('https://zoom.us/j/restricted1');
    }

    public function test_end_to_end_test_builder_flow_admin_and_student()
    {
        // ----------------------------------------------------
        // STEP 1: ADMIN ROLE - Configure Global Test Settings
        // ----------------------------------------------------
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_e2e@drastha.com']);

        $settingsResponse = $this->actingAs($admin)->post('/dashboard/settings/course-builder/test-builder', [
            'pre_passing_score' => 70,
            'post_passing_score' => 70,
            'default_duration' => 45,
            'default_max_attempts' => 3,
            'auto_enable' => true,
            'show_explanations' => true,
            'enforce_prerequisites' => true, // Restricted Mode ON
        ]);
        $settingsResponse->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'key' => 'test_builder_enforce_prerequisites',
            'value' => 'true',
        ]);

        // ----------------------------------------------------
        // STEP 2: ADMIN ROLE - Build Course, Modules & Weighted Tests
        // ----------------------------------------------------
        $category = \App\Models\Category::create(['name' => 'E2E Testing', 'slug' => 'e2e-testing']);
        $course = Course::create([
            'instructor_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'End to End Test Builder Course',
            'slug' => 'end-to-end-test-builder-course',
            'price' => 250000,
            'course_type' => 'live_class',
            'status' => 'published',
        ]);

        $module1 = Module::create([
            'course_id' => $course->id,
            'title' => 'Modul 1: Dasar E2E',
            'sort_order' => 1,
            'meeting_url' => 'https://zoom.us/j/e2e-session-1',
        ]);

        $module2 = Module::create([
            'course_id' => $course->id,
            'title' => 'Modul 2: Lanjutan E2E',
            'sort_order' => 2,
            'meeting_url' => 'https://zoom.us/j/e2e-session-2',
        ]);

        // Admin bulk creates tests with custom point weights for Module 1
        $bulkStoreResponse = $this->actingAs($admin)->postJson("/course-builder/courses/{$course->id}/assessments-bulk", [
            'module_id' => $module1->id,
            'assessments' => [
                [
                    'type' => 'pre_test',
                    'module_id' => $module1->id,
                    'title' => 'Pre-Test Modul 1 (Weighted)',
                    'description' => 'Evaluasi awal modul 1',
                    'duration_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 2,
                    'questions' => [
                        [
                            'question_text' => 'Soal 1 (Bobot 40)',
                            'options' => ['Jawaban A', 'Jawaban B', 'Jawaban C'],
                            'correct_answer' => '0',
                            'points' => 40,
                        ],
                        [
                            'question_text' => 'Soal 2 (Bobot 60)',
                            'options' => ['Jawaban A', 'Jawaban B', 'Jawaban C'],
                            'correct_answer' => '1',
                            'points' => 60,
                        ],
                    ],
                ],
                [
                    'type' => 'post_test',
                    'module_id' => $module1->id,
                    'title' => 'Post-Test Modul 1 (Weighted)',
                    'description' => 'Evaluasi akhir modul 1',
                    'duration_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 2,
                    'questions' => [
                        [
                            'question_text' => 'Soal Post 1 (Bobot 100)',
                            'options' => ['Benar', 'Salah'],
                            'correct_answer' => '0',
                            'points' => 100,
                        ],
                    ],
                ]
            ],
        ]);

        $bulkStoreResponse->assertRedirect();

        // Verify stored assessments and weights in DB
        $preTest1 = WorkshopAssessment::where('module_id', $module1->id)->where('type', 'pre_test')->first();
        $postTest1 = WorkshopAssessment::where('module_id', $module1->id)->where('type', 'post_test')->first();

        $this->assertNotNull($preTest1);
        $this->assertNotNull($postTest1);
        $this->assertEquals(40, $preTest1->questions[0]->points);
        $this->assertEquals(60, $preTest1->questions[1]->points);
        $this->assertEquals(100, $postTest1->questions[0]->points);

        // ----------------------------------------------------
        // STEP 3: STUDENT ROLE - Enrollment & Attempt Access (Blocked)
        // ----------------------------------------------------
        $student = User::factory()->create(['role' => 'student', 'email' => 'student_e2e@drastha.com']);
        $student->enrollments()->create(['course_id' => $course->id, 'status' => 'active']);

        // Student tries to access Modul 1 Zoom link before taking Pre-Test -> Blocked!
        $linkAccess1 = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $linkAccess1->assertRedirect();
        $linkAccess1->assertSessionHas('error');

        // ----------------------------------------------------
        // STEP 4: STUDENT ROLE - Complete Pre-Test & Verify Calculation
        // ----------------------------------------------------
        $attempt1 = $preTest1->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $q1 = $preTest1->questions[0];
        $q2 = $preTest1->questions[1];

        // Submit correct answers for both questions (40 + 60 = 100%)
        $submitPreTest = $this->actingAs($student)->postJson("/attempts/{$attempt1->id}/submit", [
            'answers' => [
                $q1->id => 0,
                $q2->id => 1,
            ],
        ]);

        $submitPreTest->assertStatus(200);
        $submitPreTest->assertJson(['is_passed' => true]);

        $this->assertDatabaseHas('workshop_assessment_attempts', [
            'id' => $attempt1->id,
            'total_score' => 100,
            'is_passed' => true,
        ]);

        // ----------------------------------------------------
        // STEP 5: STUDENT ROLE - Modul 1 Access Granted & Modul 2 Locked
        // ----------------------------------------------------
        // Now student accesses Modul 1 Zoom link -> Allowed!
        $linkAccessAllowed = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $linkAccessAllowed->assertRedirect('https://zoom.us/j/e2e-session-1');

        // Student tries to access Modul 2 Zoom link before passing Modul 1 Post-Test -> Blocked!
        $linkAccess2Blocked = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module2->id}");
        $linkAccess2Blocked->assertRedirect();
        $linkAccess2Blocked->assertSessionHas('error');

        // ----------------------------------------------------
        // STEP 6: STUDENT ROLE - Complete Post-Test & Unlock Modul 2
        // ----------------------------------------------------
        $postAttempt = $postTest1->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $qPost = $postTest1->questions[0];
        $submitPost = $this->actingAs($student)->postJson("/attempts/{$postAttempt->id}/submit", [
            'answers' => [
                $qPost->id => 0, // Correct answer (100 pts)
            ],
        ]);
        $submitPost->assertStatus(200);
        $submitPost->assertJson(['is_passed' => true]);

        // Student now accesses Modul 2 Zoom link -> Allowed!
        $linkAccess2Allowed = $this->actingAs($student)->get("/courses/{$course->id}/live-meeting-link?module_id={$module2->id}");
        $linkAccess2Allowed->assertRedirect('https://zoom.us/j/e2e-session-2');

        // ----------------------------------------------------
        // STEP 7: ADMIN ROLE - Turn Off Prerequisite Mode
        // ----------------------------------------------------
        $disableSetting = $this->actingAs($admin)->post('/dashboard/settings/course-builder/test-builder', [
            'pre_passing_score' => 70,
            'post_passing_score' => 70,
            'default_duration' => 45,
            'default_max_attempts' => 3,
            'auto_enable' => true,
            'show_explanations' => true,
            'enforce_prerequisites' => false, // Non-restricted mode
        ]);
        $disableSetting->assertRedirect();

        // ----------------------------------------------------
        // STEP 8: NEW STUDENT ROLE - Direct Access (No Prerequisites)
        // ----------------------------------------------------
        $newStudent = User::factory()->create(['role' => 'student', 'email' => 'new_student@drastha.com']);
        $newStudent->enrollments()->create(['course_id' => $course->id, 'status' => 'active']);

        // New student accesses Modul 1 & Modul 2 links without taking any test -> Direct Access Granted!
        $directModule1 = $this->actingAs($newStudent)->get("/courses/{$course->id}/live-meeting-link?module_id={$module1->id}");
        $directModule1->assertRedirect('https://zoom.us/j/e2e-session-1');

        $directModule2 = $this->actingAs($newStudent)->get("/courses/{$course->id}/live-meeting-link?module_id={$module2->id}");
        $directModule2->assertRedirect('https://zoom.us/j/e2e-session-2');
    }
}