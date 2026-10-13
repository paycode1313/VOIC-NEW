<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'VOIC AI') }}</title>
        <meta name="theme-color" content="#070b14">
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/svg+xml" href="/icon.svg">

        <!-- Theme Initialization Script -->
        <script>
            (function() {
                const savedTheme = localStorage.getItem('voic_theme');
                if (savedTheme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- PWA Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function(err) {
                        console.warn('SW registration skipped:', err);
                    });
                });
            }
        </script>

        <!-- Fonts & DNS Prefetch -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 dark:bg-[#070b14] text-slate-900 dark:text-slate-100 selection:bg-blue-600 selection:text-white relative overflow-x-hidden min-h-screen flex flex-col transition-colors duration-200">
        <!-- Ambient Background Orbs -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10" aria-hidden="true">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[750px] h-[450px] rounded-full bg-blue-500/10 dark:bg-blue-500/10 blur-[130px]"></div>
            <div class="absolute top-1/3 -right-40 w-[450px] h-[450px] rounded-full bg-indigo-500/5 dark:bg-indigo-600/8 blur-[100px]"></div>
            <div class="absolute -bottom-32 left-1/4 w-[600px] h-[450px] rounded-full bg-sky-500/5 dark:bg-sky-600/6 blur-[120px]"></div>
        </div>

        <!-- Full-Width Top Navigation Bar -->
        @include('layouts.navigation')

        <!-- Optional Page Header -->
        @isset($header)
            <header class="bg-white/80 dark:bg-[#0b121e]/70 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 print:hidden transition-colors">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Main Page Content -->
        <main class="flex-1 w-full">
            {{ $slot }}
        </main>

        <!-- Onboarding & Tutorial Guide Modal -->
        <x-onboarding-wizard />
    </body>
</html>
