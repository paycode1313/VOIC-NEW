<x-app-layout>
    <div class="pb-16 pt-4 sm:pt-8">
        
        <!-- 1. Hero Section (AgentVet Inspired Placement) -->
        <div class="pt-6 sm:pt-12 pb-8 text-center max-w-4xl mx-auto px-4">
            <!-- Subtle Centered Eyebrow -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-200/70 dark:bg-white/5 border border-slate-300 dark:border-white/10 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-4 transition-colors">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400 animate-pulse"></span>
                Voice & Optics Intelligent Coach
            </div>

            <!-- Main Headline (Dual-Tone Aesthetic) -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.15] transition-colors">
                Latih bicara yang <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-blue-300 dark:via-indigo-200 dark:to-slate-200">
                    teruji & percaya diri.
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="mt-4 text-xs sm:text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed transition-colors">
                Simulasi tatap muka interaktif dengan analisis kontak mata, kestabilan vokal, dan evaluasi objektif berbasis AI secara langsung.
            </p>

            <!-- Center Search / Quick Launcher Bar -->
            <div class="mt-8 max-w-xl mx-auto">
                <div class="p-1.5 sm:p-2 rounded-2xl bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 backdrop-blur-xl flex items-center justify-between shadow-lg dark:shadow-2xl transition-colors">
                    <div class="flex items-center gap-2 px-2.5 sm:px-3 text-slate-400 text-xs sm:text-sm min-w-0">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span class="text-slate-700 dark:text-slate-300 font-medium truncate">Pilih peran atau mulai simulasi baru...</span>
                    </div>
                    <a href="{{ route('practice.create') }}"
                       class="inline-flex items-center justify-center px-4 sm:px-5 py-2 text-xs font-bold text-white dark:text-slate-900 bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-200 rounded-xl transition shadow-md shrink-0">
                        Mulai Latihan
                    </a>
                </div>

                <!-- Scenario Filter Pills -->
                <div class="mt-3.5 flex items-center justify-center flex-wrap gap-2 text-xs">
                    <a href="{{ route('practice.create', ['role' => 'dosen_penguji']) }}"
                       class="px-3 py-1 rounded-full bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center gap-1.5 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                        Sidang Skripsi
                    </a>
                    <a href="{{ route('practice.create', ['role' => 'hrd']) }}"
                       class="px-3 py-1 rounded-full bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center gap-1.5 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 dark:bg-sky-400"></span>
                        Wawancara Kerja
                    </a>
                    <a href="{{ route('practice.create', ['role' => 'investor']) }}"
                       class="px-3 py-1 rounded-full bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition flex items-center gap-1.5 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                        Pitching Startup
                    </a>
                    <button type="button"
                            @click="$dispatch('open-onboarding')"
                            title="Panduan & Tutorial"
                            class="px-3 py-1 rounded-full bg-white dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer shadow-2xs">
                        Panduan Pemakaian
                        <span class="sr-only">Panduan & Tutorial</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Benchmark Certificate Card (Centered Highlight Card from Reference) -->
        <div class="max-w-4xl mx-auto px-4 mb-8">
            <div class="rounded-3xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 p-6 sm:p-8 backdrop-blur-xl shadow-xl dark:shadow-2xl transition-colors">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-semibold mb-6">
                    <span class="uppercase tracking-wider text-[10px]">Indeks Evaluasi & Kesiapan</span>
                    <span class="font-mono text-slate-400 dark:text-slate-500 text-[11px]">VOIC-2026</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-100 dark:border-white/10">
                    <div class="flex items-baseline gap-4">
                        <span class="text-5xl sm:text-6xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $averageScore > 0 ? number_format($averageScore / 10, 1) : '8.7' }}
                        </span>
                        <div>
                            <span class="inline-block text-xs font-bold px-2.5 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400 mb-1">
                                {{ $averageScore >= 80 ? 'Certified Plus' : ($averageScore >= 65 ? 'Qualified' : ($averageScore > 0 ? 'In Training' : 'Standar Kesiapan')) }}
                            </span>
                            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-xs leading-relaxed">
                                Sertifikat objektif berdasarkan kontak mata kamera (Face-API) dan kestabilan nada bicara.
                            </p>
                        </div>
                    </div>

                    @if($totalSessions > 0)
                        <a href="{{ route('practice.export') }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl transition self-start sm:self-auto">
                            <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Ekspor CSV</span>
                        </a>
                    @endif
                </div>

                <!-- Key Telemetry Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 text-left">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold block">Rata-rata Skor</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white mt-0.5 block">
                            {{ $averageScore > 0 ? number_format($averageScore, 1) : '-' }} <span class="text-xs font-normal text-slate-400 dark:text-slate-500">/ 100</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold block">Kontak Mata</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white mt-0.5 block">
                            {{ $averageEyeScore > 0 ? number_format($averageEyeScore, 1).'%' : '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold block">Skor Vokal</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white mt-0.5 block">
                            {{ $averageVoiceScore > 0 ? number_format($averageVoiceScore, 1) : '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold block">Total Latihan</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white mt-0.5 block">
                            {{ $totalSessions }} <span class="text-xs font-normal text-slate-400 dark:text-slate-500">({{ floor($totalDurationSeconds / 60) }}m)</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 space-y-8">
            
            <!-- DEEP LEARNING ADAPTIVE BRAIN ENGINE CARD -->
            <div class="rounded-3xl bg-gradient-to-br from-blue-50/90 via-white to-indigo-50/50 dark:from-[#0b1629]/95 dark:via-[#0b121e]/90 dark:to-[#0f172a]/80 border border-blue-200/80 dark:border-blue-500/20 p-5 sm:p-6 shadow-md dark:shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-36 h-36 bg-blue-500/10 dark:bg-blue-400/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-blue-100 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-blue-200 dark:border-blue-500/30 shrink-0">
                            🧠
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                                    Otak AI & Continuous Deep Learning
                                </h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Aktif Belajar
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Model terus mempelajari pola percakapan, kelemahan, dan perkembangan argumentasi Anda.
                            </p>
                        </div>
                    </div>

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/80 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs self-start sm:self-auto shadow-2xs">
                        <span class="text-slate-500 dark:text-slate-400">Tingkat Adaptasi:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">Level {{ $deepLearningStats['user_level'] ?? 1 }}</span>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $deepLearningStats['level_label'] ?? 'Active Conversationalist' }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-xs">
                    <div class="p-3 rounded-xl bg-white/70 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold block mb-1">Data Percakapan Dipelajari</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white block font-mono">
                            {{ $deepLearningStats['total_dialogue_turns_learned'] ?? 24 }} <span class="text-xs font-normal text-slate-400">Turn Dialog</span>
                        </span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block">Format Supervised SFT & DPO</span>
                    </div>

                    <div class="p-3 rounded-xl bg-white/70 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold block mb-1">Rata-rata Koherensi Semantik</span>
                        <span class="text-lg font-black text-emerald-600 dark:text-emerald-400 block font-mono">
                            {{ $deepLearningStats['average_coherence_rate'] ?? 88.5 }}%
                        </span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block">Keterikatan Jawaban terhadap Pertanyaan</span>
                    </div>

                    <div class="p-3 rounded-xl bg-white/70 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold block mb-1">Fokus Kalibrasi Otak AI</span>
                        <div class="space-y-1 mt-1">
                            @if(!empty($deepLearningStats['user_weaknesses']))
                                <span class="inline-block text-[11px] font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded-lg border border-rose-200 dark:border-rose-900/40 truncate max-w-full">
                                    Catatan: {{ $deepLearningStats['user_weaknesses'][0] }}
                                </span>
                            @else
                                <span class="inline-block text-[11px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-200 dark:border-blue-900/40">
                                    Fokus: Kontak Mata & Tempo Stabil
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Pilihan Skenario Latihan Cards -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Pilihan Skenario Latihan
                    </h3>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Pilih skenario simulasi</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Skenario 1: Sidang Skripsi -->
                    <div class="p-5 rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b121e]/90 hover:border-blue-500/50 shadow-sm dark:shadow-none transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <img src="{{ asset('images/avatars/dosen.jpg') }}" alt="Sidang Skripsi" width="40" height="40" class="w-10 h-10 rounded-xl object-cover object-top border border-slate-200 dark:border-white/10">
                                <div>
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">Sidang Skripsi</h4>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Dr. Ir. Hendra Gunawan</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                                Uji ketegasan metodologi penelitian, batasan masalah, dan konsistensi tatapan dewan penguji.
                            </p>
                        </div>
                        <a href="{{ route('practice.create', ['role' => 'dosen_penguji']) }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition">
                            Mulai Sidang Skripsi
                        </a>
                    </div>

                    <!-- Skenario 2: Wawancara Kerja -->
                    <div class="p-5 rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b121e]/90 hover:border-sky-500/50 shadow-sm dark:shadow-none transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <img src="{{ asset('images/avatars/hrd.jpg') }}?v=3" alt="Wawancara Kerja" width="40" height="40" class="w-10 h-10 rounded-xl object-cover object-top border border-slate-200 dark:border-white/10">
                                <div>
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">Wawancara Kerja</h4>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Nadia Putri, S.Psi.</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                                Jawab studi kasus metode STAR, latih senyum ramah, dan minimalisir kata gumaman.
                            </p>
                        </div>
                        <a href="{{ route('practice.create', ['role' => 'hrd']) }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition">
                            Mulai Wawancara Kerja
                        </a>
                    </div>

                    <!-- Skenario 3: Pitching Startup -->
                    <div class="p-5 rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b121e]/90 hover:border-indigo-500/50 shadow-sm dark:shadow-none transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <img src="{{ asset('images/avatars/investor.jpg') }}" alt="Pitching Startup" width="40" height="40" class="w-10 h-10 rounded-xl object-cover object-top border border-slate-200 dark:border-white/10">
                                <div>
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">Pitching Startup</h4>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Alexander Wardhana, MBA</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                                Sampaikan elevator pitch bisnis yang meyakinkan, problem-solution fit, dan proyeksi nilai produk.
                            </p>
                        </div>
                        <a href="{{ route('practice.create', ['role' => 'investor']) }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition">
                            Mulai Pitching Startup
                        </a>
                    </div>
                </div>
            </div>

            <!-- 4. Progress Score Chart -->
            <div class="rounded-3xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 p-6 backdrop-blur-xl shadow-xl dark:shadow-2xl transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Grafik Perkembangan Skor
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Tren nilai evaluasi pada 10 sesi latihan terakhir.
                        </p>
                    </div>
                    @if($totalSessions > 0)
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20">
                            Skor Evaluasi (0 - 100)
                        </span>
                    @endif
                </div>

                <div class="relative h-60 w-full">
                    @if($totalSessions > 0)
                        <canvas id="scoreChart"></canvas>
                    @else
                        <div class="h-full flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-200 dark:border-white/10 rounded-2xl">
                            <svg class="w-8 h-8 text-slate-400 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400">Belum ada data grafik</p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Selesaikan sesi latihan pertama untuk memunculkan tren grafik nilai Anda.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 5. Riwayat Sesi Latihan Table -->
            <div id="riwayat-latihan" class="rounded-3xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 overflow-hidden backdrop-blur-xl shadow-xl dark:shadow-2xl transition-colors">
                <div class="p-5 border-b border-slate-100 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Riwayat Sesi Latihan
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Daftar hasil evaluasi latihan Anda.
                        </p>
                    </div>

                    @if($totalSessions > 0)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                {{ $recentSessions->count() }} / {{ $totalSessions }} Sesi
                            </span>
                            <a href="{{ route('practice.history') }}"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition">
                                Lihat Semua
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>

                @if($recentSessions->isEmpty())
                    <div class="p-8 text-center">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-white/5 border border-blue-200 dark:border-white/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Belum Ada Sesi Latihan</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                            Mulai sesi simulasi pertamamu sekarang untuk mendapatkan analisis intonasi suara dan kontak mata secara langsung.
                        </p>
                        <a href="{{ route('practice.create') }}"
                           class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white dark:text-slate-900 bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-200 rounded-xl shadow-sm transition">
                            <span>Mulai Latihan Sekarang</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-white/10 bg-slate-50/80 dark:bg-white/2 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="py-3 px-5">Skenario</th>
                                    <th class="py-3 px-5">Penguji</th>
                                    <th class="py-3 px-5">Waktu</th>
                                    <th class="py-3 px-5">Durasi</th>
                                    <th class="py-3 px-5">Skor Total</th>
                                    <th class="py-3 px-5 text-right">Rapor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-xs">
                                @foreach($recentSessions as $session)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-white/2 transition-colors">
                                        <td class="py-3.5 px-5 font-semibold text-slate-900 dark:text-white">
                                            {{ $session->scenario_type }}
                                        </td>
                                        <td class="py-3.5 px-5 text-slate-600 dark:text-slate-400">
                                            {{ $session->aiRole ? $session->aiRole->name : 'Penguji AI' }}
                                        </td>
                                        <td class="py-3.5 px-5 text-slate-500 dark:text-slate-400">
                                            {{ $session->created_at->format('d M Y, H:i') }}
                                        </td>
                                        <td class="py-3.5 px-5 text-slate-600 dark:text-slate-400 font-mono">
                                            {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                        </td>
                                        <td class="py-3.5 px-5 font-bold">
                                            <span class="{{ $session->overall_score >= 80 ? 'text-blue-600 dark:text-blue-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                                {{ number_format($session->overall_score, 1) }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                            <a href="{{ route('practice.show', $session) }}"
                                               class="inline-flex items-center px-3 py-1 rounded-lg font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-white hover:bg-blue-50 dark:hover:bg-white/5 transition">
                                                <span>Lihat Rapor</span>
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
        <!-- Chart.js Reactive Dark/Light Mode Graph -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('scoreChart');
                if (!ctx) return;

                const labels = @json($chartLabels);
                const scores = @json($chartScores);

                let chartInstance = null;

                function renderChart() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#94a3b8' : '#64748b';
                    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(226, 232, 240, 0.8)';
                    const borderColor = isDark ? '#38bdf8' : '#2563eb';
                    const bgColor = isDark ? 'rgba(56, 189, 248, 0.08)' : 'rgba(37, 99, 235, 0.08)';

                    if (chartInstance) {
                        chartInstance.destroy();
                    }

                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Skor Evaluasi',
                                data: scores,
                                borderColor: borderColor,
                                backgroundColor: bgColor,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: borderColor,
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: isDark ? '#0b121e' : '#0f172a',
                                    titleColor: '#ffffff',
                                    bodyColor: isDark ? '#94a3b8' : '#cbd5e1',
                                    padding: 10,
                                    cornerRadius: 8,
                                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                                    borderWidth: 1,
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
                                        stepSize: 25,
                                        color: textColor,
                                        font: { family: 'Figtree', size: 11 }
                                    },
                                    grid: { color: gridColor }
                                },
                                x: {
                                    ticks: {
                                        color: textColor,
                                        font: { family: 'Figtree', size: 11 }
                                    },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                renderChart();
                window.addEventListener('voic-theme-changed', renderChart);
            });
        </script>
    @endif
</x-app-layout>
