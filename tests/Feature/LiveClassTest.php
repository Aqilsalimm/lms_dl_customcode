<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\LiveClassReminderMail;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LiveClassTest extends TestCase
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
     * Test instructor can access the live class schedule page.
     */
    public function test_instructor_can_access_live_class_schedule_page()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)
            ->get(route('dashboard.live-class'));

        $response->assertStatus(200);
    }

    /**
     * Test instructor can update live class schedule and synchronize about JSON.
     */
    public function test_instructor_can_update_live_class_schedule()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Test Live Class',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
        ]);

        $startDate = now()->addHours(10)->format('Y-m-d H:i:s');
        $endDate = now()->addHours(12)->format('Y-m-d H:i:s');

        $response = $this->actingAs($instructor)
            ->post(route('dashboard.live-class.update-schedule', $course->id), [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'timezone' => 'Asia/Jakarta',
                'meeting_url' => 'https://zoom.us/j/1234567890?pwd=xyz',
                'recording_url' => 'https://drive.google.com/test',
                'max_participants' => 50,
                'is_event_finished' => false,
                'platform_type' => 'zoom',
            ]);

        $response->assertRedirect();
        
        $course->refresh();
        $this->assertEquals('https://zoom.us/j/1234567890?pwd=xyz', $course->meeting_url);
        $this->assertEquals(50, $course->max_participants);
        $this->assertEquals('Asia/Jakarta', $course->timezone);

        // Verify JSON about
        $about = json_decode($course->about, true);
        $this->assertNotNull($about);
        $this->assertEquals('https://zoom.us/j/1234567890?pwd=xyz', $about['live_zoom_link']);
        $this->assertFalse($about['live_class_reminder_sent']);
    }

    /**
     * Test the SendLiveClassReminder command.
     */
    public function test_send_live_class_reminder_command()
    {
        Mail::fake();

        $instructor = User::factory()->create(['role' => 'instructor']);
        
        // Course 1: Starting in 12 hours (should send reminder)
        $course1 = Course::create([
            'title' => 'Upcoming Live Class',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
            'start_date' => now()->addHours(12),
        ]);

        // Course 2: Starting in 36 hours (should NOT send reminder)
        $course2 = Course::create([
            'title' => 'Far Future Live Class',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'draft',
            'start_date' => now()->addHours(36),
        ]);

        // Run command
        Artisan::call('liveclass:remind');

        // Assert mail sent for course 1
        Mail::assertSent(LiveClassReminderMail::class, function ($mail) use ($course1, $instructor) {
            return $mail->course->id === $course1->id && $mail->hasTo($instructor->email);
        });

        // Assert mail not sent for course 2
        Mail::assertNotSent(LiveClassReminderMail::class, function ($mail) use ($course2) {
            return $mail->course->id === $course2->id;
        });

        // Verify course 1 flag updated
        $course1->refresh();
        $about1 = json_decode($course1->about, true);
        $this->assertTrue($about1['live_class_reminder_sent']);
    }

    /**
     * Test guest cannot access the live class room.
     */
    public function test_guest_cannot_access_live_class_room()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->get(route('live-class.show', $course->id));
        $response->assertRedirect();
    }

    /**
     * Test student blocked from live class room if pre-test not completed.
     */
    public function test_student_blocked_from_live_class_room_if_pre_test_incomplete()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
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
            ->get(route('live-class.show', $course->id));

        $response->assertRedirect(route('courses.learn', $course->slug));
        $response->assertSessionHas('error', 'Anda harus lulus Pre-test terlebih dahulu untuk mengakses sesi Live.');
    }

    /**
     * Test student allowed to enter live class room if pre-test is completed and passed.
     */
    public function test_student_allowed_live_class_room_if_pre_test_completed()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
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
            ->get(route('live-class.show', $course->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('LiveClass/Room')
            ->has('course')
            ->where('zoom_link', 'https://zoom.us/j/123456')
        );
    }

    /**
     * Test admin/instructor bypass the pre-test lock to live class room.
     */
    public function test_admin_and_instructor_bypass_live_class_room_pre_test_lock()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
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
            ->get(route('live-class.show', $course->id));

        $response->assertStatus(200);

        // Admin bypass
        $response2 = $this->actingAs($admin)
            ->get(route('live-class.show', $course->id));

        $response2->assertStatus(200);
    }

    /**
     * Test storing online live class sanitizes location_venue to null.
     */
    public function test_store_online_live_class_sanitizes_location_venue_to_null()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Online Live Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($instructor)
            ->post(route('live-classes.store'), [
                'title' => 'Sesi 1 Online Zoom',
                'course_id' => $course->id,
                'delivery_mode' => 'online',
                'meeting_link' => 'https://zoom.us/j/987654321',
                'location_venue' => 'Address should be cleared for online mode',
                'recording_url' => 'https://youtube.com/watch?v=sample',
                'documentation_urls' => ['https://drive.google.com/file1'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Kelas berhasil dibuat.');

        $this->assertDatabaseHas('live_classes', [
            'title' => 'Sesi 1 Online Zoom',
            'delivery_mode' => 'online',
            'meeting_link' => 'https://zoom.us/j/987654321',
            'location_venue' => null,
            'recording_url' => 'https://youtube.com/watch?v=sample',
        ]);
    }

    /**
     * Test storing offline live class forces meeting_link to null.
     */
    public function test_store_offline_live_class_forces_meeting_link_to_null()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'title' => 'Offline Workshop',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($instructor)
            ->post(route('live-classes.store'), [
                'title' => 'Sesi Tatap Muka Lab Komputer',
                'course_id' => $course->id,
                'delivery_mode' => 'offline',
                'meeting_link' => 'https://zoom.us/j/should-be-null',
                'location_venue' => 'Gedung Utama Lt. 3, Ruang Lab Komputer 2',
                'recording_url' => 'https://youtube.com/watch?v=offline-rec',
                'documentation_urls' => ['https://drive.google.com/photo1', 'https://drive.google.com/photo2'],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('live_classes', [
            'title' => 'Sesi Tatap Muka Lab Komputer',
            'delivery_mode' => 'offline',
            'meeting_link' => null,
            'location_venue' => 'Gedung Utama Lt. 3, Ruang Lab Komputer 2',
        ]);
    }

    /**
     * Test LiveClass model accessors and casts.
     */
    public function test_live_class_model_accessors_and_casts()
    {
        $liveClass = \App\Models\LiveClass::create([
            'title' => 'Hybrid Masterclass',
            'delivery_mode' => 'offline',
            'location_venue' => 'Auditorium Kampus A',
            'recording_url' => 'https://vimeo.com/123456',
            'documentation_urls' => ['https://drive.google.com/album1'],
        ]);

        $this->assertTrue($liveClass->is_offline);
        $this->assertTrue($liveClass->has_recording);
        $this->assertTrue($liveClass->has_documentation);
        $this->assertIsArray($liveClass->documentation_urls);
        $this->assertCount(1, $liveClass->documentation_urls);
    }
}
