<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-800/80 text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Platform Simulasi & Evaluasi Sidang
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ __('Dashboard Analisis') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Pantau perkembangan performa public speaking, kontak mata, kestabilan intonasi, dan pencapaian kompetensi.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button"
                        @click="$dispatch('open-onboarding')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-blue-700 dark:text-blue-300 bg-blue-50/80 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/60 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 me-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Panduan Pemakaian
                </button>
                <a href="#leaderboard-booth"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-[#0f1c2f] border border-slate-300 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs">
                    <svg class="w-4 h-4 me-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Papan Peringkat
                </a>
                <a href="#riwayat-latihan"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-[#0f1c2f] border border-slate-300 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs">
                    <svg class="w-4 h-4 me-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Riwayat Sesi
                </a>
                <a href="{{ route('practice.export') }}"
                   class="hidden sm:inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-[#0f1c2f] border border-slate-300 dark:border-slate-700/80 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-xs">
                    <svg class="w-4 h-4 me-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Ekspor CSV
                </a>
                <a href="{{ route('practice.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-md shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Mulai Latihan Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Executive Navy Welcome Banner with Readiness Telemetry Dial -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#080f1b] via-[#0f243e] to-[#1e3a5f] p-6 sm:p-8 text-white shadow-xl shadow-blue-950/20 border border-blue-900/40">
                <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-semibold text-blue-200 border border-white/15">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Simulasi & Evaluasi Terstruktur
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                            Halo, {{ Auth::user()->name }}
                        </h1>
                        <p class="mt-2.5 text-blue-100 text-sm sm:text-base leading-relaxed max-w-2xl font-normal">
                            Selamat datang di <strong class="text-white font-semibold">VOIC (Voice & Optics Intelligent Coach)</strong>. Platform evaluasi terpadu untuk latihan presentasi sidang tugas akhir, wawancara kerja, dan pitching profesional dengan analisis vokal serta kontak mata secara langsung.
                        </p>

                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <a href="{{ route('practice.create') }}"
                               class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-bold text-slate-900 bg-white hover:bg-slate-100 shadow-md transition transform hover:-translate-y-0.5">
                                <span>Buka Ruang Simulasi</span>
                                <svg class="w-4 h-4 ms-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                            <button type="button"
                                    @click="$dispatch('open-onboarding')"
                                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-semibold text-white/90 bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md shadow-xs transition transform hover:-translate-y-0.5 cursor-pointer">
                                <svg class="w-4 h-4 me-1.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Panduan & Tutorial</span>
                            </button>
                            <span class="text-xs text-blue-200/80 hidden sm:inline-block">
                                • {{ $totalSessions }} sesi terselesaikan • {{ $unlockedTotal }}/{{ $totalMasterBadges }} capaian kompetensi
                            </span>
                        </div>
                    </div>

                    <!-- Readiness Telemetry Gauge -->
                    <div class="hidden lg:flex items-center gap-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/15 shrink-0 shadow-lg">
                        <div class="relative w-16 h-16 flex items-center justify-center">
                            <svg class="w-16 h-16 -rotate-90 transform" viewBox="0 0 36 36">
                                <path class="text-white/20" stroke-width="3.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="text-emerald-400" stroke-width="3.5" stroke-dasharray="{{ min(round($averageScore), 100) }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <div class="absolute text-center">
                                <span class="text-sm font-black text-white">{{ $averageScore > 0 ? round($averageScore) : 0 }}%</span>
                            </div>
                        </div>
                        <div class="text-left space-y-0.5">
                            <span class="text-[10px] uppercase font-bold text-blue-200 tracking-wider">Indeks Kesiapan</span>
                            <span class="block text-xs font-bold text-white">{{ $averageScore >= 80 ? 'Siap Sidang Teruji' : ($averageScore >= 60 ? 'Progres Stabil' : 'Perlu Tambah Latihan') }}</span>
                            <span class="block text-[10px] text-blue-200/80">Telemetri 30 FPS Edge CV</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Metric Cards Grid with Executive Depth -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Average Score Card -->
                <div class="relative overflow-hidden bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition-all duration-300 hover:shadow-xl hover:border-blue-500/40 group">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata Skor Total</span>
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/80 border border-blue-200/60 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2 relative z-10">
                        <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $averageScore > 0 ? number_format($averageScore, 1) : '-' }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">/ 100</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between relative z-10">
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full {{ $averageScore >= 80 ? 'bg-emerald-500 animate-pulse' : ($averageScore >= 65 ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                            {{ $averageScore >= 80 ? 'Predikat: Sangat Baik' : ($averageScore >= 65 ? 'Predikat: Cukup Baik' : 'Predikat: Perlu Latihan') }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 border border-blue-200/50 dark:border-blue-800/50">Target 80+</span>
                    </div>
                </div>

                <!-- Optic Face Score Card -->
                <div class="relative overflow-hidden bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition-all duration-300 hover:shadow-xl hover:border-sky-500/40 group">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-sky-500/10 rounded-full blur-2xl group-hover:bg-sky-500/20 transition-all"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata Kontak Mata</span>
                        <div class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200/60 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2 relative z-10">
                        <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $averageEyeScore > 0 ? number_format($averageEyeScore, 1) : '-' }}%
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">tatapan stabil</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between relative z-10">
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full {{ $averageEyeScore >= 70 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            {{ $averageEyeScore >= 70 ? 'Fokus Kamera Prima' : 'Kurang Menatap Kamera' }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 border border-sky-200/50 dark:border-sky-800/50">Edge CV 30 FPS</span>
                    </div>
                </div>

                <!-- Voice Score Card -->
                <div class="relative overflow-hidden bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition-all duration-300 hover:shadow-xl hover:border-indigo-500/40 group">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rata-rata Vokal</span>
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200/60 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2 relative z-10">
                        <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $averageVoiceScore > 0 ? number_format($averageVoiceScore, 1) : '-' }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">artikulasi</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between relative z-10">
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full {{ $averageVoiceScore >= 75 ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            {{ $averageVoiceScore >= 75 ? 'Tempo & Nada Jernih' : 'Variasi Nada Stabil' }}
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/50">DSP Pitch</span>
                    </div>
                </div>

                <!-- Total Sessions Card -->
                <div class="relative overflow-hidden bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 transition-all duration-300 hover:shadow-xl hover:border-emerald-500/40 group">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Sesi Latihan</span>
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2 relative z-10">
                        <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $totalSessions }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Sesi Selesai</span>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between relative z-10">
                        @php
                            $minutes = floor($totalDurationSeconds / 60);
                            $seconds = $totalDurationSeconds % 60;
                        @endphp
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                            Durasi: <strong class="text-slate-700 dark:text-slate-200">{{ $minutes }}m {{ $seconds }}s</strong>
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Trophy Showcase: Galeri Lencana Prestasi -->
            <div class="bg-white dark:bg-[#0f1c2f] p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800/80 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                                Galeri Lencana & Trofi Prestasi
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                {{ $unlockedTotal }} / {{ $totalMasterBadges }} Terbuka
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Lencana pencapaian yang diraih berdasarkan konsistensi kontak mata, artikulasi vokal, dan ketenangan simulasi.
                        </p>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        Tingkat Kelengkapan: <strong class="text-blue-600 dark:text-blue-400">{{ round(($unlockedTotal / max($totalMasterBadges, 1)) * 100) }}%</strong>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
                    @foreach($badgeShowcase as $b)
                        <div class="p-4 rounded-2xl flex flex-col items-center text-center transition-all {{ $b['unlocked'] ? 'bg-gradient-to-b from-blue-50/70 to-white dark:from-blue-950/40 dark:to-[#080f1b] border-2 border-blue-500/30 dark:border-blue-500/40 shadow-xs hover:scale-105' : 'bg-slate-50/60 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-800 opacity-60' }}">
                            <div class="relative mb-2">
                                <div class="w-12 h-12 rounded-2xl {{ $b['unlocked'] ? 'bg-blue-100/80 dark:bg-blue-900/60 text-blue-600 dark:text-blue-300 shadow-xs' : 'bg-slate-200/60 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center font-bold">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                                @if($b['unlocked'] && $b['count'] > 1)
                                    <span class="absolute -top-1 -right-2 px-1.5 py-0.2 rounded-full text-[9px] font-black bg-blue-600 text-white shadow-xs">
                                        {{ $b['count'] }}x
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white leading-tight mb-1">
                                {{ $b['name'] }}
                            </span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">
                                {{ $b['desc'] }}
                            </span>
                            <div class="mt-2">
                                @if($b['unlocked'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Terbuka
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-slate-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Terkunci
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Start Practice Scenarios Grid -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span>Pilihan Skenario Simulasi</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Pilih skenario yang ingin kamu latih hari ini dan hadapi panelis evaluasi secara langsung
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Scenario 1: Sidang Skripsi -->
                    <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-blue-500/50 hover:shadow-lg transition flex flex-col justify-between group overflow-hidden">
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-12 h-12 rounded-2xl overflow-hidden shadow-xs border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900">
                                        <img src="{{ asset('images/avatars/dosen.jpg') }}" alt="Dr. Ir. Hendra Gunawan" width="48" height="48" loading="lazy" decoding="async" class="w-full h-full object-cover object-top group-hover:scale-110 transition duration-300">
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">Sidang Skripsi</h4>
                                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block -mt-0.5">Dr. Ir. Hendra Gunawan</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Akademik</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Latih presentasi tugas akhir bersama dewan penguji. Kritis menguji metodologi bab 3, batasan masalah, dan konsistensi tatapan ke kamera.
                            </p>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Metodologi</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Dasar Teori</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">Sulit</span>
                            </div>
                        </div>
                        <div class="p-6 pt-0">
                            <a href="{{ route('practice.create', ['role' => 'dosen_penguji']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow-xs group-hover:shadow-md">
                                Latih Sidang Skripsi Sekarang →
                            </a>
                        </div>
                    </div>

                    <!-- Scenario 2: Wawancara Kerja -->
                    <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-sky-500/50 hover:shadow-lg transition flex flex-col justify-between group overflow-hidden">
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-12 h-12 rounded-2xl overflow-hidden shadow-xs border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900">
                                        <img src="{{ asset('images/avatars/hrd.jpg') }}?v=3" alt="Arya Pratama, S.Psi., CHRP" width="48" height="48" loading="lazy" decoding="async" class="w-full h-full object-cover object-top group-hover:scale-110 transition duration-300">
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">Wawancara Kerja</h4>
                                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block -mt-0.5">Arya Pratama, S.Psi., CHRP</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-300 border border-sky-200 dark:border-sky-800">Karier</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Jawab pertanyaan behavioral dengan metode STAR. Fokus pada ekspresi senyum, ketenangan emosional, dan eliminasi kata gumaman (filler words).
                            </p>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Metode STAR</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Culture Fit</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">Sedang</span>
                            </div>
                        </div>
                        <div class="p-6 pt-0">
                            <a href="{{ route('practice.create', ['role' => 'hrd']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition shadow-xs group-hover:shadow-md">
                                Latih Interview HRD Sekarang →
                            </a>
                        </div>
                    </div>

                    <!-- Scenario 3: Pitching Startup -->
                    <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200/80 dark:border-slate-800 shadow-xs hover:border-blue-500/50 hover:shadow-lg transition flex flex-col justify-between group overflow-hidden">
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-12 h-12 rounded-2xl overflow-hidden shadow-xs border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900">
                                        <img src="{{ asset('images/avatars/investor.jpg') }}" alt="Alexander Wardhana, MBA" width="48" height="48" loading="lazy" decoding="async" class="w-full h-full object-cover object-top group-hover:scale-110 transition duration-300">
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">Pitching Startup</h4>
                                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block -mt-0.5">Alexander Wardhana, MBA</span>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Bisnis</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Sampaikan elevator pitch di hadapan calon investor. Menguji problem-solution fit, Customer Acquisition Cost (CAC), dan keunggulan parit pertahanan.
                            </p>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Unit Economics</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Moat Pertahanan</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">Sedang</span>
                            </div>
                        </div>
                        <div class="p-6 pt-0">
                            <a href="{{ route('practice.create', ['role' => 'investor']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-[#0f243e] hover:bg-[#1b3a63] border border-blue-900/60 rounded-xl transition shadow-xs group-hover:shadow-md">
                                Latih Pitching Investor Sekarang →
                            </a>
                        </div>
                    </div>
                </div>    </div>
            </div>

            <!-- Leaderboard / Booth Hall of Fame Section -->
            <div id="leaderboard-booth" class="bg-white dark:bg-[#0f1c2f] rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-6 sm:p-7 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-500 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                                    Papan Peringkat Terbaik
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    Sinkronisasi Langsung
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Peringkat skor evaluasi tertinggi berdasarkan kontak mata, kejelasan artikulasi, dan pemahaman materi.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('practice.create') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                        <span>Uji Kemampuan Sekarang</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

                @if($leaderboardSessions->count() >= 3)
                    @php
                        $first = $leaderboardSessions[0];
                        $second = $leaderboardSessions[1];
                        $third = $leaderboardSessions[2];
                    @endphp
                    <!-- Visual Top-3 Podium with Trophy Aesthetics -->
                    <div class="px-6 pt-6 pb-6 bg-gradient-to-b from-slate-50/70 to-white dark:from-[#0a1424] dark:to-[#0f1c2f] border-b border-slate-100 dark:border-slate-800">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto items-end pt-2">
                            <!-- 2nd Place (Silver) -->
                            <div class="order-2 md:order-1 relative rounded-2xl p-5 bg-gradient-to-b from-slate-100/90 to-white dark:from-slate-800/90 dark:to-slate-900 border border-slate-300 dark:border-slate-700 shadow-sm text-center flex flex-col items-center">
                                <div class="absolute -top-3.5 w-7 h-7 rounded-full bg-slate-400 text-white font-black text-xs flex items-center justify-center shadow-md ring-2 ring-white dark:ring-slate-800">
                                    2
                                </div>
                                <div class="w-14 h-14 mt-1 rounded-2xl bg-gradient-to-tr from-slate-300 to-slate-100 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center font-black text-slate-700 dark:text-slate-200 text-lg shadow-inner">
                                    {{ strtoupper(substr($second->user?->name ?? 'P', 0, 2)) }}
                                </div>
                                <h4 class="mt-2.5 font-bold text-sm text-slate-900 dark:text-white truncate max-w-[180px]">
                                    {{ $second->user ? $second->user->name : 'Peserta Demo' }}
                                </h4>
                                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $second->scenario_type }}</span>
                                <div class="mt-3 px-3 py-1 rounded-xl bg-slate-200/70 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-black">
                                    {{ number_format($second->overall_score, 1) }} <span class="font-normal text-[10px] text-slate-500">Skor</span>
                                </div>
                            </div>

                            <!-- 1st Place (Gold - Elevated) -->
                            <div class="order-1 md:order-2 relative rounded-2xl p-6 bg-gradient-to-b from-amber-500/10 via-amber-500/5 to-white dark:from-amber-500/20 dark:via-[#14233c] dark:to-[#0f1c2f] border-2 border-amber-400/80 dark:border-amber-400/60 shadow-xl text-center flex flex-col items-center -translate-y-2">
                                <div class="absolute -top-4 w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white font-black text-sm flex items-center justify-center shadow-lg ring-4 ring-white dark:ring-[#0f1c2f]">
                                    👑 1
                                </div>
                                <div class="w-16 h-16 mt-2 rounded-2xl bg-gradient-to-tr from-amber-400 to-yellow-200 text-amber-950 flex items-center justify-center font-black text-xl shadow-md ring-2 ring-amber-400/50">
                                    {{ strtoupper(substr($first->user?->name ?? 'P', 0, 2)) }}
                                </div>
                                <h4 class="mt-3 font-black text-base text-slate-900 dark:text-white truncate max-w-[200px]">
                                    {{ $first->user ? $first->user->name : 'Peserta Demo' }}
                                </h4>
                                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">{{ $first->scenario_type }}</span>
                                <div class="mt-3.5 px-4 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 text-sm font-black shadow-md shadow-amber-500/20">
                                    {{ number_format($first->overall_score, 1) }} <span class="text-xs font-bold text-slate-900">Skor Tertinggi</span>
                                </div>
                            </div>

                            <!-- 3rd Place (Bronze) -->
                            <div class="order-3 relative rounded-2xl p-5 bg-gradient-to-b from-amber-900/10 to-white dark:from-amber-950/40 dark:to-slate-900 border border-amber-800/30 dark:border-amber-700/40 shadow-sm text-center flex flex-col items-center">
                                <div class="absolute -top-3.5 w-7 h-7 rounded-full bg-amber-700 text-white font-black text-xs flex items-center justify-center shadow-md ring-2 ring-white dark:ring-slate-800">
                                    3
                                </div>
                                <div class="w-14 h-14 mt-1 rounded-2xl bg-gradient-to-tr from-amber-700 to-amber-500 text-white flex items-center justify-center font-black text-lg shadow-inner">
                                    {{ strtoupper(substr($third->user?->name ?? 'P', 0, 2)) }}
                                </div>
                                <h4 class="mt-2.5 font-bold text-sm text-slate-900 dark:text-white truncate max-w-[180px]">
                                    {{ $third->user ? $third->user->name : 'Peserta Demo' }}
                                </h4>
                                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $third->scenario_type }}</span>
                                <div class="mt-3 px-3 py-1 rounded-xl bg-amber-800/10 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs font-black">
                                    {{ number_format($third->overall_score, 1) }} <span class="font-normal text-[10px] text-slate-500">Skor</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Rank</th>
                                <th class="py-3.5 px-6">Nama Peserta</th>
                                <th class="py-3.5 px-6">Skenario Ujian</th>
                                <th class="py-3.5 px-6">Kontak Mata</th>
                                <th class="py-3.5 px-6">Vokal</th>
                                <th class="py-3.5 px-6 text-center">Skor Total</th>
                                <th class="py-3.5 px-6 text-right">Capaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                            @forelse($leaderboardSessions as $index => $ls)
                                @php
                                    $isMe = ($ls->user_id === Auth::id());
                                    $notes = is_array($ls->feedback_notes) ? $ls->feedback_notes : [];
                                    $bList = $notes['badges'] ?? [];
                                @endphp
                                <tr class="transition-colors {{ $isMe ? 'bg-blue-50/60 dark:bg-blue-950/30' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40' }}">
                                    <td class="py-4 px-6 font-bold whitespace-nowrap">
                                        @if($index === 0)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold border border-amber-500/30 shadow-xs">1</span>
                                        @elseif($index === 1)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-slate-500/10 text-slate-600 dark:text-slate-300 text-xs font-bold border border-slate-500/30 shadow-xs">2</span>
                                        @elseif($index === 2)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-amber-800/10 text-amber-700 dark:text-amber-500 text-xs font-bold border border-amber-800/30 shadow-xs">3</span>
                                        @else
                                            <span class="text-slate-400 font-bold ms-2">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $ls->user ? $ls->user->name : 'Peserta Demo' }}</span>
                                            @if($isMe)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-600 text-white">Anda</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-300">
                                        <span class="font-semibold">{{ $ls->scenario_type }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $ls->face_score ? number_format($ls->face_score, 1).'%' : '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $ls->voice_score ? number_format($ls->voice_score, 1) : '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="text-base font-black text-blue-600 dark:text-blue-400">
                                            {{ number_format($ls->overall_score, 1) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-1 flex-wrap">
                                            @foreach(array_slice($bList, 0, 3) as $bk)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80" title="{{ $bk }}">
                                                    {{ str_replace('_', ' ', ucwords($bk, '_')) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-slate-400">Belum ada skor latihan terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Chart & Analytics Overview Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Progress Line Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                                Grafik Perkembangan Skor Anda
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Tren skor keseluruhan (Overall Score) pada 10 sesi latihan terakhir
                            </p>
                        </div>
                        <div class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1 bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 rounded-lg">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Skor Evaluasi AI
                        </div>
                    </div>

                    <div class="relative h-72 w-full">
                        @if($totalSessions > 0)
                            <canvas id="scoreChart"></canvas>
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                                <svg class="w-12 h-12 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Belum ada data latihan</p>
                                <p class="text-xs text-slate-400 mt-1">Selesaikan minimal satu sesi latihan untuk melihat visualisasi grafik performa.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- AI Parameters Breakdown / Tips Card -->
                <div class="bg-white dark:bg-[#0f1c2f] p-6 rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">
                            Parameter Analisis AI
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                            Aspek penilaian cerdas yang dievaluasi selama sesi latihan
                        </p>

                        <div class="space-y-3.5">
                            <!-- Eye Contact -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Kontak Mata (Eye Contact)
                                    </span>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">Optics AI</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Mendeteksi fokus pandangan ke kamera untuk menjaga engagement audiens.
                                </p>
                            </div>

                            <!-- Facial Expression -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Ekspresi Wajah & Senyum
                                    </span>
                                    <span class="text-sky-600 dark:text-sky-400 font-bold">Face-API</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Mengukur tingkat senyum dan rileks vs tegang saat menyampaikan materi.
                                </p>
                            </div>

                            <!-- Speech Pace & Clarity -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                        </svg>
                                        Tempo & Artikulasi
                                    </span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">Audio Voice</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Menghitung kata per menit (WPM ideal 110-150) dan kejelasan intonasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3.5 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <strong>Panduan Kalibrasi:</strong> Lakukan uji pencahayaan kamera dan mikrofon sebelum simulasi dimulai agar deteksi wajah dan kejernihan audio terukur optimal.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions Section -->
            <div id="riwayat-latihan" class="bg-white dark:bg-[#0f1c2f] rounded-3xl shadow-xs border border-slate-200/80 dark:border-slate-800 overflow-hidden">
                <div class="p-6 sm:p-7 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                            Riwayat Sesi Latihan Pribadi
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Daftar lengkap sesi latihan terakhir yang tersimpan di akun Anda
                        </p>
                    </div>

                    @if($totalSessions > 0)
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                            Menampilkan {{ $recentSessions->count() }} dari {{ $totalSessions }} sesi
                        </span>
                    @endif
                </div>

                @if($recentSessions->isEmpty())
                    <div class="p-8 sm:p-12">
                        <div class="max-w-2xl mx-auto text-center mb-8">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                🚀 Misi Awal Pemula
                            </span>
                            <h4 class="text-xl font-black text-slate-900 dark:text-white mt-2.5">Mulai Langkah Pertama Menjadi Presenter Handal</h4>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Belum ada riwayat sesi yang tercatat. Selesaikan misi kilat 3 langkah berikut untuk membuka analitik performa dan lencana pertamamu.
                            </p>
                        </div>

                        <!-- 3 Step Quest Road -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto mb-8">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-left">
                                <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-black text-xs flex items-center justify-center mb-3">
                                    01
                                </div>
                                <h5 class="font-bold text-xs text-slate-900 dark:text-white">Pilih Skenario</h5>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Tentukan apakah ingin latihan Sidang Skripsi, Job Interview, atau Elevator Pitch.
                                </p>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-left">
                                <div class="w-8 h-8 rounded-xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 font-black text-xs flex items-center justify-center mb-3">
                                    02
                                </div>
                                <h5 class="font-bold text-xs text-slate-900 dark:text-white">Simulasi Interaktif</h5>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Bicara dengan penguji AI sembari sistem memantau kontak mata & nada suaramu.
                                </p>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-left">
                                <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 font-black text-xs flex items-center justify-center mb-3">
                                    03
                                </div>
                                <h5 class="font-bold text-xs text-slate-900 dark:text-white">Rapor & QR Verifikasi</h5>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Dapatkan rapor evaluasi instan, lencana prestasi, dan sertifikat digital terverifikasi.
                                </p>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('practice.create') }}"
                               class="inline-flex items-center gap-2 px-6 py-3 text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-2xl shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0">
                                <span>Mulai Misi Perdana Sekarang</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="py-3.5 px-6">Skenario / Topik</th>
                                    <th class="py-3.5 px-6">Penguji AI</th>
                                    <th class="py-3.5 px-6">Tanggal & Waktu</th>
                                    <th class="py-3.5 px-6">Durasi</th>
                                    <th class="py-3.5 px-6">Skor AI</th>
                                    <th class="py-3.5 px-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                @foreach($recentSessions as $session)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="py-4 px-6 font-medium text-slate-900 dark:text-white">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/70 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 border border-blue-200/60 dark:border-blue-800/60">
                                                    @if(str_contains($session->scenario_type, 'Skripsi'))
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5"/>
                                                        </svg>
                                                    @elseif(str_contains($session->scenario_type, 'Pitching'))
                                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                                        </svg>
                                                    @elseif(str_contains($session->scenario_type, 'Wawancara'))
                                                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 dark:text-white">{{ $session->scenario_type }}</div>
                                                    <span class="text-xs text-slate-400">Sesi #{{ $session->id }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-slate-700 dark:text-slate-300">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $session->aiRole?->role_type === 'dosen_penguji' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : ($session->aiRole?->role_type === 'hrd' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700') }}">
                                                {{ $session->aiRole ? $session->aiRole->name : 'Evaluator AI' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-slate-600 dark:text-slate-300">
                                            <div class="font-semibold">{{ $session->created_at->format('d M Y') }}</div>
                                            <div class="text-xs text-slate-400">{{ $session->created_at->format('H:i') }} WIB</div>
                                        </td>
                                        <td class="py-4 px-6 text-slate-600 dark:text-slate-300">
                                            <span class="inline-flex items-center gap-1 font-mono text-xs px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-base {{ $session->overall_score >= 80 ? 'text-blue-600 dark:text-blue-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                                    {{ number_format($session->overall_score, 1) }}
                                                </span>
                                                <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $session->overall_score >= 80 ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : ($session->overall_score >= 65 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800') }}">
                                                    {{ $session->overall_score >= 80 ? 'Hebat' : ($session->overall_score >= 65 ? 'Standar' : 'Perlu Evaluasi') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-right whitespace-nowrap">
                                            <a href="{{ route('practice.show', $session) }}"
                                               class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900 border border-blue-200 dark:border-blue-800 transition shadow-xs">
                                                <span>Lihat Rapor & QR</span>
                                                <svg class="w-3.5 h-3.5 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

    @if($totalSessions > 0)
        <!-- Chart.js Script Initialization in Navy Blue Theme -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('scoreChart');
                if (!ctx) return;

                const labels = @json($chartLabels);
                const scores = @json($chartScores);

                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#94a3b8' : '#64748b';
                const gridColor = isDark ? 'rgba(30, 41, 59, 0.6)' : 'rgba(226, 232, 240, 0.8)';

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Skor Total Evaluasi',
                            data: scores,
                            borderColor: '#2563eb', // Royal Navy Blue
                            backgroundColor: 'rgba(37, 99, 235, 0.12)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#1d4ed8',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#1d4ed8',
                            pointHoverBorderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#0b192c' : '#0f172a',
                                titleColor: '#ffffff',
                                bodyColor: '#cbd5e1',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label: function(context) {
                                        return ' Skor: ' + context.parsed.y + ' / 100';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    stepSize: 20,
                                    color: textColor,
                                    font: { family: 'Figtree', size: 11 }
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            x: {
                                ticks: {
                                    color: textColor,
                                    font: { family: 'Figtree', size: 11 }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
