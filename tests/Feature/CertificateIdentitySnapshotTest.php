<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\Certificates\CertificateIdentityPresenter;
use App\Services\Certificates\CertificateIssuanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateIdentitySnapshotTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_public_certificate_snapshot_stores_user_name_with_null_organization(): void
    {
        $student = User::factory()->create(['name' => 'Budi Santoso']);
        $course = $this->createCourse();
        $cert = Certificate::create([
            'course_id' => $course->id,
            'title' => 'Completion Cert',
            'type' => 'course_completion',
        ]);

        $issuanceService = app(CertificateIssuanceService::class);
        $userCert = $issuanceService->issue($student, $course, $cert);

        $this->assertNotNull($userCert->identity_snapshot);
        $this->assertEquals('Budi Santoso', $userCert->identity_snapshot['recipient']['display_name']);
        $this->assertNull($userCert->identity_snapshot['organization']);
        $this->assertNull($userCert->identity_snapshot['membership']);
    }

    public function test_institutional_certificate_snapshot_stores_organization_and_membership(): void
    {
        $student = User::factory()->create(['name' => 'Budi Santoso']);
        $student->profile()->create(['legal_name' => 'Dr. Budi Santoso, S.Kom.']);

        $org = Organization::create([
            'code' => 'KEMENKEU',
            'name' => 'Kementerian Keuangan',
            'legal_name' => 'Kementerian Keuangan RI',
            'is_active' => true,
        ]);

        $membership = OrganizationMembership::create([
            'user_id' => $student->id,
            'organization_id' => $org->id,
            'member_type' => 'nip',
            'member_number' => '199408222019031002',
            'division' => 'Ditjen Pajak',
            'position' => 'Fungsional Analyst',
            'is_active' => true,
        ]);

        $course = $this->createCourse();
        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'organization_membership_id' => $membership->id,
        ]);

        $cert = Certificate::create([
            'course_id' => $course->id,
            'title' => 'Completion Cert',
            'type' => 'course_completion',
        ]);

        $issuanceService = app(CertificateIssuanceService::class);
        $userCert = $issuanceService->issue($student, $course, $cert, $enrollment);

        $this->assertNotNull($userCert->identity_snapshot);
        $this->assertEquals('Dr. Budi Santoso, S.Kom.', $userCert->identity_snapshot['recipient']['legal_name']);
        $this->assertEquals('KEMENKEU', $userCert->identity_snapshot['organization']['code']);
        $this->assertEquals('199408222019031002', $userCert->identity_snapshot['membership']['member_number']);

        $presenter = app(CertificateIdentityPresenter::class);
        $this->assertEquals('Dr. Budi Santoso, S.Kom.', $presenter->getRecipientName($userCert));
        $this->assertEquals('Kementerian Keuangan', $presenter->getOrganizationName($userCert));
        $this->assertEquals('19**************02', $presenter->getMemberNumber($userCert, true));
    }

    public function test_subsequent_user_profile_changes_do_not_alter_historical_snapshot(): void
    {
        $student = User::factory()->create(['name' => 'Budi Santoso']);
        $student->profile()->create(['legal_name' => 'Dr. Budi Santoso, S.Kom.']);

        $org = Organization::create([
            'code' => 'KEMENKEU',
            'name' => 'Kementerian Keuangan',
            'is_active' => true,
        ]);

        $membership = OrganizationMembership::create([
            'user_id' => $student->id,
            'organization_id' => $org->id,
            'member_type' => 'nip',
            'member_number' => '199408222019031002',
            'division' => 'Ditjen Pajak',
            'position' => 'Fungsional Analyst',
            'is_active' => true,
        ]);

        $course = $this->createCourse();
        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'organization_membership_id' => $membership->id,
        ]);

        $cert = Certificate::create([
            'course_id' => $course->id,
            'title' => 'Completion Cert',
            'type' => 'course_completion',
        ]);

        $issuanceService = app(CertificateIssuanceService::class);
        $userCert = $issuanceService->issue($student, $course, $cert, $enrollment);

        // Student updates profile and changes companies 2 years later
        $student->update(['name' => 'Budi Santoso Updated']);
        $student->profile->update(['legal_name' => 'Dr. Budi Santoso, Ph.D.']);
        $membership->update(['division' => 'Ditjen Perimbangan Keuangan']);

        // Assert historical certificate snapshot remains untouched!
        $freshUserCert = $userCert->fresh();
        $this->assertEquals('Dr. Budi Santoso, S.Kom.', $freshUserCert->identity_snapshot['recipient']['legal_name']);
        $this->assertEquals('Ditjen Pajak', $freshUserCert->identity_snapshot['membership']['division']);
    }
}
