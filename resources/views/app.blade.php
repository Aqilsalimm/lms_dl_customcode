<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/images/logo/logo_dl.png" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        @php
            $copyProtection = \App\Models\Setting::where('key', 'copy_protection')->value('value');
            $isCopyProtectionEnabled = filter_var($copyProtection, FILTER_VALIDATE_BOOLEAN);
        @endphp

        @if($isCopyProtectionEnabled)
            <style>
                body {
                    -webkit-user-select: none !important;
                    -moz-user-select: none !important;
                    -ms-user-select: none !important;
                    user-select: none !important;
                }
            </style>
            <script>
                document.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                });
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && (e.key === 'c' || e.key === 'C' || e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S' || e.key === 'a' || e.key === 'A')) {
                        e.preventDefault();
                    }
                    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'i' || e.key === 'I'))) {
                        e.preventDefault();
                    }
                });
            </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
