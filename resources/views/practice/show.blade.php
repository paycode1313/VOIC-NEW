<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-700 dark:text-emerald-300 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sesi Latihan Selesai • Hasil Analisis AI
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                    {{ __('Laporan Evaluasi Performa') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sesi #{{ $session->id }} • {{ $session->scenario_type }} • {{ $session->created_at->format('d M Y, H:i') }} WIB
                </p>
            </div>

            <div class="flex items-center gap-3">
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

            <!-- Hero Score Banner -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-100 dark:border-gray-700/60 relative overflow-hidden">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="space-y-3 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $session->overall_score >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($session->overall_score >= 65 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800') }}">
                            <span>Predikat: {{ $session->overall_score >= 85 ? 'Sangat Baik (Distinction)' : ($session->overall_score >= 70 ? 'Cukup Baik (Pass)' : 'Perlu Peningkatan') }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            Evaluasi Skenario: {{ $session->scenario_type }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                            Durasi latihan: <strong>{{ floor($session->duration_seconds / 60) }} menit {{ $session->duration_seconds % 60 }} detik</strong>. Data telemetri kamera dan suara telah diproses oleh modul evaluasi VOIC.
                        </p>
                    </div>

                    <!-- Score Dial Badge -->
                    <div class="shrink-0 flex flex-col items-center">
                        <div class="relative w-36 h-36 rounded-full flex items-center justify-center bg-gradient-to-tr {{ $session->overall_score >= 80 ? 'from-emerald-500 to-teal-400 shadow-emerald-500/25' : ($session->overall_score >= 65 ? 'from-amber-500 to-yellow-400 shadow-amber-500/25' : 'from-rose-500 to-pink-500 shadow-rose-500/25') }} text-white shadow-2xl p-1.5 ring-8 ring-gray-50 dark:ring-gray-750">
                            <div class="w-full h-full rounded-full bg-white dark:bg-gray-800 flex flex-col items-center justify-center text-gray-900 dark:text-white">
                                <span class="text-3xl sm:text-4xl font-black tracking-tight {{ $session->overall_score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                    {{ number_format($session->overall_score, 1) }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Skor Total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        Kejelasan vokal dan dinamika nada bicara.
                    </p>
                </div>
            </div>

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
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ulasan Komprehensif AI</h3>
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
                                        <span>Kurangi jeda jeda kosong dan atur pernapasan lebih tenang.</span>
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
                            Untuk skenario <strong>{{ $session->scenario_type }}</strong>, pertahankan ritme pemaparan dan usahakan jeda antar slide/poin tidak melebihi 2 detik.
                        </p>

                        <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-xs text-indigo-900 dark:text-indigo-200 space-y-2">
                            <div class="font-bold">Kunci Sukses Pameran Innofest:</div>
                            <p class="text-indigo-800/80 dark:text-indigo-300">
                                Tunjukkan kepada juri perbandingan skor sebelum dan sesudah latihan untuk memperlihatkan progres nyata berkat bimbingan AI VOIC!
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-750 flex flex-col gap-3">
                            <a href="{{ route('practice.create') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl transition shadow-md shadow-indigo-600/20">
                                Ulangi Skenario Ini
                            </a>
                            <a href="{{ route('dashboard') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                                Lihat Grafik di Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
