<?php

namespace App\Services\Certificates;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateIssuanceService
{
    public function __construct(
        private readonly CertificateIdentitySnapshotFactory $snapshotFactory
    ) {}

    /**
     * Issue or retrieve a user certificate idempotently with an immutable identity snapshot.
     */
    public function issue(User $user, Course $course, Certificate $certificate, ?Enrollment $enrollment = null): UserCertificate
    {
        if (!$enrollment) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
        }

        return DB::transaction(function () use ($user, $course, $certificate, $enrollment) {
            $existing = UserCertificate::where('user_id', $user->id)
                ->where('certificate_id', $certificate->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $snapshot = $this->snapshotFactory->create($user, $enrollment);

            $code = 'CERT-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999);

            try {
                return UserCertificate::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'certificate_id' => $certificate->id,
                    'source_enrollment_id' => $enrollment?->id,
                    'certificate_code' => $code,
                    'claimed_at' => now(),
                    'identity_snapshot' => $snapshot,
                    'snapshot_version' => $snapshot['version'] ?? 1,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $existing = UserCertificate::where('user_id', $user->id)
                    ->where('certificate_id', $certificate->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                throw $e;
            }
        });
    }
}
