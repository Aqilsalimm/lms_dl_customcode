<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenseService;

class LicenseMiddleware
{
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Define premium LMS routes that require a valid license key
        $isPremiumRoute = $request->is('course-builder*') || 
            $request->is('courses/*/learn') || 
            $request->is('payment*') || 
            $request->is('checkout') || 
            $request->is('cart/checkout');

        if ($isPremiumRoute && !$this->licenseService->isValid()) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => 'License Required: Please enter a valid license key to access premium LMS features.',
                    'license_error' => true,
                    'redirect_url' => route('dashboard.settings', ['tab' => 'license'])
                ], 402);
            }

            return redirect()->route('dashboard.settings', ['tab' => 'license'])
                ->with('error', 'License Required: Please enter a valid license key to access premium LMS features.');
        }

        return $next($request);
    }
}
