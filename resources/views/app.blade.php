<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Restaurante Las Brazas') }}</title>
        <meta name="theme-color" content="#ea580c">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="icon" type="image/jpeg" href="/images/logo-dark.jpg">
        <link rel="apple-touch-icon" href="/images/logo-dark.jpg">
        <link rel="manifest" href="/manifest.webmanifest">

        <!-- Theme Initialization Script to avoid FOUC -->
        <script>
            (function() {
                try {
                    var saved = localStorage.getItem('theme');
                    if (saved === 'light') {
                        document.documentElement.classList.remove('dark');
                        document.documentElement.classList.add('light');
                    } else {
                        document.documentElement.classList.add('dark');
                        document.documentElement.classList.remove('light');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 min-h-screen overflow-x-hidden transition-colors duration-200">
        @inertia
    </body>
</html>
