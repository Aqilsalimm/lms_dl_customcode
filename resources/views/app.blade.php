<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#264790">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Drastha LMS">

        <!-- Preload Hero Image for LCP performance -->
        <link rel="preload" as="image" href="/images/pages/welcome/welcome_beranda.gif" type="image/gif" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
        <noscript>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
        </noscript>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/images/logo/logo_dl.png" />

        <!-- Scripts -->
        @php
            $user = auth()->user();
        @endphp
        @if($user && $user->isAdmin())
            @routes('admin')
        @elseif($user && $user->isInstructor())
            @routes('instructor')
        @elseif($user)
            @routes('student')
        @else
            @routes('public')
        @endif
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        @php
            $copyProtection = \App\Models\Setting::where('key', 'copy_protection')->value('value');
            $isCopyProtectionEnabled = filter_var($copyProtection, FILTER_VALIDATE_BOOLEAN);
            $isAdmin = auth()->check() && auth()->user()->role === 'admin';
            $isLocal = app()->environment('local');
        @endphp

        @if($isCopyProtectionEnabled && !$isAdmin)
            <style>
                body {
                    -webkit-user-select: none !important;
                    -moz-user-select: none !important;
                    -ms-user-select: none !important;
                    user-select: none !important;
                }
            </style>
            <noscript>
                <style>
                    body {
                        display: none !important;
                    }
                </style>
            </noscript>
            <script>
                document.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                });
                document.addEventListener('keydown', function(e) {
                    // Disable Ctrl+C, Ctrl+U, Ctrl+S, Ctrl+A
                    if (e.ctrlKey && (e.key === 'c' || e.key === 'C' || e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S' || e.key === 'a' || e.key === 'A')) {
                        e.preventDefault();
                    }
                    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C
                    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'i' || e.key === 'I' || e.key === 'j' || e.key === 'J' || e.key === 'c' || e.key === 'C'))) {
                        e.preventDefault();
                    }
                });

                @if(!$isLocal)
                // Anti-DevTools Debugger loop
                (function() {
                    const blockDevTools = function() {
                        const before = new Date().getTime();
                        debugger;
                        const after = new Date().getTime();
                        if (after - before > 100) {
                            document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:Montserrat,sans-serif;font-size:18px;color:#ef4444;background:#f8fafc;font-weight:bold;">Akses ditolak. Silakan tutup Developer Tools untuk melihat konten.</div>';
                        }
                    };
                    setInterval(blockDevTools, 1000);
                })();
                @endif
            </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>