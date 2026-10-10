<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0b1329">

        <title>{{ config('app.name', 'VOIC AI') }}</title>

        <!-- Scripts -->
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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-[#070d19] min-h-screen relative overflow-x-hidden transition-colors duration-200">
        <!-- Ambient Mesh Light Aura Orbs -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10" aria-hidden="true">
            <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-blue-500/10 dark:bg-blue-600/15 blur-3xl"></div>
            <div class="absolute top-1/3 -right-40 w-[550px] h-[550px] rounded-full bg-indigo-500/10 dark:bg-indigo-600/15 blur-3xl"></div>
            <div class="absolute -bottom-20 left-1/4 w-[600px] h-[500px] rounded-full bg-cyan-500/8 dark:bg-cyan-600/10 blur-3xl"></div>
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center py-10 px-4 sm:px-6 relative z-10">
            <!-- Brand Badge -->
            <div class="mb-6 text-center">
                <a href="/" class="inline-flex flex-col items-center group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-sky-400 p-0.5 shadow-xl shadow-blue-500/20 group-hover:scale-105 transition-transform duration-300">
                        <div class="w-full h-full bg-white dark:bg-[#0a1424] rounded-[14px] flex items-center justify-center">
                            <span class="text-xl font-black bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600 dark:from-blue-400 dark:via-sky-300 dark:to-white bg-clip-text text-transparent">V</span>
                        </div>
                    </div>
                    <span class="mt-3 text-lg font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5">
                        VOIC <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-300 border border-blue-400/30">AI</span>
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Voice & Optics Intelligent Coach</span>
                </a>
            </div>

            <!-- Card Shell -->
            <div class="w-full sm:max-w-md bg-white/95 dark:bg-[#0c182b]/95 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xl shadow-blue-950/20 dark:shadow-blue-950/60 overflow-hidden rounded-3xl p-6 sm:p-8">
                {{ $slot }}
            </div>

            <!-- Simple Footer Note -->
            <div class="mt-8 text-center text-xs text-slate-500 dark:text-slate-400">
                &copy; {{ date('Y') }} VOIC • Voice & Optics Intelligent Coach. Seluruh data diproses lokal di peramban.
            </div>
        </div>
    </body>
</html>
