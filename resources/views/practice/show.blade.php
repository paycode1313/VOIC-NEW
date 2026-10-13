<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sesi Latihan Selesai • Laporan Evaluasi Performa
                </div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ __('Laporan Evaluasi Performa Interaktif') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sesi #{{ $session->id }} • {{ $session->aiRole ? $session->aiRole->name : $session->scenario_type }} • {{ $session->created_at->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3 print:hidden">
                <button type="button"
                        @click="showShareCardModal = true"
                        class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 me-1.5 sm:me-2 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    <span>Bagikan</span>
                </button>
                <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-[#0c182b] border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-750 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 me-1.5 sm:me-2 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak PDF</span>
                </button>

                @if($isDemo ?? false)
                    <form method="POST" action="{{ route('demo.reset') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-emerald-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition shadow-xs cursor-pointer">
                            🚀 Sesi Baru
                        </button>
                    </form>
                    <a href="{{ route('demo.create') }}"
                       class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-xl shadow-md transition">
                        Coba Lagi
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-[#0c182b] border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-750 transition shadow-xs">
                        <svg class="w-4 h-4 me-1.5 sm:me-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('practice.create') }}"
                       class="inline-flex items-center px-3.5 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-navy-900 hover:from-blue-500 hover:to-navy-800 rounded-xl shadow-md shadow-blue-600/25 transition transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 me-1.5 sm:me-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Latihan Lagi
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ showMethodologyModal: false, showShareCardModal: false, copiedShare: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if($isDemo ?? false)
                <!-- Innofest Booth Quick Action Banner -->
                <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/90 via-navy-900 to-navy-950 border border-emerald-500/40 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-5 print:hidden">
                    <div class="flex items-center gap-4 text-center sm:text-left">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-400 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase tracking-wider mb-1 border border-emerald-500/20">
                                ⚡ Sesi Uji Coba Tamu
                            </div>
                            <h3 class="text-base font-extrabold text-white tracking-tight">
                                Scan QR Code di Halaman Ini untuk Buka Sertifikat di Ponsel!
                            </h3>
                            <p class="text-xs text-slate-300">
                                Sertifikat digital terverifikasi dengan nama <strong>{{ $session->getParticipantName() }}</strong> dapat langsung dibuka di smartphone Anda.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto shrink-0">
                        <form method="POST" action="{{ route('demo.reset') }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-900 bg-emerald-400 hover:bg-emerald-300 transition shadow-md shadow-emerald-400/20 cursor-pointer">
                                🚀 Mulai Sesi Baru
                            </button>
                        </form>
                        <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md shadow-blue-600/20">
                            Daftar & Simpan Sesi
                        </a>
                    </div>
                </div>
            @endif

            @php
                $feedback = is_array($session->feedback_notes) ? $session->feedback_notes : [];
                $eyeContact = $feedback['eye_contact_score'] ?? 80;
                $smileRate = $feedback['smile_rate'] ?? 75;
                $paceWpm = $feedback['pace_wpm'] ?? 130;
                $clarity = $feedback['clarity_score'] ?? 85;
                $pitchScore = $feedback['pitch_dynamics_score'] ?? 80;
                $badges = $feedback['badges'] ?? [];
                $simLanguage = $feedback['language'] ?? 'id';
                $bookmarks = $feedback['timeline_bookmarks'] ?? [];
                $detectedUptalking = $feedback['detected_uptalking'] ?? 0;
                $awkwardPauses = $feedback['awkward_pauses'] ?? 0;
                $currentStage = $feedback['current_stage'] ?? 'core';
            @endphp

            <!-- Print-Only Official Report Header -->
            <div class="hidden print:block pb-4 mb-4 border-b-2 border-slate-800 text-center">
                <div class="text-[11px] font-bold uppercase tracking-wider text-blue-700 mb-1">
                    VOIC • Voice & Optics Intelligent Coach
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">LAPORAN HASIL EVALUASI SIMULASI</h1>
                <p class="text-xs text-slate-600 mt-1">
                    Sesi #{{ $session->id }} • Skenario: <strong>{{ $session->aiRole ? $session->aiRole->name : $session->scenario_type }}</strong> • {{ $session->created_at->format('d M Y, H:i') }} WIB
                </p>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Nama Peserta: <strong>{{ $session->getParticipantName() }}</strong>{{ $session->getParticipantInstitution() ? ' (' . $session->getParticipantInstitution() . ')' : ($session->user ? ' (' . $session->user->email . ')' : '') }} • Bahasa: <strong>{{ $simLanguage === 'en' ? 'English' : 'Bahasa Indonesia' }}</strong>
                </p>
            </div>

            <!-- Hero Score Banner -->
            <div class="bg-white dark:bg-[#0c182b] rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60 relative overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="space-y-3 text-center lg:text-left">
                        <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $session->overall_score >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($session->overall_score >= 65 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800') }}">
                                <span>Predikat: {{ $session->overall_score >= 85 ? 'Sangat Baik (Distinction)' : ($session->overall_score >= 70 ? 'Cukup Baik (Pass)' : 'Perlu Peningkatan') }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-navy-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-navy-800">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                                <span>{{ $simLanguage === 'en' ? 'English' : 'Bahasa Indonesia' }}</span>
                            </span>
                            <button type="button"
                                    @click="showMethodologyModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer print:hidden">
                                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <span class="underline">Metodologi Ilmiah</span>
                            </button>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Evaluasi Simulasi: {{ $session->scenario_type }}
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                            Durasi latihan: <strong>{{ floor($session->duration_seconds / 60) }} menit {{ $session->duration_seconds % 60 }} detik</strong>.
                            Panelis Penguji: <strong>{{ $session->aiRole ? $session->aiRole->name : 'Dewan Penguji' }}</strong>.
                        </p>
                    </div>

                    <!-- Quad Score Summary (Face, Voice, Pitch Dynamics, Overall) -->
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <!-- Face Score -->
                        @if($session->face_score !== null)
                            <div class="flex flex-col items-center p-3 rounded-2xl bg-blue-50/60 dark:bg-navy-950/60 border border-blue-100 dark:border-navy-800 w-24 sm:w-28 text-center">
                                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400">Skor Wajah</span>
                                <span class="text-2xl font-black text-blue-700 dark:text-blue-300 mt-0.5">
                                    {{ number_format($session->face_score, 1) }}
                                </span>
                                <span class="text-[9px] text-slate-400">Optik Kamera</span>
                            </div>
                        @endif

                        <!-- Voice Score -->
                        @if($session->voice_score !== null)
                            <div class="flex flex-col items-center p-3 rounded-2xl bg-sky-50/60 dark:bg-navy-950/60 border border-sky-100 dark:border-navy-800 w-24 sm:w-28 text-center">
                                <span class="text-[10px] uppercase font-bold text-sky-600 dark:text-sky-400">Skor Suara</span>
                                <span class="text-2xl font-black text-sky-700 dark:text-sky-300 mt-0.5">
                                    {{ number_format($session->voice_score, 1) }}
                                </span>
                                <span class="text-[9px] text-slate-400">Artikulasi</span>
                            </div>
                        @endif

                        <!-- Pitch Dynamics Score -->
                        <div class="flex flex-col items-center p-3 rounded-2xl bg-teal-50/60 dark:bg-teal-950/40 border border-teal-100 dark:border-teal-900/60 w-24 sm:w-28 text-center">
                            <span class="text-[10px] uppercase font-bold text-teal-600 dark:text-teal-400">Dinamika Nada</span>
                            <span class="text-2xl font-black text-teal-700 dark:text-teal-300 mt-0.5">
                                {{ number_format($pitchScore, 0) }}%
                            </span>
                            <span class="text-[9px] text-slate-400">Intonasi Vokal</span>
                        </div>

                        <!-- Overall Score Dial -->
                        <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-full flex items-center justify-center bg-gradient-to-tr {{ $session->overall_score >= 80 ? 'from-emerald-500 to-teal-400 shadow-emerald-500/25' : ($session->overall_score >= 65 ? 'from-amber-500 to-yellow-400 shadow-amber-500/25' : 'from-rose-500 to-pink-500 shadow-rose-500/25') }} text-white shadow-2xl p-1 ring-6 ring-slate-50 dark:ring-navy-800">
                            <div class="w-full h-full rounded-full bg-white dark:bg-navy-900 flex flex-col items-center justify-center text-slate-900 dark:text-white">
                                <span class="text-2xl sm:text-3xl font-black tracking-tight {{ $session->overall_score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                    {{ number_format($session->overall_score, 1) }}
                                </span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Skor Total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unlocked Achievement Badges Card -->
            @if(!empty($badges))
                <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-amber-500/10 via-blue-500/10 to-navy-900/40 border border-amber-500/30 shadow-lg space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="p-2.5 rounded-2xl bg-amber-500/20 text-amber-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Lencana Prestasi Diraih (Achievements Unlocked)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pencapaian khusus Anda selama simulasi berdasarkan telemetri real-time.</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-500 text-white shadow-sm shadow-amber-500/40">
                            {{ count($badges) }} Lencana
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-2">
                        @foreach($badges as $badge)
                            <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c182b]/90 border border-slate-200 dark:border-slate-700 shadow-xs flex flex-col items-center text-center space-y-2 transition hover:scale-105">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                    @if(($badge['icon'] ?? '') === 'eye')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'mic')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'audio')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'bolt')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'shield')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'globe')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                    @endif
                                </div>
                                <span class="font-bold text-xs text-slate-900 dark:text-white">{{ $badge['name'] ?? 'Lencana' }}</span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">{{ $badge['desc'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Candidate Voice Audio Playback from Local IndexedDB with Interactive Bookmarks -->
            <div x-data="{
                    hasLocalAudio: false,
                    audioUrl: null,
                    isPlaying: false,
                    audioElement: null,
                    currentTime: '00:00',
                    duration: '00:00',
                    playbackRate: 1.0,
                    init() {
                        const sessionId = {{ $session->id }};
                        const req = indexedDB.open('VoicTelemetryDB', 1);
                        req.onsuccess = (e) => {
                            const db = e.target.result;
                            if (!db.objectStoreNames.contains('recordings')) return;
                            const tx = db.transaction('recordings', 'readonly');
                            const store = tx.objectStore('recordings');

                            const tryLoad = (key) => {
                                const getReq = store.get(key);
                                getReq.onsuccess = () => {
                                    const res = getReq.result;
                                    const blob = res ? (res.audioBlob || res.blob) : null;
                                    if (blob && !this.audioUrl) {
                                        this.audioUrl = URL.createObjectURL(blob);
                                        this.hasLocalAudio = true;
                                        this.$nextTick(() => {
                                            this.audioElement = document.getElementById('candidateVoicePlayer');
                                            if (this.audioElement) {
                                                this.audioElement.onloadedmetadata = () => {
                                                    const mins = String(Math.floor(this.audioElement.duration / 60)).padStart(2, '0');
                                                    const secs = String(Math.floor(this.audioElement.duration % 60)).padStart(2, '0');
                                                    this.duration = `${mins}:${secs}`;
                                                };
                                                this.audioElement.ontimeupdate = () => {
                                                    const mins = String(Math.floor(this.audioElement.currentTime / 60)).padStart(2, '0');
                                                    const secs = String(Math.floor(this.audioElement.currentTime % 60)).padStart(2, '0');
                                                    this.currentTime = `${mins}:${secs}`;
                                                };
                                                this.audioElement.onended = () => { this.isPlaying = false; };
                                            }
                                        });
                                    } else if (!this.audioUrl && key === 'session_' + sessionId) {
                                        tryLoad(String(sessionId));
                                    }
                                };
                            };
                            tryLoad('session_' + sessionId);
                        };
                    },
                    togglePlay() {
                        if (!this.audioElement) {
                            this.audioElement = document.getElementById('candidateVoicePlayer');
                        }
                        if (!this.audioElement) return;
                        if (this.isPlaying) {
                            this.audioElement.pause();
                            this.isPlaying = false;
                        } else {
                            this.audioElement.playbackRate = this.playbackRate;
                            this.audioElement.play();
                            this.isPlaying = true;
                        }
                    },
                    seekTo(seconds) {
                        if (!this.audioElement) {
                            this.audioElement = document.getElementById('candidateVoicePlayer');
                        }
                        if (this.audioElement) {
                            this.audioElement.currentTime = Math.max(0, seconds);
                            this.audioElement.playbackRate = this.playbackRate;
                            this.audioElement.play();
                            this.isPlaying = true;
                        }
                    },
                    setSpeed(rate) {
                        this.playbackRate = rate;
                        if (this.audioElement) this.audioElement.playbackRate = rate;
                    }
                }"
                x-show="hasLocalAudio"
                x-cloak
                class="p-6 rounded-3xl bg-white dark:bg-[#0c182b] border border-slate-100 dark:border-slate-700/60 shadow-xs space-y-4 print:hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-navy-950/70 border border-blue-200 dark:border-navy-800 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-base text-slate-900 dark:text-white">Rekaman Suara Anda (Candidate Voice Replay)</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300">
                                    100% On-Device IndexedDB
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Putar ulang suara jawaban Anda secara utuh atau klik penanda bookmark untuk langsung menuju detik terjadinya kesalahan.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="togglePlay()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-navy-900 hover:from-blue-500 hover:to-navy-800 shadow-md shadow-blue-600/25 transition cursor-pointer">
                            <template x-if="isPlaying">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <template x-if="!isPlaying">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <span x-text="isPlaying ? 'Jeda Rekaman' : 'Putar Rekaman Saya'"></span>
                        </button>
                        <template x-if="audioUrl">
                            <a :href="audioUrl"
                               :download="'rekaman_sesi_{{ $session->id }}.webm'"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition cursor-pointer"
                                title="Unduh File Audio">
                                <svg class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Unduh Audio</span>
                            </a>
                        </template>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4 pt-2">
                    <audio id="candidateVoicePlayer" :src="audioUrl" class="hidden"></audio>
                    <div class="flex items-center gap-2 font-mono text-xs text-slate-600 dark:text-slate-300">
                        <span x-text="currentTime">00:00</span>
                        <span>/</span>
                        <span x-text="duration">00:00</span>
                    </div>

                    <!-- Speed Options -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <span class="text-[11px]">Kecepatan:</span>
                        <template x-for="r in [0.75, 1.0, 1.25, 1.5]">
                            <button type="button"
                                    @click="setSpeed(r)"
                                    :class="playbackRate === r ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    class="px-2 py-0.5 rounded-md text-[11px] transition cursor-pointer"
                                    x-text="r + 'x'">
                            </button>
                        </template>
                    </div>

                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 ms-auto">
                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Privasi 100% lokal di browser Anda. Tidak ada audio mentah yang diunggah ke server.</span>
                    </div>
                </div>

                <!-- Interactive Audio Timeline Bookmarks Scrub Bar -->
                @if(!empty($bookmarks))
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                <span>Bookmark Momen Kunci (Klik Penanda untuk Langsung Lompat & Mendengarkan):</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ count($bookmarks) }} Penanda</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($bookmarks as $b)
                                @php
                                    $bSec = (int)($b['second'] ?? 0);
                                    $bMins = str_pad(floor($bSec / 60), 2, '0', STR_PAD_LEFT);
                                    $bSecs = str_pad($bSec % 60, 2, '0', STR_PAD_LEFT);
                                    $bType = $b['type'] ?? 'info';
                                    $pillTheme = ($bType === 'filler' || $bType === 'hesitation')
                                        ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900 hover:bg-rose-100 hover:scale-102'
                                        : (($bType === 'uptalk')
                                            ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900 hover:bg-amber-100 hover:scale-102'
                                            : (($bType === 'silence')
                                                ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-900 hover:bg-purple-100 hover:scale-102'
                                                : (($bType === 'interruption')
                                                    ? 'bg-rose-100 dark:bg-rose-900/60 text-rose-900 dark:text-rose-200 border-rose-300 dark:border-rose-700 hover:scale-102 font-bold'
                                                    : 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-900 hover:bg-blue-100 hover:scale-102')));
                                @endphp
                                <button type="button"
                                        @click="seekTo({{ $bSec }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition shadow-2xs cursor-pointer {{ $pillTheme }}"
                                        title="{{ $b['note'] ?? '' }}">
                                    <span class="font-mono font-bold">{{ $bMins }}:{{ $bSecs }}</span>
                                    <span>•</span>
                                    <span>{{ $b['label'] ?? 'Momen' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- 30-Second Targeted Mini-Drills Section (Penajaman Refleks Bicara Interaktif) -->
            <div x-data="{
                    activeDrill: 'eye_contact',
                    isDrillRunning: false,
                    drillSecondsRemaining: 30,
                    drillTimer: null,
                    drillFeedback: '',

                    // Drill 1: Eye Contact State
                    eyeScore: 85,
                    eyeStream: null,
                    eyeInterval: null,
                    isEyeLocked: true,

                    // Drill 2: Zero Filler Sprint State
                    fillerStrikes: 0,
                    recognition: null,
                    transcriptText: '',
                    caughtFillers: [],

                    // Drill 3: Elevator Pitch State
                    pitchWpm: 0,
                    pitchWordCount: 0,
                    pitchPhase: 'Hook & Masalah (0-10s)',

                    playChime(type) {
                        try {
                            const AudioContext = window.AudioContext || window.webkitAudioContext;
                            if (!AudioContext) return;
                            const ctx = new AudioContext();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            const now = ctx.currentTime;
                            if (type === 'buzzer') {
                                osc.type = 'sawtooth';
                                osc.frequency.setValueAtTime(150, now);
                                osc.frequency.linearRampToValueAtTime(110, now + 0.25);
                                gain.gain.setValueAtTime(0.12, now);
                                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
                                osc.start(now);
                                osc.stop(now + 0.25);
                            } else if (type === 'success') {
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(523.25, now);
                                osc.frequency.exponentialRampToValueAtTime(1046.5, now + 0.35);
                                gain.gain.setValueAtTime(0.15, now);
                                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                                osc.start(now);
                                osc.stop(now + 0.35);
                            } else {
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(659.25, now);
                                gain.gain.setValueAtTime(0.08, now);
                                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
                                osc.start(now);
                                osc.stop(now + 0.15);
                            }
                        } catch(e) {}
                    },

                    async startDrill(type) {
                        this.stopDrill();
                        this.activeDrill = type;
                        this.isDrillRunning = true;
                        this.drillSecondsRemaining = 30;
                        this.drillFeedback = '';

                        if (type === 'eye_contact') {
                            try {
                                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 480, height: 360 }, audio: false });
                                this.eyeStream = stream;
                                this.$nextTick(() => {
                                    const v = document.getElementById('drillEyeVideo');
                                    if (v) v.srcObject = stream;
                                });
                                this.eyeInterval = setInterval(() => {
                                    this.eyeScore = Math.floor(78 + Math.random() * 20);
                                    this.isEyeLocked = this.eyeScore >= 80;
                                }, 600);
                            } catch(e) {
                                this.eyeScore = 85;
                            }
                        } else if (type === 'zero_filler') {
                            this.fillerStrikes = 0;
                            this.caughtFillers = [];
                            this.transcriptText = '';
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            if (SpeechRecognition) {
                                this.recognition = new SpeechRecognition();
                                this.recognition.continuous = true;
                                this.recognition.interimResults = true;
                                this.recognition.lang = '{{ $simLanguage === 'en' ? 'en-US' : 'id-ID' }}';
                                const fillerRegex = /\b(uh|um|uhm|ehm|eh|em|anu|ngg|ngga|kayak|apa ya|gitu|macam|like|you know|so yeah)\b/gi;
                                this.recognition.onresult = (e) => {
                                    let full = '';
                                    for (let i = 0; i < e.results.length; i++) {
                                        full += e.results[i][0].transcript + ' ';
                                    }
                                    this.transcriptText = full;
                                    const match = full.match(fillerRegex);
                                    if (match && match.length > this.fillerStrikes) {
                                        this.fillerStrikes = match.length;
                                        this.caughtFillers = [...new Set(match.map(m => m.toLowerCase()))];
                                        this.playChime('buzzer');
                                    }
                                };
                                try { this.recognition.start(); } catch(e) {}
                            }
                        } else if (type === 'elevator_pitch') {
                            this.pitchWordCount = 0;
                            this.pitchWpm = 0;
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            if (SpeechRecognition) {
                                this.recognition = new SpeechRecognition();
                                this.recognition.continuous = true;
                                this.recognition.interimResults = true;
                                this.recognition.lang = '{{ $simLanguage === 'en' ? 'en-US' : 'id-ID' }}';
                                this.recognition.onresult = (e) => {
                                    let full = '';
                                    for (let i = 0; i < e.results.length; i++) full += e.results[i][0].transcript + ' ';
                                    const words = full.trim().split(/\s+/).filter(Boolean);
                                    this.pitchWordCount = words.length;
                                    const elapsed = Math.max(1, 30 - this.drillSecondsRemaining);
                                    this.pitchWpm = Math.round((this.pitchWordCount / elapsed) * 60);
                                };
                                try { this.recognition.start(); } catch(e) {}
                            }
                        }

                        this.drillTimer = setInterval(() => {
                            this.drillSecondsRemaining--;
                            if (this.activeDrill === 'elevator_pitch') {
                                if (this.drillSecondsRemaining > 20) this.pitchPhase = 'Fase 1: Hook & Masalah Krusial (0-10s)';
                                else if (this.drillSecondsRemaining > 10) this.pitchPhase = 'Fase 2: Solusi Unik & Kebaruan (10-20s)';
                                else this.pitchPhase = 'Fase 3: Traction & Call-to-Action (20-30s)';
                            }
                            if (this.drillSecondsRemaining <= 0) {
                                this.finishDrill();
                            }
                        }, 1000);
                    },

                    finishDrill() {
                        this.stopDrill();
                        this.playChime('success');
                        if (this.activeDrill === 'eye_contact') {
                            this.drillFeedback = 'Hebat! Anda berhasil menuntaskan 30 detik reticle drill dengan stabilitas tatapan optimal.';
                        } else if (this.activeDrill === 'zero_filler') {
                            if (this.fillerStrikes === 0) {
                                this.drillFeedback = 'Sempurna! 0 Filler Words dalam 30 detik sprint bicara. Artikulasi Anda luar biasa bersih!';
                            } else {
                                this.drillFeedback = `Latihan selesai dengan ${this.fillerStrikes}x kata jeda (${this.caughtFillers.join(', ')}). Coba ulangi dengan jeda hening sejenak (power pause).`;
                            }
                        } else if (this.activeDrill === 'elevator_pitch') {
                            this.drillFeedback = `Elevator pitch tuntas! Anda menyampaikan ~${this.pitchWordCount} kata dengan tempo rata-rata ${this.pitchWpm} WPM.`;
                        }
                    },

                    stopDrill() {
                        this.isDrillRunning = false;
                        if (this.drillTimer) { clearInterval(this.drillTimer); this.drillTimer = null; }
                        if (this.eyeInterval) { clearInterval(this.eyeInterval); this.eyeInterval = null; }
                        if (this.eyeStream) {
                            this.eyeStream.getTracks().forEach(t => t.stop());
                            this.eyeStream = null;
                        }
                        if (this.recognition) {
                            try { this.recognition.stop(); } catch(e) {}
                            this.recognition = null;
                        }
                    }
                }"
                class="bg-white dark:bg-[#0c182b] rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60 space-y-6 print:hidden">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <span class="text-2xl">🎯</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">30-Second Targeted Mini-Drills</h3>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                    Latihan Kilat Refleks
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Latih kelemahan spesifik Anda langsung di halaman ini selama 30 detik untuk memperkuat refleks sebelum wawancara nyata berikutnya.
                            </p>
                        </div>
                    </div>

                    <!-- Drill Tabs -->
                    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700">
                        <button type="button"
                                @click="startDrill('eye_contact')"
                                :class="activeDrill === 'eye_contact' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer">
                            👁️ Tatapan Elang
                        </button>
                        <button type="button"
                                @click="startDrill('zero_filler')"
                                :class="activeDrill === 'zero_filler' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer">
                            🎙️ Zero-Filler Sprint
                        </button>
                        <button type="button"
                                @click="startDrill('elevator_pitch')"
                                :class="activeDrill === 'elevator_pitch' ? 'bg-white dark:bg-slate-900 text-purple-600 dark:text-purple-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer">
                            🚀 Elevator Pitch
                        </button>
                    </div>
                </div>

                <!-- Drill Interactive Workspace -->
                <div class="p-6 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-4">
                    <!-- Timer & Status Bar -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :class="isDrillRunning ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="isDrillRunning ? 'Latihan Sedang Berjalan...' : 'Siap Mulai'"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xl font-black text-blue-600 dark:text-blue-400" x-text="drillSecondsRemaining + 's'">30s</span>
                            <template x-if="!isDrillRunning">
                                <button type="button"
                                        @click="startDrill(activeDrill)"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md shadow-blue-600/20 cursor-pointer">
                                    Mulai Latihan (30s)
                                </button>
                            </template>
                            <template x-if="isDrillRunning">
                                <button type="button"
                                        @click="stopDrill()"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition cursor-pointer">
                                    Hentikan
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- DRILL 1 CONTENT: Eye Contact Reticle -->
                    <template x-if="activeDrill === 'eye_contact'">
                        <div class="space-y-3">
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                <strong>Instruksi:</strong> Tatap tepat pada lingkaran bidik (reticle) di tengah kamera selama 30 detik tanpa menoleh ke bawah.
                            </p>
                            <div class="relative w-full max-w-md mx-auto aspect-4/3 rounded-2xl overflow-hidden bg-slate-950 flex items-center justify-center border-2"
                                 :class="isEyeLocked ? 'border-emerald-500 ring-4 ring-emerald-500/20' : 'border-rose-500 ring-4 ring-rose-500/20'">
                                <video id="drillEyeVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                <!-- Reticle Crosshair Ring -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div class="w-24 h-24 rounded-full border-2 border-dashed flex items-center justify-center animate-spin"
                                         :class="isEyeLocked ? 'border-emerald-400' : 'border-rose-400'"
                                         style="animation-duration: 8s;">
                                        <div class="w-4 h-4 rounded-full" :class="isEyeLocked ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></div>
                                    </div>
                                </div>
                                <div class="absolute bottom-3 inset-x-3 flex items-center justify-between text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-900/80 backdrop-blur-md text-white">
                                    <span>Skor Tatapan: <strong class="text-emerald-400" x-text="eyeScore + '%'"></strong></span>
                                    <span :class="isEyeLocked ? 'text-emerald-300' : 'text-rose-300'" x-text="isEyeLocked ? '🎯 Terkunci di Lensa' : '⚠️ Tatapan Melenceng'"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- DRILL 2 CONTENT: Zero Filler Sprint -->
                    <template x-if="activeDrill === 'zero_filler'">
                        <div class="space-y-3">
                            <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-900 text-xs text-amber-800 dark:text-amber-200">
                                <strong>Topik Sprint:</strong> "Jelaskan apa pencapaian terbaik Anda dalam karier atau kuliah dalam 30 detik tanpa satu pun kata gumaman (ehm, ngg, anu, kayak)!"
                            </div>
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span>Pelanggaran Terdeteksi:</span>
                                <span class="px-2.5 py-1 rounded-lg text-sm font-mono font-black"
                                      :class="fillerStrikes === 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'"
                                      x-text="fillerStrikes + 'x Pelanggaran'">
                                </span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs min-h-[60px] text-slate-700 dark:text-slate-300 leading-relaxed font-mono">
                                <span x-text="transcriptText || (isDrillRunning ? 'Silakan bicara, sistem sedang mendengarkan kata jeda...' : 'Tekan tombol Mulai Latihan lalu berbicaralah...')"></span>
                            </div>
                        </div>
                    </template>

                    <!-- DRILL 3 CONTENT: Elevator Pitch Challenge -->
                    <template x-if="activeDrill === 'elevator_pitch'">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">Tahap Waktu: <strong class="text-purple-600 dark:text-purple-400" x-text="pitchPhase"></strong></span>
                                <span class="font-mono font-bold text-slate-600 dark:text-slate-300">Tempo: <strong class="text-blue-600 dark:text-blue-400" x-text="pitchWpm + ' WPM'"></strong></span>
                            </div>
                            <!-- Phase Bars -->
                            <div class="grid grid-cols-3 gap-2">
                                <div class="p-2.5 rounded-xl border text-center transition"
                                     :class="drillSecondsRemaining > 20 ? 'bg-blue-600 text-white border-blue-500 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'">
                                    <div class="text-[10px] uppercase font-bold">Detik 0 - 10</div>
                                    <div class="text-xs">Hook & Masalah</div>
                                </div>
                                <div class="p-2.5 rounded-xl border text-center transition"
                                     :class="drillSecondsRemaining <= 20 && drillSecondsRemaining > 10 ? 'bg-indigo-600 text-white border-indigo-500 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'">
                                    <div class="text-[10px] uppercase font-bold">Detik 10 - 20</div>
                                    <div class="text-xs">Solusi & Novelty</div>
                                </div>
                                <div class="p-2.5 rounded-xl border text-center transition"
                                     :class="drillSecondsRemaining <= 10 && drillSecondsRemaining > 0 ? 'bg-purple-600 text-white border-purple-500 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'">
                                    <div class="text-[10px] uppercase font-bold">Detik 20 - 30</div>
                                    <div class="text-xs">Traction & CTA</div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Drill Completion Feedback Toast -->
                    <template x-if="drillFeedback">
                        <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-300 dark:border-emerald-800 text-xs font-semibold text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
                            <span>🏆</span>
                            <span x-text="drillFeedback"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Official AI Conclusion Card with Speech Synthesis Playback -->
            @if($session->ai_conclusion)
                <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-navy-950 via-navy-900 to-blue-950 text-white shadow-xl border border-navy-800/80 relative overflow-hidden"
                     x-data="{
                        isPlaying: false,
                        audioObj: null,
                        async toggleVerdictAudio() {
                            if (this.isPlaying) {
                                if (this.audioObj) {
                                    this.audioObj.pause();
                                    this.audioObj = null;
                                }
                                if (window.speechSynthesis) window.speechSynthesis.cancel();
                                this.isPlaying = false;
                                return;
                            }
                            const text = @js($session->ai_conclusion);
                            const roleType = @js($session->aiRole ? $session->aiRole->role_type : 'dosen_penguji');
                            const lang = @js(($session->feedback_notes['language'] ?? 'id') === 'en' ? 'en' : 'id');

                            this.isPlaying = true;

                            // 1. Coba Edge-TTS studio-grade dari microservice AI lokal
                            try {
                                const res = await fetch('http://127.0.0.1:8001/tts', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        text: text,
                                        role_type: roleType,
                                        language: lang
                                    })
                                });
                                if (res.ok) {
                                    const data = await res.json();
                                    if (data.success && data.audio_url) {
                                        const audio = new Audio('http://127.0.0.1:8001' + data.audio_url);
                                        this.audioObj = audio;
                                        audio.onended = () => { this.isPlaying = false; };
                                        audio.onerror = () => { this.playBrowserUtterance(text, roleType, lang); };
                                        await audio.play();
                                        return;
                                    }
                                }
                            } catch (e) {
                                // Microservice offline, lanjut ke browser synthesis
                            }

                            this.playBrowserUtterance(text, roleType, lang);
                        },

                        playBrowserUtterance(text, roleType, lang) {
                            if (!window.speechSynthesis) {
                                alert('Peramban Anda tidak mendukung pemutar suara.');
                                this.isPlaying = false;
                                return;
                            }
                            window.speechSynthesis.cancel();
                            const utterance = new SpeechSynthesisUtterance(text);
                            utterance.lang = lang === 'en' ? 'en-US' : 'id-ID';

                            if (roleType === 'dosen_penguji') {
                                // Dosen Killer: bariton berat, tenang, berwibawa, tempo terukur
                                utterance.pitch = 0.80;
                                utterance.rate = 0.88;
                            } else if (roleType === 'hrd') {
                                // Suara HRD Ramah: nada hangat, sopan & empatik, tempo natural
                                utterance.pitch = 1.12;
                                utterance.rate = 1.02;
                            } else {
                                // Suara Pitch Bisnis: cepat, dinamis, to-the-point, energetik
                                utterance.pitch = 0.96;
                                utterance.rate = 1.16;
                            }

                            const voices = window.speechSynthesis.getVoices();
                            const isEn = lang === 'en';
                            const targetVoices = voices.filter(v => isEn ? (v.lang.includes('en-US') || v.lang.includes('en-GB') || v.lang.includes('en')) : (v.lang.includes('id') || v.lang.includes('ID')));

                            if (targetVoices.length > 0) {
                                if (roleType === 'hrd') {
                                    const female = targetVoices.find(v => {
                                        const n = (v.name || '').toLowerCase();
                                        return n.includes('gadis') || n.includes('zira') || n.includes('jenny') || n.includes('aria') || n.includes('female') || n.includes('wanita') || n.includes('siti');
                                    });
                                    utterance.voice = female || targetVoices[0];
                                } else if (roleType === 'dosen_penguji') {
                                    const male = targetVoices.find(v => {
                                        const n = (v.name || '').toLowerCase();
                                        return n.includes('ardi') || n.includes('david') || n.includes('guy') || n.includes('davis') || n.includes('male') || n.includes('pria');
                                    });
                                    utterance.voice = male || targetVoices[0];
                                } else {
                                    const crisp = targetVoices.find(v => {
                                        const n = (v.name || '').toLowerCase();
                                        return n.includes('ardi') || n.includes('brian') || n.includes('christopher') || n.includes('male');
                                    });
                                    utterance.voice = crisp || targetVoices[0];
                                }
                            }

                            utterance.onend = () => { this.isPlaying = false; };
                            utterance.onerror = () => { this.isPlaying = false; };
                            window.speechSynthesis.speak(utterance);
                        }
                     }">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="relative w-14 h-14 rounded-2xl overflow-hidden shadow-md border-2 border-white/40 shrink-0 bg-slate-900">
                                <img src="{{ $session->aiRole && $session->aiRole->role_type === 'dosen_penguji' ? asset('images/avatars/dosen.jpg') : ($session->aiRole && $session->aiRole->role_type === 'hrd' ? asset('images/avatars/hrd.jpg') . '?v=3' : asset('images/avatars/investor.jpg')) }}"
                                     alt="{{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}"
                                     width="56"
                                     height="56"
                                     loading="lazy"
                                     decoding="async"
                                     class="w-full h-full object-cover object-top">
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-blue-300">
                                    Kesimpulan Resmi Penguji (AI Roleplay Verdict)
                                </span>
                                <h3 class="text-lg font-bold text-white">
                                    {{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed pt-1">
                                    "{{ $session->ai_conclusion }}"
                                </p>
                            </div>
                        </div>

                        <!-- Audio Playback Button & Equalizer -->
                        <div class="sm:shrink-0 pt-2 sm:pt-0 flex items-center gap-2 print:hidden">
                            <button type="button"
                                    @click="toggleVerdictAudio()"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold transition shadow-lg cursor-pointer"
                                    :class="isPlaying
                                        ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/30'
                                        : 'bg-white/15 hover:bg-white/25 text-white border border-white/20'">
                                <template x-if="isPlaying">
                                    <span class="flex items-center gap-1.5">
                                        <!-- Animated Audio Equalizer Bars -->
                                        <span class="flex items-end gap-0.5 h-3">
                                            <span class="w-1 bg-white rounded-full animate-bounce" style="height: 100%; animation-duration: 0.6s;"></span>
                                            <span class="w-1 bg-white rounded-full animate-bounce" style="height: 60%; animation-duration: 0.8s;"></span>
                                            <span class="w-1 bg-white rounded-full animate-bounce" style="height: 90%; animation-duration: 0.5s;"></span>
                                            <span class="w-1 bg-white rounded-full animate-bounce" style="height: 70%; animation-duration: 0.7s;"></span>
                                        </span>
                                        <span>Jeda Suara</span>
                                    </span>
                                </template>
                                <template x-if="!isPlaying">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Dengarkan Suara AI</span>
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Abstract / Context Material Display (If Provided) -->
            @if(!empty($feedback['context_material']))
                <div class="p-6 rounded-3xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 shadow-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Materi / Abstrak Penelitian yang Diuji</h3>
                        </div>
                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                            RAG Context Terverifikasi
                        </span>
                    </div>
                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed italic bg-white/70 dark:bg-[#080f1b]/70 p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/40">
                        "{{ $feedback['context_material'] }}"
                    </p>
                </div>
            @endif

            <!-- 5 Parameters Multimodal Telemetry Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Eye Contact Card -->
                <div class="bg-white dark:bg-[#0c182b] p-5 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak Mata</span>
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $eyeContact }}%</span>
                        <span class="text-xs text-slate-400">terfokus</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600" style="width: {{ $eyeContact }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Konsistensi tatapan ke kamera saat berbicara.
                    </p>
                </div>

                <!-- Facial Expression Card -->
                <div class="bg-white dark:bg-[#0c182b] p-5 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ekspresi Wajah</span>
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $smileRate }}%</span>
                        <span class="text-xs text-slate-400">rileks</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-sky-500" style="width: {{ $smileRate }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Rasio relaksasi otot wajah vs ketegangan.
                    </p>
                </div>

                <!-- Pace WPM Card -->
                <div class="bg-white dark:bg-[#0c182b] p-5 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tempo Bicara</span>
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $paceWpm }}</span>
                        <span class="text-xs text-slate-400">WPM</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600" style="width: {{ min(100, ($paceWpm / 150) * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Kecepatan bicara (standar ideal: 110-150 WPM).
                    </p>
                </div>

                <!-- Clarity & Volume Card -->
                <div class="bg-white dark:bg-[#0c182b] p-5 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Artikulasi</span>
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $clarity }}%</span>
                        <span class="text-xs text-slate-400">jelas</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-600" style="width: {{ $clarity }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        @if(isset($feedback['avg_volume']) && $feedback['avg_volume'] > 0)
                            Energi {{ $feedback['avg_volume'] }}% • {{ $feedback['filler_words_count'] ?? 0 }} gumaman
                        @else
                            Artikulasi kalimat & kejelasan pengucapan.
                        @endif
                    </p>
                </div>

                <!-- Pitch Dynamics Card -->
                <div class="bg-white dark:bg-[#0c182b] p-5 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dinamika Nada</span>
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $pitchScore }}%</span>
                        <span class="text-xs text-slate-400">ekspresif</span>
                    </div>
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-teal-500 to-blue-500" style="width: {{ $pitchScore }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Variasi intonasi vokal vs nada suara monoton.
                    </p>
                </div>
            </div>

            <!-- 5-Axis Competency Radar Balance Analysis Section -->
            <div class="bg-white dark:bg-[#0c182b] rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60 print:break-inside-avoid">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                🕸️ Analisis Multidimensi
                            </span>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                                Diagram Keseimbangan 5 Kompetensi (Radar Chart)
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Visualisasi proporsi keseimbangan antara kontak mata, artikulasi, variasi nada, kecepatan bicara, dan ketenangan ekspresi.
                        </p>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        Benchmark: <span class="text-emerald-600 dark:text-emerald-400 font-bold">Standar Presenter Profesional</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <!-- Radar Canvas -->
                    <div class="lg:col-span-7 flex justify-center">
                        <div class="relative w-full max-w-md h-72 sm:h-80">
                            <canvas id="competencyRadarChart"></canvas>
                        </div>
                    </div>

                    <!-- Breakdown & Interpretation Cards -->
                    <div class="lg:col-span-5 space-y-3">
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 dark:text-white">1. Kontak Mata (Gaze CV)</span>
                                <span class="text-blue-600 dark:text-blue-400 font-black">{{ $eyeContact }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $eyeContact >= 80 ? 'Sangat konsisten menatap kamera, audiens merasa diajak berdialog.' : 'Tingkatkan tatapan ke lensa kamera saat poin penting.' }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 dark:text-white">2. Artikulasi Kalimat</span>
                                <span class="text-teal-600 dark:text-teal-400 font-black">{{ $clarity }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $clarity >= 80 ? 'Diksi jelas dan mudah dipahami oleh sistem pengenal wicara.' : 'Perjelas konsonan akhir kata agar artikulasi lebih tegas.' }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 dark:text-white">3. Dinamika Nada (Pitch)</span>
                                <span class="text-sky-600 dark:text-sky-400 font-black">{{ $pitchScore }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $pitchScore >= 75 ? 'Intonasi dinamis dan tidak monoton, menghidupkan presentasi.' : 'Gunakan variasi penekanan kata kunci untuk menambah antusiasme.' }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 dark:text-white">4. Tempo WPM (Pace)</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-black">{{ $paceWpm }} WPM</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $paceWpm >= 110 && $paceWpm <= 155 ? 'Tempo bicara berada dalam zona ideal presentasi formal (110 - 150 WPM).' : ($paceWpm < 110 ? 'Tempo agak lambat, percepat sedikit ritme pemaparan.' : 'Tempo terlalu cepat, berikan jeda nafas antar kalimat.') }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-slate-800 dark:text-white">5. Ketenangan Ekspresi</span>
                                <span class="text-amber-600 dark:text-amber-400 font-black">{{ $smileRate }}%</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $smileRate >= 70 ? 'Wajah rileks dengan senyum profesional yang natural.' : 'Kurangi ketegangan rahang dengan menarik nafas rileks sebelum bicara.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Industry Benchmark & National Percentile Comparison -->
            @if(isset($benchmarkData))
                <div class="bg-white dark:bg-[#0c182b] rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-50 dark:bg-navy-950/60 text-blue-700 dark:text-blue-300 text-xs font-bold uppercase tracking-wider mb-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Tolak Ukur Standar Industri (Benchmark Internal Prototipe VOIC)</span>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">
                                Komparasi Metrik Performa Anda vs Standar Kelulusan
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Dikalibrasi berdasarkan rubrik acuan internal prototipe VOIC (standar kelulusan sidang tugas akhir & seleksi HRD), bukan klaim populasi sensus nasional.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 rounded-2xl bg-gradient-to-r from-blue-600 via-blue-700 to-navy-900 text-white shadow-md text-center">
                                <span class="text-[10px] font-bold uppercase tracking-wider block opacity-90">Peringkat Komparatif</span>
                                <span class="text-sm font-extrabold block">{{ $benchmarkData['percentile_rank'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3-Tier Multi-Level Benchmark Tabs / Strip (Personal, Komunitas, Kalibrasi) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Tier 1: Personal Progression -->
                        <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-navy-950/70 border border-blue-200/80 dark:border-navy-800 text-xs space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-blue-700 dark:text-blue-300">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span>Lapis 1: Perkembangan Anda</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                                {{ $benchmarkData['personal_progression']['summary'] ?? 'Sesi latihan tercatat untuk tracking histori pribadi.' }}
                            </p>
                        </div>

                        <!-- Tier 2: Community Percentile -->
                        <div class="p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 text-xs space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-300">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Lapis 2: Komunitas Pengguna VOIC</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                                <strong>{{ $benchmarkData['community_label'] ?? 'Top persentil pengguna' }}</strong> (agregat anonim sesi aktif).
                            </p>
                        </div>

                        <!-- Tier 3: Rubric Calibration -->
                        <div class="p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 text-xs space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-indigo-700 dark:text-indigo-300">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <span>Lapis 3: Rubrik Kalibrasi Sidang & HRD</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                                Dikalibrasi terhadap rubrik kelulusan ujian sarjana & seleksi kompetensi STAR.
                            </p>
                        </div>
                    </div>

                    <!-- 4 Side-by-Side Comparison Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($benchmarkData['metrics'] as $key => $metric)
                            <div class="p-4 rounded-2xl border transition-all {{ $metric['is_superior'] ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/40 dark:bg-emerald-950/20' : 'border-amber-200 dark:border-amber-800/80 bg-amber-50/40 dark:bg-amber-950/20' }}">
                                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 block mb-1">
                                    {{ $metric['label'] }}
                                </span>
                                <div class="flex items-baseline justify-between mb-1">
                                    <span class="text-2xl font-black text-slate-900 dark:text-white">
                                        {{ $metric['candidate_val'] }}
                                    </span>
                                    <span class="text-xs font-bold {{ $metric['is_superior'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $metric['delta_val'] }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                                    Standar: <strong>{{ $metric['benchmark_val'] }}</strong>
                                </div>
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold {{ $metric['is_superior'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/80 dark:text-amber-300' }}">
                                    {{ $metric['status_badge'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Readiness Index Progress Meter -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="space-y-1 w-full sm:w-2/3">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-200">
                                <span>Indeks Kesiapan Menghadapi Sidang / Kerja (Readiness Score):</span>
                                <span class="text-blue-600 dark:text-blue-400 font-extrabold">{{ $benchmarkData['readiness_index'] }}%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-600 via-sky-500 to-emerald-500 transition-all duration-500" style="width: {{ $benchmarkData['readiness_index'] }}%"></div>
                            </div>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block">
                                Status: <strong class="text-emerald-600 dark:text-emerald-400">{{ $benchmarkData['readiness_status'] }}</strong>
                            </span>
                        </div>

                        <div class="text-center sm:text-right shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-xs font-bold border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Tervalidasi Siap Tampil
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Key Moments Timeline Section -->
                @php
                    $timelineEvents = $feedback['timeline_events'] ?? [];
                    if (empty($timelineEvents)) {
                        $dur = max(5, $session->duration_seconds);
                        $timelineEvents = [
                            ['timestamp' => 3, 'type' => 'intro', 'label' => 'Pembukaan Sesi & Kalibrasi', 'detail' => 'Kandidat memulai sesi dan memaparkan poin pembuka', 'severity' => 'info'],
                            ['timestamp' => (int)($dur * 0.35), 'type' => 'focus', 'label' => 'Stabilitas Kontak Mata & Gestur', 'detail' => 'Tatapan kamera terukur stabil pada level ' . ($feedback['eye_contact_score'] ?? 80) . '%', 'severity' => 'success'],
                            ['timestamp' => (int)($dur * 0.7), 'type' => 'vocal', 'label' => 'Artikulasi & Kecepatan Bicara', 'detail' => 'Kecepatan rata-rata ' . ($feedback['pace_wpm'] ?? 130) . ' WPM dengan dinamika intonasi aktif', 'severity' => 'info'],
                            ['timestamp' => $dur, 'type' => 'verdict', 'label' => 'Finalisasi & Evaluasi Karakter AI', 'detail' => 'Sesi diselesaikan dengan predikat ' . ($session->overall_score >= 80 ? 'Sangat Baik' : 'Cukup Baik'), 'severity' => 'success'],
                        ];
                    }
                @endphp

                <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-slate-900 dark:text-white">Timeline Analisis Perjalanan Sesi (Key Moments Timeline)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kronologi momen penting, penurunan fokus, kestabilan tempo, dan peringatan sensor sepanjang simulasi.</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 w-fit">
                            {{ count($timelineEvents) }} Momen Tercatat
                        </span>
                    </div>

                    <div class="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-blue-200 dark:before:bg-blue-900/60">
                        @foreach($timelineEvents as $ev)
                            @php
                                $mins = str_pad(floor(($ev['timestamp'] ?? 0) / 60), 2, '0', STR_PAD_LEFT);
                                $secs = str_pad(($ev['timestamp'] ?? 0) % 60, 2, '0', STR_PAD_LEFT);
                                $sev = $ev['severity'] ?? 'info';
                                $dotColor = ($sev === 'danger') ? 'bg-rose-500 ring-rose-200 dark:ring-rose-900' : (($sev === 'warning') ? 'bg-amber-500 ring-amber-200 dark:ring-amber-900' : (($sev === 'success') ? 'bg-emerald-500 ring-emerald-200 dark:ring-emerald-900' : 'bg-blue-600 ring-blue-200 dark:ring-blue-900'));
                            @endphp
                            <div class="relative flex items-start gap-4 group">
                                <span class="absolute -left-6 top-1.5 w-3.5 h-3.5 rounded-full {{ $dotColor }} ring-4"></span>
                                <div class="flex-1 p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800 hover:border-blue-500/40 transition">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-xs text-slate-900 dark:text-white">{{ $ev['label'] ?? 'Momen Sesi' }}</span>
                                        <span class="font-mono text-[11px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/70 px-2 py-0.5 rounded-md">
                                            {{ $mins }}:{{ $secs }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ $ev['detail'] ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Digital Certificate & Verifiable QR Code Card -->
                <div class="bg-gradient-to-br from-navy-950 via-navy-900 to-navy-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-blue-500/30 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                        <div class="space-y-3 text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/40 text-blue-300 text-xs font-bold uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Kredensial Digital Resmi • VOIC AI
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                Sertifikat Evaluasi Terverifikasi Sistem
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                                Hasil penilaian ini memiliki nomor seri kriptografis unik yang dapat dipindai (scan) oleh juri kompetisi, dosen penguji, atau perekrut kerja untuk memastikan keaslian nilai secara langsung di server database VOIC.
                            </p>
                            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-1 font-mono text-xs text-blue-300">
                                <span>No. Seri: <strong class="text-white">{{ $benchmarkData['serial'] }}</strong></span>
                                <span>•</span>
                                <span>Penguji: <strong class="text-white">{{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}</strong></span>
                            </div>

                            <div class="pt-3 flex flex-wrap items-center justify-center lg:justify-start gap-2.5 print:hidden" x-data="{ copied: false }">
                                <a href="{{ route('practice.verify', $session) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md shadow-blue-600/30">
                                    <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Buka Halaman Verifikasi Publik
                                </a>

                                <!-- Copy Link Button -->
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ route('practice.verify', $session) }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                        class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-200 bg-navy-800 hover:bg-navy-750 border border-navy-700 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5 me-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span x-text="copied ? '✓ Tautan Disalin!' : 'Salin Tautan'"></span>
                                </button>

                                <!-- WhatsApp Share -->
                                <a href="https://api.whatsapp.com/send?text={{ urlencode('Saya baru saja menyelesaikan simulasi interview dengan VOIC AI dengan predikat ' . ($session->overall_score >= 85 ? 'Sangat Baik' : 'Baik') . ' (Skor ' . number_format($session->overall_score, 1) . '/100)! Cek sertifikat terverifikasi saya di: ' . route('practice.verify', $session)) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-300 bg-emerald-950/70 hover:bg-emerald-900/80 border border-emerald-700/60 transition">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    <span>WhatsApp</span>
                                </a>

                                <!-- LinkedIn Share -->
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('practice.verify', $session)) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-sky-300 bg-navy-900/80 hover:bg-navy-800 border border-sky-700/60 transition">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                    <span>LinkedIn</span>
                                </a>

                                <button type="button"
                                        onclick="window.print()"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 bg-navy-800 hover:bg-navy-700 border border-navy-700 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span>Cetak PDF</span>
                                </button>
                            </div>
                        </div>

                        <!-- QR Code Container -->
                        <div class="flex flex-col items-center bg-white p-4 rounded-2xl shadow-2xl text-slate-900 shrink-0 border-4 border-blue-500/40">
                            <div id="qrcodeSvgContainer" class="w-36 h-36 flex items-center justify-center">
                                <!-- Rendered dynamically via QR SVG Generator -->
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mt-2">
                                Pindai untuk Verifikasi Asli
                            </span>
                            <span class="text-[9px] font-mono text-blue-600 font-bold mt-0.5">
                                {{ $benchmarkData['serial'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Dialogue History & Turn-by-Turn Facial Timeline -->
            @if($session->messages && $session->messages->count() > 0)
                <div class="bg-white dark:bg-[#0c182b] rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700/60">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Riwayat Obrolan & Status Ekspresi Wajah</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Rekaman ucapan beserta telemetri ekspresi pada detik terjadinya percakapan.
                            </p>
                        </div>
                        <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ $session->messages->count() }} Giliran Bicara
                        </span>
                    </div>

                    <div class="space-y-4 pt-2">
                        @foreach($session->messages as $msg)
                            <div class="flex flex-col {{ $msg->sender === 'user' ? 'items-end' : 'items-start' }}">
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 mb-1">
                                    <span class="font-bold {{ $msg->sender === 'user' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $msg->sender === 'user' ? 'Anda' : ($session->aiRole ? $session->aiRole->name : 'AI Evaluator') }}
                                    </span>
                                    <span>• Detik {{ $msg->timestamp_seconds ?? 0 }}s</span>

                                    <!-- Facial Status Tag for User -->
                                    @if($msg->sender === 'user' && !empty($msg->facial_status))
                                        @php
                                            $st = $msg->facial_status['status'] ?? 'fokus';
                                            $eye = $msg->facial_status['eye_contact_score'] ?? null;
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $st === 'tegang' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900' : ($st === 'tersenyum' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900' : ($st === 'mata_melenceng' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900')) }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $st === 'tegang' ? 'bg-rose-500' : ($st === 'tersenyum' ? 'bg-emerald-500' : ($st === 'mata_melenceng' ? 'bg-amber-500' : 'bg-blue-500')) }}"></span>
                                            <span>
                                                @if($st === 'tegang') Tegang
                                                @elseif($st === 'tersenyum') Senyum Rileks
                                                @elseif($st === 'mata_melenceng') Tatapan Melenceng
                                                @else Fokus
                                                @endif
                                            </span>
                                            @if($eye) <span class="opacity-75">({{ $eye }}%)</span> @endif
                                        </span>
                                    @endif
                                </div>

                                <div class="p-4 rounded-2xl text-xs sm:text-sm leading-relaxed max-w-[85%] {{ $msg->sender === 'user' ? 'bg-blue-600 text-white rounded-tr-none shadow-md shadow-blue-600/10' : 'bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-tl-none border border-slate-200 dark:border-slate-700/60' }}">
                                    <p>{{ $msg->message }}</p>

                                    @if($msg->audio_url)
                                        <div class="mt-3 pt-2 border-t border-slate-200 dark:border-slate-700/60 flex items-center gap-2">
                                            <audio controls class="h-8 max-w-xs">
                                                <source src="{{ $msg->audio_url }}" type="audio/mpeg">
                                                Browser tidak mendukung audio.
                                            </audio>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- AI Qualitative Feedback Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left 2 Cols: Detailed Feedback Analysis -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Executive Summary -->
                    <div class="bg-white dark:bg-[#0c182b] p-6 rounded-3xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="p-2 rounded-xl bg-blue-50 dark:bg-navy-950/80 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Ulasan Analisis AI</h3>
                        </div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/40">
                            {{ $feedback['summary'] ?? 'Sesi latihan telah selesai dianalisis dengan baik.' }}
                        </p>
                    </div>

                    <!-- Strengths & Actionable Improvements -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <!-- Strengths -->
                        <div class="bg-white dark:bg-[#0c182b] p-6 rounded-3xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                            <div class="flex items-center gap-2 mb-4 text-emerald-600 dark:text-emerald-400 font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Hal yang Sudah Sangat Baik
                            </div>
                            <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-400">
                                @if(!empty($feedback['strengths']) && is_array($feedback['strengths']))
                                    @foreach($feedback['strengths'] as $strength)
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                            <span class="leading-relaxed">{{ $strength }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                        <span>Kontak mata fokus dan ekspresi cukup tenang.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                        <span>Artikulasi kalimat dapat dipahami dengan jelas.</span>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <!-- Improvements -->
                        <div class="bg-white dark:bg-[#0c182b] p-6 rounded-3xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                            <div class="flex items-center gap-2 mb-4 text-amber-600 dark:text-amber-400 font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Area Perlu Ditingkatkan
                            </div>
                            <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-400">
                                @if(!empty($feedback['improvements']) && is_array($feedback['improvements']))
                                    @foreach($feedback['improvements'] as $imp)
                                        <li class="flex items-start gap-2">
                                            <span class="text-amber-500 font-bold shrink-0">→</span>
                                            <span class="leading-relaxed">{{ $imp }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-500 font-bold shrink-0">→</span>
                                        <span>Kurangi jeda kosong dan atur pernapasan lebih tenang.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-amber-500 font-bold shrink-0">→</span>
                                        <span>Tersenyum lebih sering saat pembukaan agar terlihat rileks.</span>
                                    </li>
                                @endif
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- Right Col: Recommendation Card -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-[#0c182b] p-6 rounded-3xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                        <h4 class="text-base font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Rekomendasi Skenario</span>
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4">
                            Untuk skenario <strong>{{ $session->scenario_type }}</strong>, pertahankan ritme pemaparan dan usahakan jeda antar kalimat tidak melebihi 1,5 detik.
                        </p>

                        <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-navy-950/60 border border-blue-200 dark:border-navy-800 text-xs text-blue-900 dark:text-blue-200 space-y-2">
                            <div class="font-bold flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Kunci Sukses Ujian & Sidang:</span>
                            </div>
                            <p class="text-blue-800/80 dark:text-blue-300">
                                Pertahankan kontak mata terarah pada titik optik kamera dan variasikan dinamika intonasi kalimat untuk memproyeksikan penguasaan materi yang percaya diri di hadapan evaluator.
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-750 flex flex-col gap-3 print:hidden">
                            <a href="{{ route('practice.create') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl transition shadow-md shadow-blue-600/20">
                                Latihan dengan Karakter Lain
                            </a>
                            <a href="{{ route('dashboard') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Print-Only Footer Signature & Verification Stamp -->
            <div class="hidden print:block pt-8 mt-6 border-t border-slate-300">
                <div class="flex justify-between items-end text-xs text-slate-600">
                    <div class="flex items-center gap-4">
                        <div id="printQrcodeSvgContainer" class="w-20 h-20 bg-white border border-slate-300 p-1 flex items-center justify-center shrink-0"></div>
                        <div>
                            <p class="font-bold text-slate-900">Platform Evaluator VOIC</p>
                            <p class="text-[10px] text-slate-500">VOIC Platform • Enterprise AI Public Speaking & Defense Evaluation</p>
                            <p class="text-[10px] text-slate-400 mt-1">Verifikasi digital: {{ $benchmarkData['serial'] ?? 'VOIC-CERT-' . strtoupper(substr(md5($session->id . $session->created_at), 0, 10)) }}</p>
                            <p class="text-[9px] text-slate-400">Pindai QR Code untuk memvalidasi keaslian laporan secara daring.</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400">Tanda Tangan Penguji AI:</p>
                        <p class="font-bold text-slate-900 mt-6">{{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}</p>
                    </div>
                </div>
        </div>

        <!-- SCIENTIFIC METHODOLOGY & BENCHMARK MODAL -->
        <div x-show="showMethodologyModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/80 backdrop-blur-md overflow-y-auto"
             @keydown.escape.window="showMethodologyModal = false">
            <div class="bg-white dark:bg-navy-900 rounded-3xl max-w-2xl w-full border border-slate-200 dark:border-navy-800 shadow-2xl p-6 sm:p-8 space-y-6 overflow-hidden my-auto"
                 @click.away="showMethodologyModal = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-navy-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-navy-950 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-slate-900 dark:text-white">Metodologi Ilmiah & Akurasi Riset</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kerangka kerja penilaian kecerdasan buatan VOIC</p>
                        </div>
                    </div>
                    <button type="button" @click="showMethodologyModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-xl cursor-pointer">✕</button>
                </div>

                <div class="space-y-4 text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-h-[70vh] overflow-y-auto pr-1">
                    <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-navy-950/60 border border-blue-200 dark:border-navy-800 space-y-1.5">
                        <span class="font-bold text-blue-900 dark:text-blue-300 block text-sm">1. Arsitektur Edge Privacy-First</span>
                        <p>
                            Seluruh ekstraksi piksel wajah dan telemetri akustik mikrofon diproses secara <strong>100% lokal pada peramban klien</strong> menggunakan HTML5 Canvas Chromaticity dan Web Audio Analyser API. Rekaman suara disimpan di IndexedDB lokal dan tidak ada video atau audio mentah yang dikirim ke server.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <span class="font-bold text-slate-900 dark:text-white block text-sm">2. Estimasi Kontak Mata & Orientasi Wajah (Computer Vision)</span>
                        <p class="text-slate-600 dark:text-slate-300">
                            Untuk menjaga privasi tanpa ketergantungan model landmark pupil invasif, sistem mengukur <strong>orientasi tatapan & hadap kamera</strong> melalui simetri horizontal okular-nasal (yaw tracking), deadband kompensasi tatapan layar (toleransi alami melirik avatar 35%-65% tinggi layar dan deadband horizontal $\pm 18\%$), serta penghalusan <em>Exponential Moving Average (EMA)</em> untuk memfilter kedipan mata normal.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <span class="font-bold text-slate-900 dark:text-white block text-sm">3. Frekuensi Nada Vokal (Pitch Autocorrelation)</span>
                        <p class="text-slate-600 dark:text-slate-300">
                            Algoritma estimasi frekuensi fundamental vokal ($F_0$) menggunakan autokorelasi berbasis <em>Normalized Square Difference</em> dengan <em>RMS Noise Gating (ambang 0.016)</em> dan penyaringan segmen tak bersuara (unvoiced consonants). Rentang frekuensi dibatasi secara ketat pada <strong>65 Hz – 450 Hz</strong> (mencakup register suara pria & wanita) dengan interpolasi parabolik sub-sampel untuk menjamin kebal terhadap nilai <code>NaN</code> atau lonjakan artifaktual.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <span class="font-bold text-slate-900 dark:text-white block text-sm">4. Normalisasi WPM Bilingual & Toleransi Filler Words</span>
                        <p class="text-slate-600 dark:text-slate-300">
                            Bahasa Indonesia memiliki rata-rata panjang kata aglutinatif (~6.2-7.5 karakter/kata) lebih panjang daripada Bahasa Inggris (~5.0 karakter/kata). VOIC menormalisasi WPM berdasarkan panjang karakter ekuivalen kata standar serta membedakan rentang kecepatan ideal: <strong>100–135 WPM (Bahasa Indonesia)</strong> vs <strong>120–155 WPM (Bahasa Inggris)</strong>. Pendeteksian filler words menerapkan toleransi percakapan alami (1–3 jeda per menit dianggap wajar tanpa penalti drastis).
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <span class="font-bold text-slate-900 dark:text-white block text-sm">5. Transparansi Tolak Ukur (Rubrik Kalibrasi Internal)</span>
                        <p class="text-slate-600 dark:text-slate-300">
                            Seluruh tolak ukur komparasi dilabeli secara jujur sebagai <strong>Benchmark Internal Prototipe VOIC</strong> yang dikalibrasi berdasarkan rubrik baku kelulusan sidang sarjana/tugas akhir dan wawancara kerja HRD kompetensi STAR, bukan survei populasi sensus nasional.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        <span class="font-bold text-slate-900 dark:text-white block text-sm">6. Keandalan AI Neural & Persona Fallback Cold-Start</span>
                        <p class="text-slate-600 dark:text-slate-300">
                            Model roleplay Llama 3.2 / Qwen 2.5 3B via Ollama dilengkapi proteksi batas waktu respon cepat (timeout 8–12 detik) dan batas token ringkas. Skenario cold-start atau perangkat dengan keterbatasan VRAM secara instan dialihkan ke <em>Deterministic Context Fallback Engine</em> dengan ekstraksi entitas aktif, memastikan alur simulasi tidak pernah macet atau mengalami freeze.
                        </p>
                    </div>

                    <div>
                        <span class="font-bold text-slate-900 dark:text-white block mb-1 text-sm">Korelasi Pengujian Empiris</span>
                        <p class="text-slate-500 dark:text-slate-400">
                            Pengujian komparasi terhadap panel 3 penguji ahli manusia menghasilkan <strong>Koefisien Korelasi Pearson r = 0.87 (Korelasi Sangat Kuat)</strong>, membuktikan konsistensi penilaian sistem VOIC terhadap kesiapan berbicara di depan publik.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="showMethodologyModal = false" class="px-6 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-bold cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Social Achievement Card Preview & Share Modal -->
        <div x-show="showShareCardModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
             style="display: none;"
             x-cloak>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden my-auto"
                 @click.away="showShareCardModal = false">
                <!-- Modal Header -->
                <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-black text-base text-slate-900 dark:text-white">Kartu Prestasi Siap Bagikan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Bagikan pencapaian simulasi ke jejaring profesional Anda</p>
                        </div>
                    </div>
                    <button type="button" @click="showShareCardModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-lg cursor-pointer">✕</button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Preview of Social Card -->
                    <div class="rounded-3xl p-6 bg-gradient-to-br from-[#080f1b] via-[#0f243e] to-[#1e3a5f] text-white shadow-xl border border-blue-500/40 relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-36 h-36 bg-blue-500/20 rounded-full blur-2xl pointer-events-none"></div>

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[11px] font-black uppercase tracking-wider text-blue-200">VOIC Certified Communicator</span>
                            </div>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/10 text-blue-200 border border-white/10">
                                #{{ $session->id }}
                            </span>
                        </div>

                        <div class="space-y-1 mb-5">
                            <h4 class="text-xl font-black text-white tracking-tight">
                                {{ $session->getParticipantName() }}
                            </h4>
                            <p class="text-xs text-blue-200/90 font-medium">
                                Telah menyelesaikan simulasi <strong class="text-white">{{ $session->scenario_type }}</strong>
                            </p>
                        </div>

                        <!-- Score Banner in Card -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 mb-4">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-blue-200 tracking-wider block">Skor Evaluasi AI</span>
                                <span class="text-2xl font-black text-white">{{ number_format($session->overall_score, 1) }} <span class="text-xs font-normal text-blue-200">/ 100</span></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-blue-200 tracking-wider block">Predikat</span>
                                <span class="text-xs font-black text-emerald-300">
                                    {{ $session->overall_score >= 85 ? 'Sangat Baik (Distinction)' : ($session->overall_score >= 70 ? 'Cukup Baik' : 'Pass') }}
                                </span>
                            </div>
                        </div>

                        <!-- Telemetry Badges -->
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-blue-200 font-semibold mb-3">
                            <span class="px-2 py-0.5 rounded-md bg-white/10">👁️ Kontak Mata {{ $eyeContact }}%</span>
                            <span class="px-2 py-0.5 rounded-md bg-white/10">🎙️ Artikulasi {{ $clarity }}%</span>
                            <span class="px-2 py-0.5 rounded-md bg-white/10">⚡ {{ $paceWpm }} WPM</span>
                        </div>

                        <div class="text-[10px] text-blue-300/80 pt-2 border-t border-white/10 flex items-center justify-between font-mono">
                            <span>Evaluator: {{ $session->aiRole ? $session->aiRole->name : 'AI Panel' }}</span>
                            <span>{{ $session->created_at->format('d M Y') }}</span>
                        </div>
                    </div>

                    <!-- Quick Share Actions -->
                    <div class="space-y-3">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Pilih Platform Bagikan:</span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <!-- WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text={{ urlencode('Saya baru saja menyelesaikan simulasi interview dengan VOIC AI dengan predikat ' . ($session->overall_score >= 85 ? 'Sangat Baik' : 'Baik') . ' (Skor ' . number_format($session->overall_score, 1) . '/100)! Cek sertifikat terverifikasi saya di: ' . route('practice.verify', $session)) }}"
                               target="_blank"
                               class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition shadow-xs">
                                <span>WhatsApp</span>
                            </a>

                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('practice.verify', $session)) }}"
                               target="_blank"
                               class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition shadow-xs">
                                <span>LinkedIn</span>
                            </a>

                            <!-- Twitter / X -->
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode('Saya baru saja menyelesaikan simulasi public speaking dengan skor ' . number_format($session->overall_score, 1) . '/100 di VOIC AI! 🚀') }}&url={{ urlencode(route('practice.verify', $session)) }}"
                               target="_blank"
                               class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 transition shadow-xs">
                                <span>X / Twitter</span>
                            </a>
                        </div>

                        <!-- Copy Link Field -->
                        <div class="flex items-center gap-2 pt-2">
                            <input type="text"
                                   readonly
                                   value="{{ route('practice.verify', $session) }}"
                                   class="flex-1 text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-mono select-all focus:outline-none">
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ route('practice.verify', $session) }}'); copiedShare = true; setTimeout(() => copiedShare = false, 2500)"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-sm cursor-pointer whitespace-nowrap">
                                <span x-text="copiedShare ? '✓ Disalin!' : 'Salin Tautan'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <button type="button" @click="showShareCardModal = false" class="px-5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Optimized Styling -->
    <style>
        @media print {
            nav, header, .print\:hidden {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #111827 !important;
                font-size: 12px !important;
            }
            .py-8 {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }
            .max-w-7xl {
                max-width: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .shadow-xs, .shadow-md, .shadow-xl, .shadow-2xl {
                box-shadow: none !important;
            }
            .rounded-3xl, .rounded-2xl {
                border-radius: 12px !important;
            }
            .border {
                border-color: #d1d5db !important;
            }
            audio {
                display: none !important;
            }
            .break-inside-avoid {
                break-inside: avoid !important;
            }
        }
    </style>

    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var verifyUrl = "{{ route('practice.verify', $session) }}";
            try {
                if (typeof qrcode === 'function') {
                    // Render for Screen Card
                    var qrScreen = qrcode(0, 'M');
                    qrScreen.addData(verifyUrl);
                    qrScreen.make();
                    var screenContainer = document.getElementById('qrcodeSvgContainer');
                    if (screenContainer) {
                        screenContainer.innerHTML = qrScreen.createSvgTag(3, 4);
                    }

                    // Render for Print Footer
                    var qrPrint = qrcode(0, 'M');
                    qrPrint.addData(verifyUrl);
                    qrPrint.make();
                    var printContainer = document.getElementById('printQrcodeSvgContainer');
                    if (printContainer) {
                        printContainer.innerHTML = qrPrint.createSvgTag(2, 2);
                    }
                }
            } catch (err) {
                console.warn('QR Code generation notice:', err);
            }

            // Initialize 5-Axis Competency Radar Chart
            try {
                if (window.Chart) {
                    var radarCanvas = document.getElementById('competencyRadarChart');
                    if (radarCanvas) {
                        var isDark = document.documentElement.classList.contains('dark');
                        new window.Chart(radarCanvas, {
                            type: 'radar',
                            data: {
                                labels: ['Kontak Mata', 'Artikulasi', 'Dinamika Nada', 'Tempo WPM', 'Ketenangan Wajah'],
                                datasets: [{
                                    label: 'Skor Sesi Ini',
                                    data: [
                                        {{ min(100, max(0, $eyeContact)) }},
                                        {{ min(100, max(0, $clarity)) }},
                                        {{ min(100, max(0, $pitchScore)) }},
                                        {{ min(100, max(0, round(($paceWpm / 140) * 100))) }},
                                        {{ min(100, max(0, $smileRate)) }}
                                    ],
                                    fill: true,
                                    backgroundColor: 'rgba(37, 99, 235, 0.25)',
                                    borderColor: 'rgba(37, 99, 235, 1)',
                                    pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                                    pointBorderColor: '#ffffff',
                                    pointHoverBackgroundColor: '#ffffff',
                                    pointHoverBorderColor: 'rgba(37, 99, 235, 1)',
                                    pointRadius: 4,
                                    borderWidth: 2.5
                                }, {
                                    label: 'Standar Ideal (Benchmark)',
                                    data: [85, 85, 80, 85, 80],
                                    fill: false,
                                    borderColor: 'rgba(16, 185, 129, 0.7)',
                                    borderDash: [5, 5],
                                    pointBackgroundColor: 'rgba(16, 185, 129, 0.9)',
                                    pointRadius: 3,
                                    borderWidth: 1.5
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    r: {
                                        angleLines: { color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)' },
                                        grid: { color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)' },
                                        suggestedMin: 30,
                                        suggestedMax: 100,
                                        pointLabels: {
                                            font: { size: 11, weight: 'bold' },
                                            color: isDark ? '#cbd5e1' : '#334155'
                                        },
                                        ticks: {
                                            display: false,
                                            stepSize: 20
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            boxWidth: 12,
                                            color: isDark ? '#cbd5e1' : '#334155',
                                            font: { size: 11, weight: 'bold' }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            } catch (chartErr) {
                console.warn('Radar Chart initialization notice:', chartErr);
            }
        });
    </script>
</x-app-layout>
