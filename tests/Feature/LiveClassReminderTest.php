<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\LiveClassReminder;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class LiveClassReminderTest extends \Tests\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    public function test_reminder_command_dispatches_queued_notification_for_students()
    {
        Notification::fake();

        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Test Live Class',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
            'start_date' => now()->addMinutes(30),
            'about' => json_encode(['live_class_reminder_sent' => true]),
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->artisan('liveclass:remind')
            ->assertExitCode(0);

        Notification::assertSentTo(
            [$student],
            LiveClassReminder::class
        );
    }

    public function test_push_subscription_store_and_destroy_endpoints()
    {
        $user = User::factory()->create();

        $responseKey = $this->actingAs($user)->get(route('push.key'));
        $responseKey->assertStatus(200);

        $responseStore = $this->actingAs($user)->postJson(route('push.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token',
            'keys' => [
                'auth' => 'test-auth-token',
                'p256dh' => 'test-p256dh-key',
            ],
        ]);
        $responseStore->assertStatus(200);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token',
        ]);

        $responseDestroy = $this->actingAs($user)->deleteJson(route('push.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token',
        ]);
        $responseDestroy->assertStatus(200);

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-token',
        ]);
    }

    public function test_live_class_reminder_to_web_push_payload()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Flutter Live Masterclass',
            'instructor_id' => $instructor->id,
            'course_type' => 'live_class',
            'price' => 200000,
            'level' => 'Umum',
            'status' => 'published',
            'start_date' => now()->addMinutes(45),
        ]);

        $notification = new LiveClassReminder($course);
        $webPushMessage = $notification->toWebPush($student, null);

        $this->assertInstanceOf(\NotificationChannels\WebPush\WebPushMessage::class, $webPushMessage);
        
        $data = $webPushMessage->toArray();
        $this->assertStringContainsString('Flutter Live Masterclass', $data['title']);
        $this->assertEquals(route('live-class.show', $course->id), $data['data']['url']);
    }
}
