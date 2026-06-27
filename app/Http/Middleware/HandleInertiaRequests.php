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
        return parent::version($request);
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
        ];
    }

    /**
     * Get public settings, filtering out any sensitive backend keys
     */
    private function getPublicSettings(): array
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        
        $sensitiveKeys = [
            'midtrans_server_key',
            'xendit_secret_key',
            'brevo_api_key',
            'google_client_secret',
            'license_key',
        ];
        
        foreach ($sensitiveKeys as $key) {
            unset($settings[$key]);
        }
        
        return $settings;
    }
}
