<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CourseStudentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid enrollment lifecycle notifications hitting external channels during tests.
        Notification::fake();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    private function makeCourse(User $instructor): Course
    {
        return Course::create([
            'title' => 'Sample Course',
            'instructor_id' => $instructor->id,
            'price' => 100000,
            'level' => 'Umum',
            'status' => 'published',
        ]);
    }

    private function enroll(Course $course, string $name, string $email, string $status = 'active'): Enrollment
    {
        $student = User::factory()->create(['name' => $name, 'email' => $email]);

        return Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
        ]);
    }

    public function test_instructor_can_view_students_of_their_own_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->makeCourse($instructor);
        $this->enroll($course, 'Andi Student', 'andi@example.com');
        $this->enroll($course, 'Budi Student', 'budi@example.com');

        $response = $this->actingAs($instructor)
            ->get(route('course-builder.students', $course->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Instructor/CourseStudents')
            ->has('enrollments.data', 2)
            ->where('course.id', $course->id)
        );
    }

    public function test_instructor_cannot_view_students_of_another_instructors_course()
    {
        $owner = User::factory()->create(['role' => 'instructor']);
        $intruder = User::factory()->create(['role' => 'instructor']);
        $course = $this->makeCourse($owner);
        $this->enroll($course, 'Andi Student', 'andi@example.com');

        $response = $this->actingAs($intruder)
            ->get(route('course-builder.students', $course->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_students_of_any_course()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->makeCourse($instructor);
        $this->enroll($course, 'Andi Student', 'andi@example.com');

        $response = $this->actingAs($admin)
            ->get(route('course-builder.students', $course->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Instructor/CourseStudents')
            ->has('enrollments.data', 1)
        );
    }

    public function test_students_can_be_filtered_by_status()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->makeCourse($instructor);
        $this->enroll($course, 'Active One', 'active@example.com', 'active');
        $this->enroll($course, 'Completed One', 'completed@example.com', 'completed');
        $this->enroll($course, 'Expired One', 'expired@example.com', 'expired');

        $response = $this->actingAs($instructor)
            ->get(route('course-builder.students', ['course' => $course->id, 'status' => 'completed']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('enrollments.data', 1)
            ->where('enrollments.data.0.status', 'completed')
        );
    }

    public function test_students_can_be_searched_by_name_and_email()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->makeCourse($instructor);
        $this->enroll($course, 'Andi Wijaya', 'andi@example.com');
        $this->enroll($course, 'Budi Santoso', 'budi@example.com');

        // by name
        $this->actingAs($instructor)
            ->get(route('course-builder.students', ['course' => $course->id, 'search' => 'Andi']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('enrollments.data', 1)
                ->where('enrollments.data.0.user.name', 'Andi Wijaya')
            );

        // by email
        $this->actingAs($instructor)
            ->get(route('course-builder.students', ['course' => $course->id, 'search' => 'budi@example.com']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('enrollments.data', 1)
                ->where('enrollments.data.0.user.email', 'budi@example.com')
            );
    }

    public function test_invalid_status_filter_is_rejected()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = $this->makeCourse($instructor);

        $response = $this->actingAs($instructor)
            ->get(route('course-builder.students', ['course' => $course->id, 'status' => 'hacker']));

        $response->assertSessionHasErrors('status');
    }

    public function test_student_listing_does_not_trigger_n_plus_one_queries()
    {
        // The robust N+1 signal is that query count stays flat as row count grows,
        // not an absolute threshold (which is polluted by auth/session/count queries).
        $countQueriesFor = function (int $studentCount): int {
            $instructor = User::factory()->create(['role' => 'instructor']);
            $course = $this->makeCourse($instructor);
            for ($i = 0; $i < $studentCount; $i++) {
                $this->enroll($course, "Student {$i}", "s{$i}_{$instructor->id}@example.com");
            }

            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->actingAs($instructor)
                ->get(route('course-builder.students', $course->id))
                ->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $withFew = $countQueriesFor(3);
        $withMany = $countQueriesFor(30);

        // Eager loading loads all users in one query, so a 10x increase in students
        // must not meaningfully increase query count. A per-row N+1 would add ~27 queries.
        $this->assertLessThanOrEqual(
            $withFew + 2,
            $withMany,
            "Query count scaled with row count ({$withFew} -> {$withMany}); likely an N+1."
        );
    }
}
