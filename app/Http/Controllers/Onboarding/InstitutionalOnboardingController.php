<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationMembershipRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\OrganizationMembership;
use App\Services\Identity\MembershipCompletionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionalOnboardingController extends Controller
{
    public function __construct(
        private readonly MembershipCompletionService $completionService
    ) {}

    /**
     * Show institutional onboarding page.
     */
    public function show(Request $request): Response
    {
        $user = auth()->user()->loadMissing(['profile', 'organizationMemberships.organization']);

        $memberships = $user->organizationMemberships
            ->where('is_active', true)
            ->values();

        $returnUrl = $request->get('return_url');
        // Validate internal return URL to prevent open redirect vulnerabilities
        if ($returnUrl && (!str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//'))) {
            $returnUrl = null;
        }

        return Inertia::render('Auth/InstitutionalOnboarding', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'profile' => $user->profile,
            'memberships' => $memberships,
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Update user personal legal profile.
     */
    public function updateProfile(UpdateUserProfileRequest $request)
    {
        $user = auth()->user();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        // Re-evaluate memberships completion and persist timestamp
        $user->loadMissing('organizationMemberships');
        foreach ($user->organizationMemberships as $membership) {
            $this->completionService->evaluateAndPersist($membership);
        }

        return back()->with('message', 'Profil personal berhasil diperbarui.');
    }

    /**
     * Update institutional organization membership details.
     */
    public function updateMembership(UpdateOrganizationMembershipRequest $request, OrganizationMembership $membership)
    {
        $user = auth()->user();

        if ($request->has('legal_name')) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->only(['legal_name', 'phone', 'gender'])
            );
            $user->load('profile');
        }

        $this->completionService->updateAndEvaluate(
            $membership,
            $user->profile ? $user->profile->toArray() : [],
            $request->only(['member_type', 'member_number', 'division', 'position'])
        );

        $returnUrl = $request->get('return_url');
        if ($returnUrl && str_starts_with($returnUrl, '/') && !str_starts_with($returnUrl, '//')) {
            return redirect($returnUrl)->with('message', 'Profil institusional berhasil dilengkapi.');
        }

        return back()->with('message', 'Data keanggotaan institusi berhasil diperbarui.');
    }
}
