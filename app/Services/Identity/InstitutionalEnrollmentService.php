<?php

namespace App\Services\Identity;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InstitutionalEnrollmentService
{
    /**
     * Enroll a user into a course with an optional organization membership context.
     */
    public function enroll(User $user, Course $course, ?OrganizationMembership $membership = null): Enrollment
    {
        if ($membership !== null) {
            if ($membership->user_id !== $user->id) {
                throw new InvalidArgumentException('Organization membership does not belong to the enrolled user.');
            }

            if (!$membership->is_active) {
                throw new InvalidArgumentException('Organization membership is inactive.');
            }
        }

        return DB::transaction(function () use ($user, $course, $membership) {
            $enrollment = Enrollment::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'organization_membership_id' => $membership?->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]
            );

            if ($membership !== null && $enrollment->organization_membership_id !== $membership->id) {
                $enrollment->update(['organization_membership_id' => $membership->id]);
            }

            return $enrollment;
        });
    }
}
