<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ZiggySecurityTest extends TestCase
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

    public function test_guest_only_receives_public_routes()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Assert that the page includes the 'public' Ziggy configuration
        $response->assertSee('api.gemini.chat');
        $response->assertSee('courses.show');

        // Assert that the page DOES NOT include student/instructor/admin-only routes
        $response->assertDontSee('dashboard.enrolled-courses');
        $response->assertDontSee('course-builder.index');
        $response->assertDontSee('dashboard.users.manage');
    }

    public function test_student_only_receives_student_routes()
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/');
        $response->assertStatus(200);

        // Assert that student sees student and public routes
        $response->assertSee('courses.show');
        $response->assertSee('dashboard.enrolled-courses');

        // Assert that student DOES NOT see instructor/admin routes
        $response->assertDontSee('course-builder.index');
        $response->assertDontSee('dashboard.users.manage');
    }

    public function test_instructor_only_receives_instructor_routes()
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor)->get('/');
        $response->assertStatus(200);

        // Assert that instructor sees instructor, student, and public routes
        $response->assertSee('courses.show');
        $response->assertSee('dashboard.enrolled-courses');
        $response->assertSee('course-builder.index');

        // Assert that instructor DOES NOT see admin routes
        $response->assertDontSee('dashboard.users.manage');
    }

    public function test_admin_receives_all_routes()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/');
        $response->assertStatus(200);

        // Assert that admin sees all route categories
        $response->assertSee('courses.show');
        $response->assertSee('dashboard.enrolled-courses');
        $response->assertSee('course-builder.index');
        $response->assertSee('dashboard.users.manage');
    }

    public function test_security_headers_are_present_on_404_responses()
    {
        $response = $this->get('/this-route-does-not-exist');
        $response->assertStatus(404);

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
