<nav x-data="{ open: false }" class="bg-white/90 dark:bg-[#070b14]/90 backdrop-blur-xl border-b border-slate-200/90 dark:border-slate-800/80 sticky top-0 z-40 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Left: Brand Logo Wordmark -->
            <div class="flex items-center gap-8">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center group">
                    <x-application-logo variant="horizontal" class="h-7 sm:h-8 w-auto max-w-[125px] sm:max-w-none group-hover:scale-[1.03] transition-transform" />
                </a>

                <!-- Desktop Nav Links -->
                <div class="hidden md:flex items-center space-x-6 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    @auth
                        <a href="{{ route('dashboard') }}" 
                           class="transition {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-white font-bold' : 'hover:text-slate-900 dark:hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('practice.create') }}" 
                           class="transition {{ request()->routeIs('practice.create') ? 'text-blue-600 dark:text-white font-bold' : 'hover:text-slate-900 dark:hover:text-white' }}">
                            Simulasi AI
                        </a>
                        <a href="{{ route('practice.history') }}" 
                           class="transition {{ request()->routeIs('practice.history') ? 'text-blue-600 dark:text-white font-bold' : 'hover:text-slate-900 dark:hover:text-white' }}">
                            Riwayat Sesi
                        </a>
                    @else
                        <a href="{{ url('/') }}" 
                           class="transition {{ request()->is('/') ? 'text-blue-600 dark:text-white font-bold' : 'hover:text-slate-900 dark:hover:text-white' }}">
                            Beranda
                        </a>
                        <a href="{{ route('demo.create') }}" 
                           class="transition {{ request()->routeIs('demo.*') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'hover:text-slate-900 dark:hover:text-white' }}">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Demo Booth
                            </span>
                        </a>
                    @endauth
                    <button type="button"
                            @click="$dispatch('open-onboarding')"
                            title="Panduan & Tutorial"
                            class="hover:text-slate-900 dark:hover:text-white transition cursor-pointer">
                        Panduan
                        <span class="sr-only">Panduan Pemakaian</span>
                    </button>
                </div>
            </div>

            <!-- Right: Actions, Theme Toggle & User Profile -->
            <div class="hidden sm:flex items-center gap-3">
                <!-- Theme Toggle Button -->
                <button type="button"
                        onclick="
                            const isDark = document.documentElement.classList.toggle('dark');
                            localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                            window.dispatchEvent(new CustomEvent('voic-theme-changed', { detail: { isDark } }));
                        "
                        class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 border border-slate-200 dark:border-white/10 transition cursor-pointer"
                        title="Ganti Mode Tampilan (Gelap / Terang)">
                    <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                @auth
                    <!-- Quick CTA button (hidden if already on practice.create) -->
                    @if(!request()->routeIs('practice.create'))
                        <a href="{{ route('practice.create') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white dark:text-slate-900 bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-200 rounded-xl transition shadow-sm">
                            <span>Mulai Latihan</span>
                        </a>
                    @endif

                    <!-- User Dropdown Menu -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 px-2.5 py-1.5 border border-slate-200 dark:border-white/10 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-slate-100/80 dark:bg-white/5 hover:bg-slate-200/80 dark:hover:bg-white/10 focus:outline-none transition cursor-pointer">
                                <span class="w-6 h-6 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-[10px] flex items-center justify-center">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[120px] truncate font-bold text-slate-900 dark:text-white">{{ Auth::user()->name }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 text-xs">
                                <div class="font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</div>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profil Akun') }}
                            </x-dropdown-link>

                            <button type="button"
                                    @click="$dispatch('open-onboarding')"
                                    class="w-full text-start block px-4 py-2 text-xs leading-5 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none transition cursor-pointer">
                                {{ __('Panduan & Tutorial') }}
                            </button>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Keluar') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <span class="hidden lg:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Sesi Tamu
                        </span>
                        <a href="{{ route('login') }}" class="px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white transition">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow-sm">
                            Daftar
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Mobile Hamburger Toggle -->
            <div class="flex items-center gap-2 sm:hidden">
                <button type="button"
                        onclick="
                            const isDark = document.documentElement.classList.toggle('dark');
                            localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                            window.dispatchEvent(new CustomEvent('voic-theme-changed', { detail: { isDark } }));
                        "
                        class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-white/10">
                    <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button @click="open = ! open" class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 border border-slate-200 dark:border-white/10">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer / Dropdown -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-[#070b14]/95 px-4 pt-3 pb-4 space-y-2">
        @auth
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-white/5">
                Dashboard
            </a>
            <a href="{{ route('practice.create') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-white/5">
                Mulai Simulasi AI
            </a>
            <a href="{{ route('practice.history') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('practice.history') ? 'text-blue-600 dark:text-white bg-slate-100 dark:bg-white/5' : 'text-slate-600 dark:text-slate-300' }} hover:bg-slate-100 dark:hover:bg-white/5">
                Riwayat Sesi
            </a>
            <button type="button"
                    @click="$dispatch('open-onboarding'); open = false;"
                    class="w-full text-left px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5">
                Panduan Pemakaian
            </button>
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold">
                        Keluar
                    </button>
                </form>
            </div>
        @else
            <a href="{{ url('/') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-white/5">
                Beranda
            </a>
            <a href="{{ route('demo.create') }}" class="block px-3 py-2 rounded-xl text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:bg-slate-100 dark:hover:bg-white/5">
                🎪 Mode Booth Tamu
            </a>
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
                <a href="{{ route('login') }}" class="w-1/2 text-center py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/5 rounded-xl">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="w-1/2 text-center py-2 text-xs font-bold text-white bg-blue-600 rounded-xl">
                    Daftar
                </a>
            </div>
        @endauth
    </div>
</nav>
