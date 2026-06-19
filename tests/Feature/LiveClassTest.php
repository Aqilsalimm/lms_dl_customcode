<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\LiveClassReminderMail;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LiveClassTest extends TestCase
{
    use RefreshDatabase;

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
}
