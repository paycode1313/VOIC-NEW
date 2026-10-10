<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'VOIC AI') }}</title>
        <meta name="theme-color" content="#0b1329">
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/svg+xml" href="/icon.svg">

        <!-- Theme Initialization (Default to Professional Light Theme) -->
        <script>
            (function() {
                const savedTheme = localStorage.getItem('voic_theme');
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
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
    <body class="font-sans antialiased bg-slate-50 dark:bg-[#070d19] text-slate-900 dark:text-slate-100 selection:bg-blue-600 selection:text-white relative overflow-x-hidden"
          x-data="{ sidebarOpen: false }">
        <!-- Ambient Mesh Background Orbs -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10" aria-hidden="true">
            <div class="absolute -top-32 -left-32 w-[550px] h-[550px] rounded-full bg-blue-500/10 dark:bg-blue-600/15 blur-3xl"></div>
            <div class="absolute top-1/3 -right-32 w-[500px] h-[500px] rounded-full bg-sky-500/8 dark:bg-sky-500/10 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/3 w-[600px] h-[450px] rounded-full bg-indigo-500/8 dark:bg-indigo-600/12 blur-3xl"></div>
        </div>

        <div class="min-h-screen transition-colors duration-200">
            <!-- Sidebar Navigation (Fixed on Desktop + Mobile Drawer) -->
            @include('layouts.sidebar')

            <!-- Main Content Area (Offset by sidebar width on lg: screens) -->
            <div class="lg:pl-64 print:pl-0 flex flex-col min-h-screen transition-all duration-300">
                <!-- Topbar Header -->
                @include('layouts.topbar')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white/80 dark:bg-[#0b1424]/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 shadow-xs print:hidden">
                        <div class="max-w-7xl mx-auto py-4 sm:py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Onboarding & Tutorial Guide Modal -->
        <x-onboarding-wizard />
    </body>
</html>
