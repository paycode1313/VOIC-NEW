<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0b1329">

        <title>{{ $title ?? config('app.name', 'VOIC AI') }} • Platform Evaluasi AI Multimodal</title>

        <!-- Theme Initialization Script -->
        <script>
            (function() {
                const savedTheme = localStorage.getItem('voic_theme');
                if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Favicon & PWA -->
        <link rel="icon" type="image/svg+xml" href="/icon.svg">
        <link rel="manifest" href="/manifest.json">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts / CSS -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-[#070e1b] min-h-screen flex flex-col justify-between selection:bg-blue-600 selection:text-white relative overflow-x-hidden transition-colors duration-200">

        <!-- Ambient Mesh Light Aura Orbs & Minimalist Matrix Grid -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none" aria-hidden="true">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] sm:w-[60rem] h-[28rem] rounded-full bg-gradient-to-b from-blue-600/20 via-sky-500/15 to-transparent blur-[120px] animate-pulse-slow"></div>
            <div class="absolute top-1/4 -left-32 w-80 h-80 rounded-full bg-emerald-500/10 blur-[100px] animate-float-slow"></div>
            <div class="absolute top-1/2 -right-32 w-80 h-80 rounded-full bg-indigo-600/15 blur-[100px] animate-float-delayed"></div>
            <!-- High-Tech Micro Dot Grid Texture with Smooth Radial Mask -->
            <div class="absolute inset-0 bg-[radial-gradient(rgba(59,130,246,0.14)_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_15%,#000_70%,transparent_100%)] opacity-70 dark:opacity-90"></div>
        </div>

        <!-- Top Navigation Bar (Frosted Glass) -->
        <header class="w-full border-b border-slate-200/70 dark:border-white/10 bg-white/75 dark:bg-[#070e1b]/75 backdrop-blur-2xl sticky top-0 z-40 transition-colors duration-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-18 flex items-center justify-between">
                <!-- Brand Logo Link -->
                <a href="/" class="flex items-center gap-3 group">
                    <x-application-logo variant="horizontal" class="h-8 sm:h-9 w-auto group-hover:scale-[1.02] transition-transform duration-200" />
                </a>

                <!-- Right Actions: Back to Home + Dark Mode Switcher -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Back to Home Link -->
                    <a href="/" class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white glass-pill-nav hover:scale-[1.02] transition shadow-2xs">
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Beranda</span>
                    </a>

                    <!-- Dark / Light Theme Toggle -->
                    <button type="button"
                            onclick="
                                const isDark = document.documentElement.classList.toggle('dark');
                                localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                            "
                            class="p-2 sm:p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 glass-pill-nav hover:scale-[1.02] transition cursor-pointer shadow-2xs"
                            title="Ganti Mode Gelap / Terang">
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col justify-center items-center py-8 sm:py-12 px-4 sm:px-6 relative z-10 w-full">
            {{ $slot }}
        </main>

        <!-- Clean Legal & Security Footer (Frosted Glass) -->
        <footer class="w-full border-t border-slate-200/70 dark:border-white/10 py-5 bg-white/60 dark:bg-[#070e1b]/60 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>VOIC Multimodal Security: Seluruh analisis visual & akustik diproses On-Device lokal.</span>
                </div>
                <div>
                    &copy; {{ date('Y') }} VOIC • Voice & Optics Intelligent Coach. Seluruh hak cipta dilindungi.
                </div>
            </div>
        </footer>

    </body>
</html>
