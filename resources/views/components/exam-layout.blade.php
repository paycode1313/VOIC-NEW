@props(['title' => null, 'back' => null, 'backLabel' => null, 'fullHeight' => false, 'call' => false])

{{-- The single page shell for the whole site (practice, dashboard, history, auth). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · ' : '' }}VOIC</title>
        <meta name="theme-color" content="#f6f6f3">
        <link rel="icon" type="image/svg+xml" href="/icon.svg">
        <link rel="manifest" href="/manifest.json">
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
    <body class="font-sans antialiased {{ $call ? 'bg-[#0b0d12] text-white' : 'bg-[#f6f6f3] text-slate-900 dark:bg-[#0c0d10] dark:text-slate-100' }} {{ $fullHeight ? 'h-dvh overflow-hidden flex flex-col' : 'min-h-screen' }}">
        {{-- A call (the exam room) is full screen, like a video call: no site header. --}}
        @unless ($call)
        <header x-data="{ menu: false, account: false }" class="shrink-0 border-b border-black/5 dark:border-white/5 bg-[#f6f6f3]/85 dark:bg-[#0c0d10]/85 backdrop-blur sticky top-0 z-30">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($back)
                        <a href="{{ $back }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white" aria-label="{{ $backLabel ?? 'Kembali' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @endif
                    <a href="{{ url('/') }}" class="flex items-center gap-2 font-extrabold tracking-tight">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-black text-white dark:bg-white dark:text-slate-900">V</span>
                        <span class="text-[15px]">VOIC</span>
                    </a>
                    <nav class="ml-4 hidden items-center gap-1 text-sm font-semibold md:flex" aria-label="Navigasi utama">
                        @php
                            $links = [['exam.index', 'Latihan', 'exam.*']];
                            if (auth()->check()) {
                                $links[] = ['dashboard', 'Dashboard', 'dashboard'];
                                $links[] = ['practice.history', 'Riwayat', 'practice.history'];
                            }
                        @endphp
                        @foreach ($links as [$route, $label, $pattern])
                            <a href="{{ route($route) }}" @class([
                                'rounded-full px-3 py-1.5 transition',
                                'bg-black/5 text-slate-900 dark:bg-white/10 dark:text-white' => request()->routeIs($pattern),
                                'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => ! request()->routeIs($pattern),
                            ])>{{ $label }}</a>
                        @endforeach
                    </nav>
                    {{ $headerLeft ?? '' }}
                </div>
                <div class="flex items-center gap-1.5">
                    {{ $headerRight ?? '' }}
                    @auth
                        <button type="button" @click="$dispatch('open-onboarding')" title="Panduan & Tutorial"
                                class="hidden h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white sm:inline-flex">
                            <span aria-hidden="true">Panduan</span><span class="sr-only">Panduan Pemakaian</span>
                        </button>
                    @endauth
                    <button type="button"
                            onclick="const d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('voic_theme',d?'dark':'light')}catch(e){}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white"
                            aria-label="Ganti tema">
                        <svg class="h-[18px] w-[18px] dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                        <svg class="hidden h-[18px] w-[18px] dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    @auth
                        <div class="relative hidden md:block">
                            <button type="button" @click="account = !account" @click.outside="account = false" :aria-expanded="account"
                                    class="inline-flex h-9 items-center gap-2 rounded-full pl-1 pr-3 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/10">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="max-w-[8rem] truncate">{{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</span>
                            </button>
                            <div x-show="account" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-48 overflow-hidden rounded-2xl border border-black/5 bg-white py-1 text-sm shadow-lg dark:border-white/10 dark:bg-[#16171b]">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-black/5 dark:hover:bg-white/5">Profil</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left hover:bg-black/5 dark:hover:bg-white/5">Keluar</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="hidden h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-600 hover:bg-black/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white sm:inline-flex">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden h-9 items-center rounded-full bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 sm:inline-flex">Daftar</a>
                        @endif
                    @endauth
                    <button type="button" @click="menu = !menu" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 dark:hover:bg-white/10 md:hidden" :aria-expanded="menu" aria-label="Menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                </div>
            </div>
            <div x-show="menu" x-cloak class="border-t border-black/5 px-4 py-2 text-sm font-semibold dark:border-white/10 md:hidden">
                <a href="{{ route('exam.index') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Latihan</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('practice.history') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Riwayat</a>
                    <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Profil</a>
                    <button type="button" @click="menu = false; $dispatch('open-onboarding')" class="block w-full rounded-xl px-3 py-2 text-left hover:bg-black/5 dark:hover:bg-white/10">Panduan</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left hover:bg-black/5 dark:hover:bg-white/10">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Masuk</a>
                    <a href="{{ route('register') }}" class="block rounded-xl px-3 py-2 hover:bg-black/5 dark:hover:bg-white/10">Daftar</a>
                @endauth
            </div>
        </header>
        @endunless

        @if (session('status') && ! request()->routeIs('login', 'password.*', 'verification.*') && ! in_array(session('status'), ['verification-link-sent', 'profile-updated', 'password-updated'], true))
            <div class="mx-auto mt-4 max-w-6xl px-4 sm:px-6">
                <p class="rounded-2xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200" role="status">{{ session('status') }}</p>
            </div>
        @endif

        <main class="{{ $fullHeight ? 'flex-1 min-h-0' : '' }}">
            {{ $slot }}
        </main>

        {{ $after ?? '' }}
    </body>
</html>
