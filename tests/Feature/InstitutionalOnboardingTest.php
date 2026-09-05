<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\Identity\MembershipCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('isValid')->andReturn(true);
        });
    }

    private function createCourse(): Course
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        return Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course ' . uniqid(),
            'slug' => 'test-course-' . uniqid(),
            'level' => 'Umum',
            'price' => 0,
            'status' => 'published',
        ]);
    }

    public function test_public_student_enrollment_is_not_redirected_to_onboarding(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createCourse();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'organization_membership_id' => null,
        ]);

        $response = $this->actingAs($student)->get(route('courses.learn', $course->slug));
        if ($response->status() !== 200) {
            dump('Redirect target: ' . $response->headers->get('Location'));
        }
        $response->assertStatus(200);
    }

    public function test_institutional_student_with_incomplete_membership_is_redirected(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $org = Organization::create([
            'code' => 'ORG-TEST-1',
            'name' => 'Test Corp',
            'is_active' => true,
        ]);
        $membership = OrganizationMembership::create([
            'user_id' => $student->id,
            'organization_id' => $org->id,
            'member_type' => 'employee_id',
            'member_number' => null,
            'profile_completed_at' => null,
            'is_active' => true,
        ]);

        $course = $this->createCourse();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'organization_membership_id' => $membership->id,
        ]);

        // Protected route with institutional.profile middleware
        $response = $this->actingAs($student)->get(route('courses.learn', $course->slug));
        $response->assertRedirect(route('onboarding.institutional.show', ['return_url' => parse_url(route('courses.learn', $course->slug), PHP_URL_PATH)]));
    }

    public function test_json_request_returns_409_conflict_when_profile_incomplete(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $org = Organization::create([
            'code' => 'ORG-TEST-2',
            'name' => 'Test Corp 2',
            'is_active' => true,
        ]);
        $membership = OrganizationMembership::create([
            'user_id' => $student->id,
            'organization_id' => $org->id,
            'member_type' => 'employee_id',
            'member_number' => null,
            'profile_completed_at' => null,
            'is_active' => true,
        ]);

        $course = $this->createCourse();

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'organization_membership_id' => $membership->id,
        ]);

        $response = $this->actingAs($student)
            ->getJson(route('courses.learn', $course->slug));

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'INSTITUTIONAL_PROFILE_INCOMPLETE',
            ]);
    }

    public function test_updating_profile_and_membership_completes_onboarding(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $org = Organization::create([
            'code' => 'ORG-TEST-3',
            'name' => 'Test Corp 3',
            'is_active' => true,
        ]);
        $membership = OrganizationMembership::create([
            'user_id' => $student->id,
            'organization_id' => $org->id,
            'member_type' => 'employee_id',
            'member_number' => null,
            'profile_completed_at' => null,
            'is_active' => true,
        ]);

        $service = app(MembershipCompletionService::class);

        $isComplete = $service->updateAndEvaluate(
            $membership,
            ['legal_name' => 'Dr. Budi Santoso', 'phone' => '08123456789', 'gender' => 'Laki-laki'],
            ['member_type' => 'nip', 'member_number' => '199408222019031002', 'division' => 'IT Ops', 'position' => 'Lead']
        );

        $this->assertTrue($isComplete);
        $this->assertNotNull($membership->fresh()->profile_completed_at);
        $this->assertEquals('Dr. Budi Santoso', $student->profile->legal_name);
        $this->assertEquals('Laki-laki', $student->profile->gender);
    }
}
