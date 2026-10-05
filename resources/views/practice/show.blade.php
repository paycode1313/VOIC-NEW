<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sesi Latihan Selesai • Hasil Rapor Evaluasi AI
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                    {{ __('Laporan Evaluasi Performa Interaktif') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sesi #{{ $session->id }} • {{ $session->aiRole ? $session->aiRole->name : $session->scenario_type }} • {{ $session->created_at->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <div class="flex items-center gap-3 print:hidden">
                <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-750 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 me-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak / Simpan PDF
                </button>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-750 transition shadow-xs">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('practice.create') }}"
                   class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 rounded-xl shadow-md shadow-indigo-500/25 transition transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Latihan Lagi
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Print-Only Official Report Header -->
            <div class="hidden print:block pb-4 mb-4 border-b-2 border-gray-800 text-center">
                <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 mb-1">
                    VOIC • Voice & Optics Intelligent Coach
                </div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">LAPORAN HASIL EVALUASI SIMULASI</h1>
                <p class="text-xs text-gray-600 mt-1">
                    Sesi #{{ $session->id }} • Skenario: <strong>{{ $session->aiRole ? $session->aiRole->name : $session->scenario_type }}</strong> • {{ $session->created_at->format('d M Y, H:i') }} WIB
                </p>
                <p class="text-[11px] text-gray-500 mt-0.5">
                    Nama Peserta: <strong>{{ $session->user ? $session->user->name : 'Pengunjung Demo' }}</strong> ({{ $session->user ? $session->user->email : '-' }})
                </p>
            </div>

            <!-- Hero Score Banner -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100 dark:border-gray-700/60 relative overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="space-y-3 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $session->overall_score >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($session->overall_score >= 65 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800') }}">
                            <span>Predikat: {{ $session->overall_score >= 85 ? 'Sangat Baik (Distinction)' : ($session->overall_score >= 70 ? 'Cukup Baik (Pass)' : 'Perlu Peningkatan') }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            Evaluasi Simulasi: {{ $session->scenario_type }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                            Durasi latihan: <strong>{{ floor($session->duration_seconds / 60) }} menit {{ $session->duration_seconds % 60 }} detik</strong>.
                            Karakter Penguji: <strong>{{ $session->aiRole ? $session->aiRole->name : 'Evaluator AI' }}</strong>.
                        </p>
                    </div>

                    <!-- Triple Score Summary (Face, Voice, Overall) -->
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- Face Score -->
                        @if($session->face_score !== null)
                            <div class="flex flex-col items-center p-3 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 w-28 text-center">
                                <span class="text-[10px] uppercase font-bold text-indigo-600 dark:text-indigo-400">Skor Wajah</span>
                                <span class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-0.5">
                                    {{ number_format($session->face_score, 1) }}
                                </span>
                                <span class="text-[9px] text-gray-400">Optik Kamera</span>
                            </div>
                        @endif

                        <!-- Voice Score -->
                        @if($session->voice_score !== null)
                            <div class="flex flex-col items-center p-3 rounded-2xl bg-purple-50/60 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900/60 w-28 text-center">
                                <span class="text-[10px] uppercase font-bold text-purple-600 dark:text-purple-400">Skor Suara</span>
                                <span class="text-2xl font-black text-purple-700 dark:text-purple-300 mt-0.5">
                                    {{ number_format($session->voice_score, 1) }}
                                </span>
                                <span class="text-[9px] text-gray-400">Artikulasi</span>
                            </div>
                        @endif

                        <!-- Overall Score Dial -->
                        <div class="relative w-32 h-32 rounded-full flex items-center justify-center bg-gradient-to-tr {{ $session->overall_score >= 80 ? 'from-emerald-500 to-teal-400 shadow-emerald-500/25' : ($session->overall_score >= 65 ? 'from-amber-500 to-yellow-400 shadow-amber-500/25' : 'from-rose-500 to-pink-500 shadow-rose-500/25') }} text-white shadow-2xl p-1 ring-6 ring-gray-50 dark:ring-gray-750">
                            <div class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex flex-col items-center justify-center text-gray-900 dark:text-white">
                                <span class="text-3xl font-black tracking-tight {{ $session->overall_score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                    {{ number_format($session->overall_score, 1) }}
                                </span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Skor Total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Official AI Conclusion Card -->
            @if($session->ai_conclusion)
                <div class="p-6 rounded-3xl bg-gradient-to-r from-indigo-900 via-indigo-950 to-purple-950 text-white shadow-xl border border-indigo-800/60 relative overflow-hidden">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl shrink-0 border border-white/20">
                            @if($session->aiRole && $session->aiRole->role_type === 'dosen_penguji')
                                🎓
                            @elseif($session->aiRole && $session->aiRole->role_type === 'hrd')
                                💼
                            @else
                                🚀
                            @endif
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-300">
                                Kesimpulan Resmi Penguji (AI Roleplay Verdict)
                            </span>
                            <h3 class="text-lg font-bold text-white">
                                {{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}
                            </h3>
                            <p class="text-xs sm:text-sm text-indigo-100 leading-relaxed pt-1">
                                "{{ $session->ai_conclusion }}"
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 4 Parameters Telemetry Grid -->
            @php
                $feedback = is_array($session->feedback_notes) ? $session->feedback_notes : [];
                $eyeContact = $feedback['eye_contact_score'] ?? 80;
                $smileRate = $feedback['smile_rate'] ?? 75;
                $paceWpm = $feedback['pace_wpm'] ?? 130;
                $clarity = $feedback['clarity_score'] ?? 85;
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Eye Contact Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontak Mata</span>
                        <span class="text-xl">👁️</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $eyeContact }}%</span>
                        <span class="text-xs text-gray-400">terfokus</span>
                    </div>
                    <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-600" style="width: {{ $eyeContact }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Tingkat konsistensi tatapan ke kamera saat berbicara.
                    </p>
                </div>

                <!-- Facial Expression Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ekspresi Wajah</span>
                        <span class="text-xl">😊</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $smileRate }}%</span>
                        <span class="text-xs text-gray-400">rileks</span>
                    </div>
                    <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-600" style="width: {{ $smileRate }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Tingkat senyum dan ekspresi rileks vs ekspresi tegang.
                    </p>
                </div>

                <!-- Pace WPM Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tempo Bicara</span>
                        <span class="text-xl">⏱️</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $paceWpm }}</span>
                        <span class="text-xs text-gray-400">WPM</span>
                    </div>
                    <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600" style="width: {{ min(100, ($paceWpm / 150) * 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Kata per menit (ideal presentasi: 120-150 WPM).
                    </p>
                </div>

                <!-- Clarity Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artikulasi & Energi</span>
                        <span class="text-xl">🎙️</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $clarity }}%</span>
                        <span class="text-xs text-gray-400">jelas</span>
                    </div>
                    <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-600" style="width: {{ $clarity }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        @if(isset($feedback['avg_volume']) && $feedback['avg_volume'] > 0)
                            Energi vokal: <strong>{{ $feedback['avg_volume'] }}%</strong> • {{ $feedback['total_words'] ?? 0 }} kata
                            @if(isset($feedback['filler_words_count']))
                                • <span class="{{ $feedback['filler_words_count'] > 2 ? 'text-amber-500 dark:text-amber-400 font-bold' : 'text-emerald-500 dark:text-emerald-400 font-bold' }}">{{ $feedback['filler_words_count'] }} kata gumaman (filler)</span>
                            @endif
                        @else
                            Kejelasan vokal dan dinamika nada bicara.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Industry Benchmark & National Percentile Comparison -->
            @if(isset($benchmarkData))
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100 dark:border-gray-700/60 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700/60">
                        <div>
                            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold uppercase tracking-wider mb-1">
                                <span>🏆</span>
                                <span>Tolak Ukur Standar Industri & Sidang Nasional</span>
                            </div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">
                                Komparasi Metrik Performa Anda vs Standar Kelulusan
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dikalibrasi berdasarkan standar penilaian HRD profesional dan dewan dosen penguji sidang tugas akhir.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md text-center">
                                <span class="text-[10px] font-bold uppercase tracking-wider block opacity-90">Peringkat Komparatif</span>
                                <span class="text-sm font-extrabold block">{{ $benchmarkData['percentile_rank'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Side-by-Side Comparison Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($benchmarkData['metrics'] as $key => $metric)
                            <div class="p-4 rounded-2xl border transition-all {{ $metric['is_superior'] ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/40 dark:bg-emerald-950/20' : 'border-amber-200 dark:border-amber-800/80 bg-amber-50/40 dark:bg-amber-950/20' }}">
                                <span class="text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 block mb-1">
                                    {{ $metric['label'] }}
                                </span>
                                <div class="flex items-baseline justify-between mb-1">
                                    <span class="text-2xl font-black text-gray-900 dark:text-white">
                                        {{ $metric['candidate_val'] }}
                                    </span>
                                    <span class="text-xs font-bold {{ $metric['is_superior'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $metric['delta_val'] }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mb-2">
                                    Standar: <strong>{{ $metric['benchmark_val'] }}</strong>
                                </div>
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold {{ $metric['is_superior'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/80 dark:text-amber-300' }}">
                                    {{ $metric['status_badge'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Readiness Index Progress Meter -->
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="space-y-1 w-full sm:w-2/3">
                            <div class="flex items-center justify-between text-xs font-bold text-gray-700 dark:text-gray-200">
                                <span>Indeks Kesiapan Menghadapi Sidang / Kerja (Readiness Score):</span>
                                <span class="text-indigo-600 dark:text-indigo-400 font-extrabold">{{ $benchmarkData['readiness_index'] }}%</span>
                            </div>
                            <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 transition-all duration-500" style="width: {{ $benchmarkData['readiness_index'] }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 block">
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

                <!-- Digital Certificate & Verifiable QR Code Card -->
                <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-500/40 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                        <div class="space-y-3 text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Kredensial Digital Resmi • VOIC AI
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                Sertifikat Evaluasi Terverifikasi Sistem
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                                Hasil penilaian ini memiliki nomor seri kriptografis unik yang dapat dipindai (scan) oleh juri kompetisi, dosen penguji, atau perekrut kerja untuk memastikan keaslian nilai secara langsung di server database VOIC.
                            </p>
                            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-1 font-mono text-xs text-indigo-300">
                                <span>No. Seri: <strong class="text-white">{{ $benchmarkData['serial'] }}</strong></span>
                                <span>•</span>
                                <span>Penguji: <strong class="text-white">{{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}</strong></span>
                            </div>

                            <div class="pt-3 flex flex-wrap items-center justify-center lg:justify-start gap-3 print:hidden">
                                <a href="{{ route('practice.verify', $session) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-md shadow-indigo-600/30">
                                    <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Buka Halaman Verifikasi Publik
                                </a>
                                <button type="button"
                                        onclick="window.print()"
                                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition">
                                    Cetak Sertifikat Resmi PDF
                                </button>
                            </div>
                        </div>

                        <!-- QR Code Container -->
                        <div class="flex flex-col items-center bg-white p-4 rounded-2xl shadow-2xl text-slate-900 shrink-0 border-4 border-indigo-400/40">
                            <div id="qrcodeSvgContainer" class="w-36 h-36 flex items-center justify-center">
                                <!-- Rendered dynamically via QR SVG Generator -->
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mt-2">
                                Pindai untuk Verifikasi Asli
                            </span>
                            <span class="text-[9px] font-mono text-indigo-600 font-bold mt-0.5">
                                {{ $benchmarkData['serial'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Dialogue History & Turn-by-Turn Facial Timeline -->
            @if($session->messages && $session->messages->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>💬</span>
                                <span>Riwayat Obrolan Bolak-Balik & Status Ekspresi Wajah</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Rekaman ucapan beserta telemetri ekspresi pada detik terjadinya percakapan.
                            </p>
                        </div>
                        <span class="text-xs font-mono font-semibold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ $session->messages->count() }} Giliran Bicara
                        </span>
                    </div>

                    <div class="space-y-4 pt-2">
                        @foreach($session->messages as $msg)
                            <div class="flex flex-col {{ $msg->sender === 'user' ? 'items-end' : 'items-start' }}">
                                <div class="flex items-center gap-2 text-[11px] text-gray-400 mb-1">
                                    <span class="font-bold {{ $msg->sender === 'user' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $msg->sender === 'user' ? 'Anda' : ($session->aiRole ? $session->aiRole->name : 'AI Evaluator') }}
                                    </span>
                                    <span>• Detik {{ $msg->timestamp_seconds ?? 0 }}s</span>

                                    <!-- Facial Status Tag for User -->
                                    @if($msg->sender === 'user' && !empty($msg->facial_status))
                                        @php
                                            $st = $msg->facial_status['status'] ?? 'fokus';
                                            $eye = $msg->facial_status['eye_contact_score'] ?? null;
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $st === 'tegang' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : ($st === 'tersenyum' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : ($st === 'mata_melenceng' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300')) }}">
                                            @if($st === 'tegang') 😬 Tegang
                                            @elseif($st === 'tersenyum') 😊 Senyum Rileks
                                            @elseif($st === 'mata_melenceng') 👀 Tatapan Melenceng
                                            @else 🎯 Fokus
                                            @endif
                                            @if($eye) ({{ $eye }}%) @endif
                                        </span>
                                    @endif
                                </div>

                                <div class="p-4 rounded-2xl text-xs sm:text-sm leading-relaxed max-w-[85%] {{ $msg->sender === 'user' ? 'bg-indigo-600 text-white rounded-tr-none shadow-md shadow-indigo-600/10' : 'bg-gray-50 dark:bg-gray-750 text-gray-800 dark:text-gray-200 rounded-tl-none border border-gray-200 dark:border-gray-700/60' }}">
                                    <p>{{ $msg->message }}</p>

                                    @if($msg->audio_url)
                                        <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-700/60 flex items-center gap-2">
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
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ulasan Analisis AI</h3>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-750/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/40">
                            {{ $feedback['summary'] ?? 'Sesi latihan telah selesai dianalisis dengan baik.' }}
                        </p>
                    </div>

                    <!-- Strengths & Actionable Improvements -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <!-- Strengths -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center gap-2 mb-4 text-emerald-600 dark:text-emerald-400 font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Hal yang Sudah Sangat Baik
                            </div>
                            <ul class="space-y-3 text-xs text-gray-600 dark:text-gray-400">
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
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                            <div class="flex items-center gap-2 mb-4 text-amber-600 dark:text-amber-400 font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Area Perlu Ditingkatkan
                            </div>
                            <ul class="space-y-3 text-xs text-gray-600 dark:text-gray-400">
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

                <!-- Right Col: Innofest Exhibition Tips Card -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                        <h4 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <span>🚀</span>
                            <span>Rekomendasi Skenario</span>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                            Untuk skenario <strong>{{ $session->scenario_type }}</strong>, pertahankan ritme pemaparan dan usahakan jeda antar kalimat tidak melebihi 1,5 detik.
                        </p>

                        <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-xs text-indigo-900 dark:text-indigo-200 space-y-2">
                            <div class="font-bold">Kunci Sukses Pameran Innofest:</div>
                            <p class="text-indigo-800/80 dark:text-indigo-300">
                                Tunjukkan kepada juri perbandingan skor optik kamera dan suara sebelum vs sesudah latihan untuk memperlihatkan progres nyata berkat bimbingan VOIC AI!
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-750 flex flex-col gap-3 print:hidden">
                            <a href="{{ route('practice.create') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition shadow-md shadow-indigo-600/20">
                                Latihan dengan Karakter Lain
                            </a>
                            <a href="{{ route('dashboard') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Print-Only Footer Signature & Verification Stamp -->
            <div class="hidden print:block pt-8 mt-6 border-t border-gray-300">
                <div class="flex justify-between items-end text-xs text-gray-600">
                    <div class="flex items-center gap-4">
                        <div id="printQrcodeSvgContainer" class="w-20 h-20 bg-white border border-gray-300 p-1 flex items-center justify-center shrink-0"></div>
                        <div>
                            <p class="font-bold text-gray-900">Platform Evaluator VOIC</p>
                            <p class="text-[10px] text-gray-500">Innofest 2026 • AI Public Speaking & Presentation Coach</p>
                            <p class="text-[10px] text-gray-400 mt-1">Verifikasi digital: {{ $benchmarkData['serial'] ?? 'VOIC-CERT-' . strtoupper(substr(md5($session->id . $session->created_at), 0, 10)) }}</p>
                            <p class="text-[9px] text-gray-400">Pindai QR Code untuk memvalidasi keaslian laporan secara daring.</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400">Tanda Tangan Penguji AI:</p>
                        <p class="font-bold text-gray-900 mt-6">{{ $session->aiRole ? $session->aiRole->name : 'Evaluator VOIC' }}</p>
                    </div>
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
        });
    </script>
</x-app-layout>
