<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceModeSetting = \App\Models\Setting::where('key', 'maintenance_mode')->value('value');
        $isMaintenanceMode = filter_var($maintenanceModeSetting, FILTER_VALIDATE_BOOLEAN);

        if ($isMaintenanceMode) {
            // Allow access to login, logout, and dashboard endpoints for admins
            if (!$request->is('dashboard/*') && !$request->is('login') && !$request->is('logout') && !$request->is('api/*')) {
                $user = $request->user();
                if (!$user || $user->role !== 'admin') {
                    abort(503, 'Site is under maintenance. Please try again later.');
                }
            }
        }

        return $next($request);
    }
}
