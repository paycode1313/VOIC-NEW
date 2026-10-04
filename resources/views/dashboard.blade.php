<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    AI Speech & Emotion Analytics
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                    {{ __('Dashboard Analisis') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pantau perkembangan performa public speaking, ekspresi wajah, dan intonasi bicaramu.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="#riwayat-latihan"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors shadow-xs">
                    <svg class="w-4 h-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Riwayat Latihan
                </a>
                <a href="{{ route('practice.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 rounded-xl shadow-md shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Mulai Latihan Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-800 p-6 sm:p-8 text-white shadow-xl shadow-indigo-900/10">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 max-w-2xl">
                    <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-lg text-xs font-semibold uppercase tracking-wider text-indigo-100 mb-3">
                        Innofest 2026 Ready 🚀
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Halo, {{ Auth::user()->name }}! 👋
                    </h1>
                    <p class="mt-2 text-indigo-100 text-sm sm:text-base leading-relaxed">
                        Selamat datang di <strong class="text-white">VOIC (Voice & Optics Intelligent Coach)</strong>. Latih kemampuan presentasimu bersama AI untuk sidang skripsi, pitching ide, atau wawancara kerja dengan feedback instan.
                    </p>
                </div>
            </div>

            <!-- Stats Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Average Score Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Rata-rata Skor</span>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $averageScore > 0 ? number_format($averageScore, 1) : '-' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">/ 100</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <span class="inline-block w-2 h-2 rounded-full {{ $averageScore >= 80 ? 'bg-emerald-500' : ($averageScore >= 65 ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                        {{ $averageScore >= 80 ? 'Sangat Baik' : ($averageScore >= 65 ? 'Cukup Baik' : 'Perlu Latihan') }}
                    </div>
                </div>

                <!-- Best Score Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Skor Tertinggi</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/80 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $bestScore > 0 ? number_format($bestScore, 1) : '-' }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">/ 100</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Pencapaian terbaik sejauh ini
                    </div>
                </div>

                <!-- Total Sessions Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Sesi</span>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/80 flex items-center justify-center text-purple-600 dark:text-purple-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $totalSessions }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Latihan Selesai</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Tercatat di database MySQL
                    </div>
                </div>

                <!-- Total Time Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Durasi Bicara</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                            @php
                                $minutes = floor($totalDurationSeconds / 60);
                                $seconds = $totalDurationSeconds % 60;
                            @endphp
                            {{ $minutes }}<span class="text-lg font-medium text-gray-500">m</span> {{ $seconds }}<span class="text-lg font-medium text-gray-500">s</span>
                        </span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Total waktu latihan berbicara
                    </div>
                </div>
            </div>

            <!-- Chart & Analytics Overview Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Progress Line Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                                Grafik Perkembangan Skor
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tren skor keseluruhan (Overall Score) pada 10 sesi latihan terakhir
                            </p>
                        </div>
                        <div class="inline-flex items-center gap-2 text-xs font-semibold px-3 py-1 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Skor Evaluasi AI
                        </div>
                    </div>

                    <div class="relative h-72 w-full">
                        @if($totalSessions > 0)
                            <canvas id="scoreChart"></canvas>
                        @else
                            <div class="h-full flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                                <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Belum ada data latihan</p>
                                <p class="text-xs text-gray-400 mt-1">Selesaikan minimal satu sesi latihan untuk melihat visualisasi grafik performa.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- AI Parameters Breakdown / Tips Card -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Parameter Analisis AI
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            Aspek penilaian cerdas yang dievaluasi selama sesi latihan
                        </p>

                        <div class="space-y-4">
                            <!-- Eye Contact -->
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-750/50 border border-gray-100 dark:border-gray-700/40">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Kontak Mata (Eye Contact)
                                    </span>
                                    <span class="text-indigo-600 dark:text-indigo-400">Optics AI</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Mendeteksi fokus pandangan ke kamera untuk menjaga engagement audiens.
                                </p>
                            </div>

                            <!-- Facial Expression -->
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-750/50 border border-gray-100 dark:border-gray-700/40">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Ekspresi Wajah & Senyum
                                    </span>
                                    <span class="text-purple-600 dark:text-purple-400">Face-API</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Mengukur tingkat senyum dan rileks vs tegang saat menyampaikan materi.
                                </p>
                            </div>

                            <!-- Speech Pace & Clarity -->
                            <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-750/50 border border-gray-100 dark:border-gray-700/40">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                        </svg>
                                        Tempo & Artikulasi
                                    </span>
                                    <span class="text-emerald-600 dark:text-emerald-400">Audio Voice</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                    Menghitung kata per menit (WPM ideal 120-150) dan kejelasan intonasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-xs text-indigo-800 dark:text-indigo-300">
                        💡 <strong>Tips Innofest:</strong> Mulai latihan 3-5 menit sebelum demo agar performa AI terkalibrasi dengan pencahayaan ruangan.
                    </div>
                </div>
            </div>

            <!-- Recent Sessions Section -->
            <div id="riwayat-latihan" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Riwayat Sesi Latihan
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Daftar lengkap sesi latihan terakhir yang tersimpan di sistem
                        </p>
                    </div>

                    @if($totalSessions > 0)
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Menampilkan {{ $recentSessions->count() }} dari {{ $totalSessions }} sesi
                        </span>
                    @endif
                </div>

                @if($recentSessions->isEmpty())
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 dark:text-white">Belum Ada Sesi Latihan</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto mt-1 mb-6">
                            Kamu belum memiliki riwayat latihan. Klik tombol di bawah untuk mencoba simulasi latihan berbicara pertama kalinya!
                        </p>
                        <a href="{{ route('practice.create') }}"
                           class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md transition">
                            Coba Latihan Sekarang
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-850/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <th class="py-3.5 px-6">Skenario / Topik</th>
                                    <th class="py-3.5 px-6">Tanggal & Waktu</th>
                                    <th class="py-3.5 px-6">Durasi</th>
                                    <th class="py-3.5 px-6">Skor AI</th>
                                    <th class="py-3.5 px-6">Catatan Ringkasan AI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-sm">
                                @foreach($recentSessions as $session)
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-750/30 transition-colors">
                                        <td class="py-4 px-6 font-medium text-gray-900 dark:text-white">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                                    @if(str_contains($session->scenario_type, 'Skripsi'))
                                                        🎓
                                                    @elseif(str_contains($session->scenario_type, 'Pitching'))
                                                        🚀
                                                    @elseif(str_contains($session->scenario_type, 'Wawancara'))
                                                        💼
                                                    @else
                                                        🎙️
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $session->scenario_type }}</div>
                                                    <span class="text-xs text-gray-400">Sesi #{{ $session->id }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-gray-600 dark:text-gray-300">
                                            <div>{{ $session->created_at->format('d M Y') }}</div>
                                            <div class="text-xs text-gray-400">{{ $session->created_at->format('H:i') }} WIB</div>
                                        </td>
                                        <td class="py-4 px-6 text-gray-600 dark:text-gray-300">
                                            <span class="inline-flex items-center gap-1 font-mono text-xs px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <span class="font-black text-base {{ $session->overall_score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($session->overall_score >= 65 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">
                                                    {{ number_format($session->overall_score, 1) }}
                                                </span>
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $session->overall_score >= 80 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($session->overall_score >= 65 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800') }}">
                                                    {{ $session->overall_score >= 80 ? 'Hebat' : ($session->overall_score >= 65 ? 'Standar' : 'Perlu Evaluasi') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-gray-600 dark:text-gray-300 max-w-xs">
                                            @if(is_array($session->feedback_notes) && isset($session->feedback_notes['summary']))
                                                <p class="line-clamp-2">{{ $session->feedback_notes['summary'] }}</p>
                                                <div class="mt-1 flex flex-wrap gap-2 text-[10px] text-gray-400">
                                                    @if(isset($session->feedback_notes['eye_contact_score']))
                                                        <span>Mata: {{ $session->feedback_notes['eye_contact_score'] }}%</span>
                                                    @endif
                                                    @if(isset($session->feedback_notes['pace_wpm']))
                                                        <span>Tempo: {{ $session->feedback_notes['pace_wpm'] }} WPM</span>
                                                    @endif
                                                </div>
                                            @elseif(is_string($session->feedback_notes))
                                                <p class="line-clamp-2">{{ $session->feedback_notes }}</p>
                                            @else
                                                <span class="text-gray-400 italic">Tidak ada catatan</span>
                                            @endif
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
        <!-- Chart.js Script Initialization -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('scoreChart');
                if (!ctx || typeof Chart === 'undefined') return;

                const isDark = document.documentElement.classList.contains('dark') ||
                               window.matchMedia('(prefers-color-scheme: dark)').matches;

                const gridColor = isDark ? 'rgba(75, 85, 99, 0.25)' : 'rgba(229, 231, 235, 0.8)';
                const textColor = isDark ? '#9CA3AF' : '#6B7280';

                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 250);
                gradient.addColorStop(0, 'rgba(99, 102, 241, 0.45)');
                gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Skor Keseluruhan',
                            data: @json($chartScores),
                            borderColor: '#6366F1',
                            borderWidth: 3,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#6366F1',
                            pointBorderColor: isDark ? '#1F2937' : '#FFFFFF',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
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
                                backgroundColor: isDark ? '#1F2937' : '#111827',
                                titleColor: '#FFFFFF',
                                bodyColor: '#E0E7FF',
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return ' Skor: ' + context.parsed.y + ' / 100';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: textColor,
                                    font: { size: 11 }
                                }
                            },
                            y: {
                                min: 0,
                                max: 100,
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    color: textColor,
                                    stepSize: 20,
                                    font: { size: 11 }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
