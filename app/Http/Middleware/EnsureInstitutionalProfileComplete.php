<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Identity\MembershipCompletionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstitutionalProfileComplete
{
    public function __construct(
        private readonly MembershipCompletionService $completionService
    ) {}

    /**
     * Handle an incoming request to ensure institutional enrollment profile is complete.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Resolve course from route parameter if available
        $courseParam = $request->route('course');
        $courseId = null;

        if ($courseParam instanceof Course) {
            $courseId = $courseParam->id;
        } elseif (is_numeric($courseParam)) {
            $courseId = (int) $courseParam;
        } elseif (is_string($courseParam)) {
            $c = Course::where('slug', $courseParam)->first();
            $courseId = $c?->id;
        }

        if (!$courseId) {
            return $next($request);
        }

        // Bounded query to find user enrollment for this course, prioritize institutional enrollment
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->orderByRaw('organization_membership_id IS NOT NULL DESC')
            ->with(['organizationMembership.organization', 'user.profile'])
            ->first();

        // If no enrollment or public enrollment (no membership), allow request
        if (!$enrollment || !$enrollment->organization_membership_id) {
            return $next($request);
        }

        $membership = $enrollment->organizationMembership;

        // If membership is complete, allow request
        if ($membership && $this->completionService->isComplete($membership)) {
            return $next($request);
        }

        // Profile incomplete for institutional course
        $onboardingUrl = route('onboarding.institutional.show', [
            'return_url' => $request->getRequestUri(),
        ]);

        if ($request->is('api/*') || ($request->expectsJson() && !$request->header('X-Inertia'))) {
            return response()->json([
                'message' => 'Profil institusional belum lengkap.',
                'code' => 'INSTITUTIONAL_PROFILE_INCOMPLETE',
                'onboarding_url' => $onboardingUrl,
            ], 409);
        }

        // If Inertia, we must return a 409 with X-Inertia-Location header
        if ($request->header('X-Inertia')) {
            return response('', 409, ['X-Inertia-Location' => $onboardingUrl]);
        }

        return redirect()->guest($onboardingUrl);
    }
}
