@props(['title' => null, 'back' => null, 'backLabel' => null, 'fullHeight' => false, 'call' => false])

{{-- The single page shell for the whole site (practice, dashboard, history, auth). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · ' : '' }}VOIC</title>
        <meta name="theme-color" content="#f6f6f3">
        <link rel="icon" type="image/svg+xml" href="/icon.svg">
        <link rel="manifest" href="/manifest.json">
        <script>
            (function () {
                try {
                    localStorage.removeItem('voic_theme');
                } catch (e) {}
                // Booth kiosk (set by the welcome screen): hide account and navigation links.
                try { if (sessionStorage.getItem('voic_kiosk') === '1') document.documentElement.classList.add('voic-kiosk'); } catch (e) {}
                // Scroll effects (js/scroll-fx.js) hide [data-split]/[data-reveal] until they animate in: decide before the first paint.
                if (!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) document.documentElement.classList.add('voic-fx');
            })();
        </script>
        @vite(['src/css/app.css', 'src/js/app.js'])
    </head>
    <body class="relative isolate font-sans antialiased {{ $call ? 'bg-[#eef1f8] text-slate-900' : 'bg-[#f6f6f3] text-slate-900 dark:bg-[#0c0d10] dark:text-slate-100' }} {{ $fullHeight ? 'h-dvh overflow-hidden flex flex-col' : 'min-h-screen' }}">
        {{-- A call (the exam room) is full screen, like a video call: no site header. --}}
        @unless ($call)
        {{-- Soft brand backdrop (indigo, sky, amber) so light pages have depth instead of flat beige. --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-40 -top-40 h-[34rem] w-[34rem] rounded-full bg-indigo-300/30 blur-3xl"></div>
            <div class="absolute -right-32 top-1/3 h-[28rem] w-[28rem] rounded-full bg-sky-200/40 blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 h-[30rem] w-[30rem] rounded-full bg-amber-200/30 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.35] [background-image:radial-gradient(rgb(15_23_42/0.07)_1px,transparent_1px)] [background-size:22px_22px] [mask-image:linear-gradient(to_bottom,black,transparent_70%)]"></div>
            <div class="voic-grain absolute inset-0"></div>
        </div>
        <header x-data="{ menu: false, account: false }" class="shrink-0 border-b border-black/5 dark:border-white/5 bg-white/70 shadow-[0_1px_0_rgb(255_255_255/0.6)_inset] dark:bg-[#0c0d10]/85 backdrop-blur sticky top-0 z-30">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($back)
                        <a href="{{ $back }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-black/5 hover:text-slate-900 active:bg-black/10 dark:hover:bg-white/10 dark:hover:text-white" aria-label="{{ $backLabel ?? 'Kembali' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @endif
                    <a href="{{ url('/') }}" data-kiosk-href="{{ route('attract') }}" class="flex shrink-0 items-center" aria-label="VOIC">
                        <img src="/images/logo/voic-logo-horizontal.svg" alt="VOIC" class="h-7 w-auto dark:hidden" width="99" height="28">
                        <img src="/images/logo/voic-logo-horizontal-dark.svg" alt="VOIC" class="hidden h-7 w-auto dark:block" width="99" height="28">
                    </a>
                    <nav data-kiosk-hide class="ml-4 hidden items-center gap-1 text-sm font-semibold md:flex" aria-label="Navigasi utama">
                        @php
                            $links = [['exam.index', 'Latihan', 'exam.*']];
                            if (auth()->check()) {
                                $links[] = ['dashboard', 'Dashboard', 'dashboard'];
                                $links[] = ['practice.history', 'Riwayat', 'practice.history'];
                                $links[] = ['classrooms.index', 'Kelas', 'classrooms.*'];
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
                    <a href="{{ route('attract') }}" data-kiosk-show class="hidden h-9 items-center gap-1.5 rounded-full bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                        Selesai
                    </a>
                    @auth
                        <button type="button" @click="$dispatch('open-onboarding')" title="Panduan & Tutorial"
                                class="hidden h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-500 hover:bg-black/5 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white sm:inline-flex">
                            <span aria-hidden="true">Panduan</span><span class="sr-only">Panduan Pemakaian</span>
                        </button>
                    @endauth
                    @auth
                        {{-- Desktop: avatar dropdown --}}
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
                        {{-- Mobile: account icon (taps open the dropdown) --}}
                        <button type="button" data-kiosk-hide @click="menu = !menu" :aria-expanded="menu" aria-label="Akun"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full transition active:scale-95 md:hidden">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                        </button>
                    @else
                        <a data-kiosk-hide href="{{ route('login') }}" class="hidden h-9 items-center rounded-full px-3 text-sm font-semibold text-slate-600 hover:bg-black/5 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white sm:inline-flex">Masuk</a>
                        @if (Route::has('register'))
                            <a data-kiosk-hide href="{{ route('register') }}" class="hidden h-9 items-center rounded-full bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 sm:inline-flex">Daftar</a>
                        @endif
                        {{-- Mobile guest: compact CTA --}}
                        <a data-kiosk-hide href="{{ route('register') }}" class="inline-flex h-9 items-center rounded-full bg-slate-900 px-4 text-sm font-semibold text-white active:bg-slate-700 md:hidden">Daftar</a>
                    @endauth
                </div>
            </div>
            {{-- Mobile account dropdown (guide + logout) --}}
            @auth
            <div x-show="menu" x-cloak data-kiosk-hide
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="border-t border-black/5 dark:border-white/10 md:hidden bg-white/95 dark:bg-[#0c0d10]/98">
                <div class="px-4 py-3 flex items-center gap-3 border-b border-black/5 dark:border-white/10">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white dark:bg-white dark:text-slate-900">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="font-bold text-sm truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <div class="px-3 py-2 space-y-0.5 text-sm font-semibold">
                    <button type="button" @click="menu = false; $dispatch('open-onboarding')" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-black/5 dark:hover:bg-white/10 active:bg-black/10">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                        Panduan
                    </button>
                    <div class="border-t border-black/5 dark:border-white/10 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 active:bg-rose-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </header>

        {{-- ========== BOTTOM NAVIGATION BAR (mobile-only, hidden on desktop md+) ========== --}}
        @auth
        <nav data-kiosk-hide aria-label="Navigasi bawah"
             class="voic-bottom-nav fixed bottom-0 inset-x-0 z-40 md:hidden border-t border-black/[0.07] dark:border-white/[0.08] bg-white/92 dark:bg-[#0c0d10]/95 backdrop-blur-xl">
            @php
                $navItems = [
                    [
                        'route'   => 'exam.index',
                        'pattern' => 'exam.*',
                        'label'   => 'Latihan',
                        'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>',
                    ],
                    [
                        'route'   => 'dashboard',
                        'pattern' => 'dashboard',
                        'label'   => 'Dashboard',
                        'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>',
                    ],
                    [
                        'route'   => 'practice.history',
                        'pattern' => 'practice.history',
                        'label'   => 'Riwayat',
                        'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'route'   => 'classrooms.index',
                        'pattern' => 'classrooms.*',
                        'label'   => 'Kelas',
                        'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>',
                    ],
                    [
                        'route'   => 'profile.edit',
                        'pattern' => 'profile.*',
                        'label'   => 'Profil',
                        'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>',
                    ],
                ];
            @endphp
            <div class="flex items-stretch voic-bottom-nav-inner">
                @foreach ($navItems as $item)
                    @php $isActive = request()->routeIs($item['pattern']); @endphp
                    <a href="{{ route($item['route']) }}"
                       aria-current="{{ $isActive ? 'page' : 'false' }}"
                       class="relative flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[10px] font-semibold tracking-tight transition-all duration-150 select-none active:scale-90 {{ $isActive ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300' }}">
                        {{-- Active indicator dot --}}
                        @if ($isActive)
                            <span class="absolute top-0 inset-x-1/4 h-[2.5px] rounded-b-full bg-slate-900 dark:bg-white" aria-hidden="true"></span>
                        @endif
                        <svg class="h-6 w-6 transition-transform duration-100 {{ $isActive ? '' : '' }}" fill="none" stroke="currentColor" stroke-width="{{ $isActive ? '2' : '1.75' }}" viewBox="0 0 24 24" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span class="{{ $isActive ? '' : 'opacity-70' }}">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
        @else
        {{-- Guest: bottom bar with Masuk / Daftar (hidden on auth pages where the form is already on screen) --}}
        @unless (request()->routeIs('login', 'register', 'password.*', 'verification.*'))
        <nav data-kiosk-hide aria-label="Navigasi bawah"
             class="voic-bottom-nav fixed bottom-0 inset-x-0 z-40 md:hidden border-t border-black/[0.07] dark:border-white/[0.08] bg-white/92 dark:bg-[#0c0d10]/95 backdrop-blur-xl">
            <div class="flex items-center gap-3 px-4 voic-bottom-nav-inner">
                <a href="{{ route('login') }}" class="flex flex-1 items-center justify-center rounded-full border border-black/10 dark:border-white/15 text-sm font-bold text-slate-700 dark:text-slate-300 active:bg-black/10 h-11">Masuk</a>
                <a href="{{ route('register') }}" class="flex flex-1 items-center justify-center rounded-full bg-slate-900 dark:bg-white dark:text-slate-900 text-sm font-bold text-white active:bg-slate-700 h-11">Daftar gratis</a>
            </div>
        </nav>
        @endunless
        @endauth
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

        @unless ($call)
            {{-- Booth kiosk: if this tab came from the welcome screen, go back there when the visitor walks away. --}}
            <script>
                (function () {
                    try { if (sessionStorage.getItem('voic_kiosk') !== '1') return; } catch (e) { return; }
                    document.querySelectorAll('[data-kiosk-href]').forEach(function (a) { a.href = a.dataset.kioskHref; });
                    var IDLE = 120, WARN = 15, idle = 0, box = null;
                    function reset() { idle = 0; if (box) { box.remove(); box = null; } }
                    ['pointerdown', 'keydown', 'wheel', 'touchstart', 'mousemove'].forEach(function (e) { addEventListener(e, reset, { passive: true }); });
                    setInterval(function () {
                        idle++;
                        if (idle >= IDLE + WARN) { location.href = @js(route('attract')); return; }
                        if (idle < IDLE) return;
                        if (!box) {
                            box = document.createElement('div');
                            box.setAttribute('role', 'alertdialog');
                            box.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-6 backdrop-blur-sm';
                            box.innerHTML = '<div class="w-full max-w-sm rounded-3xl bg-white p-6 text-center text-slate-900 shadow-2xl"><p class="text-lg font-extrabold">Masih di sini?</p><p class="mt-1 text-sm text-slate-600">Kembali ke layar awal dalam <b data-left></b> detik.</p><button type="button" class="mt-5 w-full rounded-full bg-slate-900 px-5 py-3 font-bold text-white">Lanjutkan</button></div>';
                            box.querySelector('button').addEventListener('click', reset);
                            document.body.appendChild(box);
                        }
                        box.querySelector('[data-left]').textContent = IDLE + WARN - idle;
                    }, 1000);
                })();
            </script>
        @endunless
    </body>
</html>
