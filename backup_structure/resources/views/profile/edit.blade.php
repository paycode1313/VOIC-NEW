<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-800/80 text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Kredensial & Pengaturan Profil Akun
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ __('Pengaturan Akun & Keamanan') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Kelola informasi identitas akun, perbarui kata sandi, dan preferensi privasi Anda.
                </p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-[#0f1c2f] border border-slate-300 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs w-fit">
                <svg class="w-4 h-4 me-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Executive User Profile Summary Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#080f1b] via-[#0f243e] to-[#1e3a5f] p-6 sm:p-8 text-white shadow-xl shadow-blue-950/20 border border-blue-900/40">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <!-- User Avatar Initials Badge -->
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-gradient-to-tr from-blue-600 to-sky-400 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-lg ring-4 ring-white/10 shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>

                    <div class="flex-1 text-center sm:text-left space-y-2">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                {{ Auth::user()->name }}
                            </h3>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Peserta Aktif
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-blue-200 font-mono">
                            {{ Auth::user()->email }}
                        </p>

                        <div class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-blue-200/80">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Bergabung: {{ Auth::user()->created_at->format('d M Y') }}
                            </span>
                            <span>•</span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ Auth::user()->practiceSessions()->count() }} Sesi Latihan Terselesaikan
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Grid -->
            <div class="space-y-6">
                <!-- Section 1: Profile Information -->
                <div class="p-6 sm:p-8 bg-white dark:bg-[#0f1c2f] rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition hover:border-blue-500/30">
                    <div class="max-w-2xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <!-- Section 2: Update Password -->
                <div class="p-6 sm:p-8 bg-white dark:bg-[#0f1c2f] rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition hover:border-blue-500/30">
                    <div class="max-w-2xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <!-- Section 3: Delete Account (Danger Zone) -->
                <div class="p-6 sm:p-8 bg-white dark:bg-[#0f1c2f] rounded-3xl shadow-xs border border-rose-200/70 dark:border-rose-950/80 transition hover:border-rose-500/40">
                    <div class="max-w-2xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
