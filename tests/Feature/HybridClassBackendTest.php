<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\ClassEnrollment;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HybridClassBackendTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $instructor;
    private Course $course;
    private LiveClass $liveClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['role' => 'instructor']);
        $this->student = User::factory()->create(['role' => 'student']);
        
        $this->course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Test Hybrid Course',
            'slug' => 'test-hybrid-course',
            'status' => 'published',
            'course_type' => 'live_class',
            'delivery_mode' => 'offline',
        ]);

        $this->liveClass = LiveClass::create([
            'course_id' => $this->course->id,
            'title' => 'Test Live Class Session',
            'mode' => 'hybrid',
            'offline_capacity' => 2,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
            'is_published' => true,
        ]);
    }

    public function test_only_enrolled_users_can_select_attendance(): void
    {
        // 1. Non-enrolled user attempts to set preference -> 403 Forbidden
        $response = $this->actingAs($this->student)
            ->post(route('live-classes.select-attendance', $this->liveClass), [
                'attendance_type' => 'online'
            ]);

        $response->assertStatus(403);

        // 2. Enroll student and try again
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $responseSuccess = $this->actingAs($this->student)
            ->post(route('live-classes.select-attendance', $this->liveClass), [
                'attendance_type' => 'online'
            ]);

        $responseSuccess->assertRedirect();
        $responseSuccess->assertSessionHas('success', 'Pilihan kehadiran Anda berhasil diperbarui.');

        $this->assertDatabaseHas('class_enrollments', [
            'user_id' => $this->student->id,
            'live_class_id' => $this->liveClass->id,
            'attendance_type' => 'online',
        ]);
    }

    public function test_cannot_select_onsite_attendance_if_class_is_online_only(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->liveClass->update(['mode' => 'online']);

        $response = $this->actingAs($this->student)
            ->post(route('live-classes.select-attendance', $this->liveClass), [
                'attendance_type' => 'onsite'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Kelas ini diadakan secara online saja.');

        $this->assertDatabaseMissing('class_enrollments', [
            'user_id' => $this->student->id,
            'live_class_id' => $this->liveClass->id,
            'attendance_type' => 'onsite',
        ]);
    }

    public function test_onsite_capacity_limit_prevents_selection(): void
    {
        // Limit is 2
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Enroll all users to the course
        foreach ([$user1, $user2, $user3] as $user) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $this->course->id,
                'status' => 'active',
            ]);
        }

        // User 1 chooses onsite -> succeeds
        $this->actingAs($user1)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite'])
            ->assertSessionHas('success');

        // User 2 chooses onsite -> succeeds
        $this->actingAs($user2)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite'])
            ->assertSessionHas('success');

        // User 3 chooses onsite -> fails due to capacity (2 max)
        $response = $this->actingAs($user3)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite']);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Kapasitas onsite kelas ini sudah penuh.');
    }

    public function test_cannot_change_attendance_within_24_hours(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Move session start_time to 12 hours from now
        $this->liveClass->update([
            'start_time' => now()->addHours(12)
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('live-classes.select-attendance', $this->liveClass), [
                'attendance_type' => 'online'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Perubahan tipe kehadiran hanya dapat dilakukan paling lambat H-1 sebelum acara dimulai.');
    }

    public function test_change_attendance_frees_up_capacity(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        foreach ([$user1, $user2, $user3] as $user) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $this->course->id,
                'status' => 'active',
            ]);
        }

        // Fill capacity (limit 2)
        $this->actingAs($user1)->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite']);
        $this->actingAs($user2)->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite']);

        // User 3 onsite fails
        $this->actingAs($user3)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite'])
            ->assertSessionHas('error');

        // User 1 switches back to online
        $this->actingAs($user1)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'online'])
            ->assertSessionHas('success');

        // User 3 onsite now succeeds
        $this->actingAs($user3)
            ->post(route('live-classes.select-attendance', $this->liveClass), ['attendance_type' => 'onsite'])
            ->assertSessionHas('success');
    }

    public function test_live_class_room_query_count_is_o1(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        // Prime session / auth middleware cache
        $this->actingAs($this->student)->get(route('live-class.show', $this->course));

        // Request with 1 live class session
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response1 = $this->actingAs($this->student)
            ->get(route('live-class.show', $this->course));
        $response1->assertStatus(200);

        $queryCount1 = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // Create 10 additional live class sessions
        for ($i = 0; $i < 10; $i++) {
            LiveClass::create([
                'course_id' => $this->course->id,
                'title' => "Session {$i}",
                'mode' => 'hybrid',
                'offline_capacity' => 50,
                'start_time' => now()->addDays(2),
                'is_published' => true,
            ]);
        }

        \Illuminate\Support\Facades\DB::flushQueryLog();

        $response2 = $this->actingAs($this->student)
            ->get(route('live-class.show', $this->course));
        $response2->assertStatus(200);

        $queryCount2 = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // Query count for N=1 vs N=11 sessions must be equal -> strictly O(1) constant
        $this->assertEquals($queryCount1, $queryCount2, "Live Class Room query count must be strictly O(1) constant regardless of N sessions.");
    }
}
