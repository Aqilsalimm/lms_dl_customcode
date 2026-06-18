<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription;
use App\Models\Invoice;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $courseParam = $request->route('course') ?? $request->route('slug');
        $courseSlug = null;

        if ($courseParam instanceof \App\Models\Course) {
            $courseSlug = $courseParam->slug;
        } elseif (is_string($courseParam)) {
            $courseSlug = $courseParam;
        }

        if (!$courseSlug) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return redirect()->to('/?login=true');
        }

        // Find the subscription for this user and course
        $subscription = Subscription::whereHas('course', function ($query) use ($courseSlug) {
            $query->where('slug', $courseSlug);
        })
        ->where('user_id', $user->id)
        ->first();

        // If no subscription at all, maybe they aren't enrolled (handle appropriately)
        if (!$subscription) {
            // For now, let the controller handle it or redirect to course page
            return $next($request);
        }

        // If subscription is suspended
        if ($subscription->status === 'suspended') {
            // Find the latest unpaid invoice
            $unpaidInvoice = Invoice::where('subscription_id', $subscription->id)
                ->where('status', 'unpaid')
                ->latest()
                ->first();

            // If an unpaid invoice exists, redirect to the suspension page with the invoice ID
            if ($unpaidInvoice) {
                return redirect()->route('billing.suspended', ['invoice_id' => $unpaidInvoice->id]);
            }
        }

        return $next($request);
    }
}
