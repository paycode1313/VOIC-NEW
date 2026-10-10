<!-- Mobile Backdrop Overlay -->
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs lg:hidden print:hidden"
     x-cloak></div>

<!-- Sidebar Navigation Drawer -->
<aside class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-white/95 dark:bg-[#091122]/95 backdrop-blur-2xl border-r border-slate-200/80 dark:border-slate-800/80 shadow-2xl lg:shadow-none transition-transform duration-300 ease-in-out print:hidden"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       x-cloak>

    <!-- Sidebar Header: Brand Logo & Mobile Close -->
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-200/80 dark:border-slate-800/80 shrink-0">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 group">
            <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-blue-700 via-blue-600 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-blue-600/25 ring-2 ring-blue-500/20 group-hover:scale-105 transition-all">
                <span class="font-black text-base tracking-tighter">V</span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-center gap-1.5">
                    <span class="text-base font-black tracking-tight text-slate-900 dark:text-white leading-none">
                        VOIC
                    </span>
                    <span class="px-1.5 py-0.2 rounded-md bg-blue-500/15 text-blue-600 dark:text-blue-400 font-extrabold text-[10px] border border-blue-500/30">
                        AI
                    </span>
                </div>
                <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-wider uppercase mt-1">
                    Multimodal Coach
                </span>
            </div>
        </a>

        <!-- Mobile Close Button -->
        <button type="button"
                @click="sidebarOpen = false"
                class="lg:hidden p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                title="Tutup Menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Scrollable Navigation Area -->
    <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">

        <!-- Navigation Group: Menu Utama -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                Menu Utama
            </div>
            <nav class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white hover:bg-blue-50/70 dark:hover:bg-slate-800/70' }}">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard Analisis</span>
                </a>

                <!-- Mulai Simulasi -->
                <a href="{{ route('practice.create') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group {{ request()->routeIs('practice.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white hover:bg-blue-50/70 dark:hover:bg-slate-800/70' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('practice.*') ? 'text-white' : 'text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span>Ruang Simulasi</span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-bold {{ request()->routeIs('practice.*') ? 'bg-white/20 text-white' : 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live
                    </span>
                </a>

                <!-- Riwayat Sesi -->
                <a href="{{ route('dashboard') }}#riwayat-latihan"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white hover:bg-blue-50/70 dark:hover:bg-slate-800/70 transition-all group">
                    <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Riwayat Sesi</span>
                </a>

                <!-- Leaderboard -->
                <a href="{{ route('dashboard') }}#leaderboard-booth"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-amber-500 dark:hover:text-amber-400 hover:bg-amber-50/70 dark:hover:bg-slate-800/70 transition-all group">
                    <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-amber-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <span>Papan Prestasi</span>
                </a>

                <!-- Panduan & Tutorial Pemakaian -->
                <button type="button"
                        @click="$dispatch('open-onboarding'); sidebarOpen = false;"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white hover:bg-blue-50/70 dark:hover:bg-slate-800/70 transition-all group cursor-pointer text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Panduan & Tutorial</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900">
                        Help
                    </span>
                </button>
            </nav>
        </div>

    </div>

    <!-- Sidebar Footer: Mode Tampilan & Akun Pengguna -->
    <div class="p-3 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/60 dark:bg-[#070e1c]/60 shrink-0 space-y-2">
        <!-- Theme Toggle Switch -->
        <button type="button"
                onclick="
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                "
                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800/80 transition cursor-pointer shadow-2xs">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="w-4 h-4 block dark:hidden text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span>Tema Tampilan</span>
            </span>
            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">
                <span class="block dark:hidden">Terang</span>
                <span class="hidden dark:block">Gelap</span>
            </span>
        </button>

        <!-- User Profile Card -->
        <div class="p-2.5 rounded-xl bg-white dark:bg-[#0b1424] border border-slate-200/80 dark:border-slate-800/80 shadow-2xs flex items-center justify-between gap-2">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 min-w-0 flex-1 group">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                        {{ Auth::user()->name }}
                    </div>
                    <div class="text-[10px] text-slate-400 truncate">
                        {{ Auth::user()->email }}
                    </div>
                </div>
            </a>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        title="Keluar (Log Out)"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
