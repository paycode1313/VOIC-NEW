@props(['title' => null, 'back' => null, 'backLabel' => null, 'fullHeight' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'VOIC') }}</title>
        <meta name="theme-color" content="#f6f6f3">
        <link rel="icon" type="image/svg+xml" href="/icon.svg">
        <script>
            (function () {
                try {
                    const saved = localStorage.getItem('voic_theme');
                    if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#f6f6f3] text-slate-900 dark:bg-[#0c0d10] dark:text-slate-100 {{ $fullHeight ? 'h-dvh overflow-hidden flex flex-col' : 'min-h-screen' }}">
        <header class="shrink-0 border-b border-black/5 dark:border-white/5 bg-[#f6f6f3]/85 dark:bg-[#0c0d10]/85 backdrop-blur sticky top-0 z-30">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($back)
                        <a href="{{ $back }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white" aria-label="{{ $backLabel ?? 'Kembali' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @endif
                    <a href="{{ route('exam.index') }}" class="flex items-center gap-2 font-extrabold tracking-tight">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-black text-white dark:bg-white dark:text-slate-900">V</span>
                        <span class="text-[15px]">VOIC <span class="font-semibold text-slate-400">Prep</span></span>
                    </a>
                    {{ $headerLeft ?? '' }}
                </div>
                <div class="flex items-center gap-1.5">
                    {{ $headerRight ?? '' }}
                    <button type="button"
                            onclick="const d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('voic_theme',d?'dark':'light')}catch(e){}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white"
                            aria-label="Ganti tema">
                        <svg class="h-[18px] w-[18px] dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                        <svg class="hidden h-[18px] w-[18px] dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-600 hover:bg-black/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-600 hover:bg-black/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Masuk</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="{{ $fullHeight ? 'flex-1 min-h-0' : '' }}">
            {{ $slot }}
        </main>
    </body>
</html>
