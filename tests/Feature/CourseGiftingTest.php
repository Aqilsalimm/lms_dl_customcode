<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;
use App\Mail\CourseGiftedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CourseGiftingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LicenseService validation globally to allow access to premium routes
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    /**
     * Test admin can gift any course.
     */
    public function test_admin_can_gift_any_course()
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Student Recipient']);

        $course = Course::create([
            'title' => 'Sample Gifting Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'async',
            'payment_type' => 'one-time',
            'price' => 150005,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('dashboard.courses.gift', $course->id), [
                'student_id' => $student->id,
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'is_gifted' => true,
            'gifted_by' => 'Admin User',
            'next_billing_date' => null,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        Mail::assertSent(CourseGiftedMail::class, function ($mail) use ($student, $course) {
            return $mail->hasTo($student->email) && $mail->course->id === $course->id;
        });
    }

    /**
     * Test instructor can gift their own course.
     */
    public function test_instructor_can_gift_own_course()
    {
        Mail::fake();

        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor Joe']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Joe\'s Async Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'async',
            'payment_type' => 'monthly',
            'price' => 50000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($instructor)
            ->post(route('dashboard.courses.gift', $course->id), [
                'student_id' => $student->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'is_gifted' => true,
            'gifted_by' => 'Instructor Joe',
        ]);

        // Subscription next billing date should be set (next month)
        $subscription = Subscription::where('user_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($subscription->next_billing_date);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        Mail::assertSent(CourseGiftedMail::class);
    }

    /**
     * Test instructor cannot gift other instructor's course.
     */
    public function test_instructor_cannot_gift_other_instructor_course()
    {
        $instructor1 = User::factory()->create(['role' => 'instructor']);
        $instructor2 = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);

        $course = Course::create([
            'title' => 'Instructor 1 Course',
            'instructor_id' => $instructor1->id,
            'course_type' => 'async',
            'payment_type' => 'one-time',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($instructor2)
            ->post(route('dashboard.courses.gift', $course->id), [
                'student_id' => $student->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test student cannot gift a course.
     */
    public function test_student_cannot_gift_course()
    {
        $studentGifter = User::factory()->create(['role' => 'student']);
        $studentRecipient = User::factory()->create(['role' => 'student']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Free Class for Gifting',
            'instructor_id' => $instructor->id,
            'course_type' => 'async',
            'payment_type' => 'one-time',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $response = $this->actingAs($studentGifter)
            ->post(route('dashboard.courses.gift', $course->id), [
                'student_id' => $studentRecipient->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test cannot gift a course if student is already enrolled.
     */
    public function test_cannot_gift_course_if_student_already_enrolled()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $course = Course::create([
            'title' => 'Sample Gifting Course',
            'instructor_id' => $instructor->id,
            'course_type' => 'async',
            'payment_type' => 'one-time',
            'price' => 150000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        // Already enrolled
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('dashboard.courses.gift', $course->id), [
                'student_id' => $student->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        // Subscription should not have been created
        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    /**
     * Test student search endpoint.
     */
    public function test_search_students_returns_matching_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $match1 = User::factory()->create(['role' => 'student', 'name' => 'Alice Margatroid', 'email' => 'alice@example.com']);
        $match2 = User::factory()->create(['role' => 'student', 'name' => 'Bob Smith', 'email' => 'bob@example.com']);
        $noMatch = User::factory()->create(['role' => 'student', 'name' => 'Charlie', 'email' => 'charlie@example.com']);
        $instructorMatch = User::factory()->create(['role' => 'instructor', 'name' => 'Alice Instructor', 'email' => 'alice.inst@example.com']);

        $response = $this->actingAs($admin)
            ->get(route('dashboard.students.search', ['q' => 'Alice']));

        $response->assertStatus(200);
        
        $data = $response->json();
        
        // Should only return Alice student, not Alice instructor, Bob, or Charlie
        $this->assertCount(1, $data);
        $this->assertEquals($match1->id, $data[0]['id']);
    }
}
