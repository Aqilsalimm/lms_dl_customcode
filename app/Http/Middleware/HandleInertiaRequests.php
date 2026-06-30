<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        if (file_exists($manifestPath = public_path('build/manifest.json'))) {
            return md5_file($manifestPath);
        }
        return parent::version($request);
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        // Prevent browser caching of dynamic Inertia HTML/JSON responses to avoid blank pages and stale asset versions
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $translationsPath = base_path("lang/{$locale}.json");
        $translations = file_exists($translationsPath) 
            ? json_decode(file_get_contents($translationsPath), true) 
            : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'notifications' => $request->user() ? $request->user()->unreadNotifications()->latest()->get() : [],
            ],
            'locale' => $locale,
            'translations' => $translations,
            'settings' => $this->getPublicSettings(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'logout_message' => $request->session()->get('logout_message'),
            ],
            'ziggy' => function () use ($request) {
                $user = $request->user();
                $group = 'public';
                if ($user) {
                    if ($user->isAdmin()) {
                        $group = 'admin';
                    } elseif ($user->isInstructor()) {
                        $group = 'instructor';
                    } else {
                        $group = 'student';
                    }
                }
                return array_merge((new \Tighten\Ziggy\Ziggy($group))->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }

    /**
     * Get public settings, filtering out any sensitive backend keys using a strict whitelist
     */
    private function getPublicSettings(): array
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        
        $publicKeys = [
            'site_name',
            'course_logo',
            'course_columns',
            'instructor_list_layout',
            'blog_template',
            'enable_otp',
            'enable_native_ppt',
            'native_ppt_access',
            'allowed_blog_instructors',
            'pagination_rows',
            'midtrans_client_key',
            'midtrans_sandbox_mode',
            'enable_marketplace',
            'become_instructor_button',
            'instructor_publish_course',
            'instructor_trash_course',
            'instructor_change_author',
            'dashboard_page',
            'terms_page',
            'privacy_page',
            'primary_color',
            'primary_hover_color',
            'text_color',
            'gray_color',
            'border_color',
            'text_color_hover',
        ];
        
        return array_intersect_key($settings, array_flip($publicKeys));
    }
}
