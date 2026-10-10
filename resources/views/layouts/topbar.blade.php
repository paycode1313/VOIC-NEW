<header class="h-16 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8 bg-white/85 dark:bg-[#0b1424]/85 backdrop-blur-xl border-b border-slate-200/80 dark:border-slate-800/80 print:hidden transition-colors">

    <!-- Left: Mobile Hamburger & Context Breadcrumb -->
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Open Toggle -->
        <button type="button"
                @click="sidebarOpen = true"
                class="lg:hidden p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                title="Buka Navigasi">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Mobile Brand Logo (Visible only on mobile/tablet) -->
        <div class="flex lg:hidden items-center gap-2">
            <div class="h-8 w-8 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-sky-500 flex items-center justify-center text-white shadow-xs">
                <span class="font-black text-sm">V</span>
            </div>
            <span class="font-black text-sm tracking-tight text-slate-900 dark:text-white">VOIC</span>
        </div>

        <!-- Desktop Context Indicator / Breadcrumb -->
        <div class="hidden sm:flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 text-slate-400 dark:text-slate-500 font-medium">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Portal
            </span>
            <span class="text-slate-300 dark:text-slate-700">/</span>
            <span class="font-bold text-slate-800 dark:text-slate-100 bg-slate-100 dark:bg-slate-800/70 px-2.5 py-0.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                @if(request()->routeIs('dashboard'))
                    Dashboard Analisis
                @elseif(request()->routeIs('practice.create'))
                    Ruang Simulasi Tatap Muka
                @elseif(request()->routeIs('practice.show'))
                    Laporan Evaluasi Performa
                @elseif(request()->routeIs('profile.*'))
                    Pengaturan Profil Akun
                @else
                    Platform Sesi
                @endif
            </span>
        </div>
    </div>

    <!-- Right: Quick Actions, Theme Toggle & User Dropdown -->
    <div class="flex items-center gap-2 sm:gap-3">

        <!-- Quick "Mulai Sesi Baru" CTA button (hidden if already on practice.create) -->
        @if(!request()->routeIs('practice.create'))
            <a href="{{ route('practice.create') }}"
               class="hidden md:inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 rounded-xl shadow-md shadow-blue-600/20 transition transform hover:-translate-y-0.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Mulai Simulasi</span>
            </a>
        @endif

        <!-- Dark / Light Theme Toggle Button -->
        <button type="button"
                onclick="
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                "
                class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800/80 transition cursor-pointer"
                title="Ganti Mode Tampilan (Gelap / Terang)">
            <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="w-4 h-4 block dark:hidden text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>

        <!-- User Dropdown Menu -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 px-2.5 py-1.5 border border-slate-200/90 dark:border-slate-800 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-white/95 dark:bg-[#0f1d32] hover:bg-slate-50 dark:hover:bg-slate-800/80 focus:outline-none transition shadow-2xs cursor-pointer">
                    <span class="w-6 h-6 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-[10px] flex items-center justify-center shadow-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="max-w-[120px] truncate font-bold">{{ Auth::user()->name }}</span>

                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800 text-xs">
                    <div class="font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
                    <div class="text-[10px] text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</div>
                </div>

                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profil Akun') }}
                </x-dropdown-link>

                <x-dropdown-link :href="route('dashboard')">
                    {{ __('Dashboard') }}
                </x-dropdown-link>

                <div class="border-t border-slate-100 dark:border-slate-800"></div>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-rose-600 dark:text-rose-400">
                        {{ __('Keluar (Log Out)') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
