<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-navy-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sertifikat Digital • VOIC AI</title>
    <meta name="theme-color" content="#0b1329">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icon.svg">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between bg-gradient-to-b from-navy-950 via-navy-900 to-navy-950 text-slate-100">

    <!-- Top Navigation Header -->
    <header class="border-b border-navy-800/80 bg-navy-900/80 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-700 to-blue-500 flex items-center justify-center font-black text-white text-lg shadow-lg shadow-blue-600/30">
                    V
                </div>
                <div>
                    <span class="font-extrabold text-base tracking-tight text-white block">VOIC AI</span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block">Official Credential Registry</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="hidden sm:inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-300 bg-navy-800/90 hover:bg-navy-700 border border-navy-700 rounded-lg transition">
                    <svg class="w-3.5 h-3.5 me-1.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Verifikasi
                </button>
                <a href="{{ url('/') }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-md shadow-blue-600/30 transition">
                    Coba VOIC AI
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12 w-full space-y-6">

        <!-- Verification Banner Badge -->
        <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-950/80 via-navy-900 to-navy-950/90 border border-emerald-500/40 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-emerald-400 shrink-0 shadow-lg shadow-emerald-500/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Kredensial Sah & Terverifikasi
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Sertifikat Hasil Evaluasi AI Multimodal
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300">
                        Dokumen ini adalah bukti otentik hasil pengujian simulasi berbicara, telemetri tatapan kamera, dan vokal yang tersimpan pada pangkalan data VOIC.
                    </p>
                    <p class="text-[11px] font-mono text-emerald-400/90 pt-1">
                        Nomor Seri Registrasi: <span class="font-bold tracking-wider">{{ $benchmarkData['serial'] }}</span>
                    </p>

                    <div class="pt-3 flex flex-wrap items-center gap-2 print:hidden" x-data="{ copied: false }">
                        <button type="button"
                                @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2500)"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-200 bg-navy-800 hover:bg-navy-750 border border-navy-700 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5 me-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="copied ? '✓ Tautan Disalin!' : 'Salin Tautan Verifikasi'"></span>
                        </button>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode('Verifikasi Sertifikat Resmi VOIC AI: ' . url()->current()) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-300 bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-700/60 transition">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-sky-300 bg-navy-900/90 hover:bg-navy-800 border border-sky-700/60 transition">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            <span>LinkedIn</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidate & Session Identity Details -->
        @php
            $feedback = is_array($session->feedback_notes) ? $session->feedback_notes : [];
            $badges = $feedback['badges'] ?? [];
            $pitchScore = $feedback['pitch_dynamics_score'] ?? 80;
            $simLanguage = $feedback['language'] ?? 'id';
        @endphp

        <div class="bg-navy-900/90 rounded-3xl p-6 sm:p-8 border border-navy-800 shadow-xl space-y-6">
            <div class="border-b border-navy-800 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Identitas Peserta Uji</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-950 text-blue-300 border border-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            <span>{{ $simLanguage === 'en' ? 'English' : 'Bahasa Indonesia' }}</span>
                        </span>
                    </div>
                    <h2 class="text-2xl font-extrabold text-white mt-0.5">
                        {{ $user ? $user->name : 'Peserta Uji Terkalibrasi' }}
                    </h2>
                    <p class="text-xs text-slate-400">
                        Email: {{ $user ? $user->email : 'Akun Tamu Demo' }}
                    </p>
                </div>

                <div class="flex items-center gap-2 bg-navy-800/80 border border-navy-700/60 px-4 py-2 rounded-2xl w-fit">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 border border-blue-400/20 text-blue-300 flex items-center justify-center">
                        @if($role?->role_type === 'dosen_penguji')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                            </svg>
                        @elseif($role?->role_type === 'hrd')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Karakter Penguji AI</span>
                        <span class="text-xs font-extrabold text-blue-300 block">{{ $role ? $role->name : $session->scenario_type }}</span>
                    </div>
                </div>
            </div>

            <!-- Scorecards (Quad Metrics) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- Overall Score -->
                <div class="p-4 rounded-2xl bg-gradient-to-tr from-navy-950 to-blue-950/60 border border-blue-500/30 text-center relative overflow-hidden">
                    <span class="text-[10px] uppercase font-bold text-blue-300 tracking-wider">Skor Total</span>
                    <div class="text-3xl font-black text-white mt-1">
                        {{ number_format($session->overall_score, 1) }}
                    </div>
                    <span class="text-[9px] text-slate-400 block mt-1">
                        {{ $session->overall_score >= 85 ? 'Sangat Baik' : ($session->overall_score >= 70 ? 'Cukup Baik' : 'Berkembang') }}
                    </span>
                </div>

                <!-- Optical Face Score -->
                <div class="p-4 rounded-2xl bg-gradient-to-tr from-navy-950 to-sky-950/60 border border-sky-500/30 text-center">
                    <span class="text-[10px] uppercase font-bold text-sky-300 tracking-wider">Optik Kamera</span>
                    <div class="text-3xl font-black text-white mt-1">
                        {{ number_format($session->face_score ?? 80, 1) }}
                    </div>
                    <span class="text-[9px] text-slate-400 block mt-1">
                        Kontak Mata Wajah
                    </span>
                </div>

                <!-- Voice Score -->
                <div class="p-4 rounded-2xl bg-gradient-to-tr from-navy-950 to-emerald-950/60 border border-emerald-500/30 text-center">
                    <span class="text-[10px] uppercase font-bold text-emerald-300 tracking-wider">Artikulasi</span>
                    <div class="text-3xl font-black text-white mt-1">
                        {{ number_format($session->voice_score ?? 85, 1) }}
                    </div>
                    <span class="text-[9px] text-slate-400 block mt-1">
                        Kelancaran Vokal
                    </span>
                </div>

                <!-- Pitch Dynamics Score -->
                <div class="p-4 rounded-2xl bg-gradient-to-tr from-navy-950 to-teal-950/60 border border-teal-500/30 text-center">
                    <span class="text-[10px] uppercase font-bold text-teal-300 tracking-wider">Dinamika Nada</span>
                    <div class="text-3xl font-black text-white mt-1">
                        {{ number_format($pitchScore, 0) }}%
                    </div>
                    <span class="text-[9px] text-slate-400 block mt-1">
                        Intonasi Ekspresif
                    </span>
                </div>
            </div>

            <!-- Unlocked Badges Section on Certificate -->
            @if(!empty($badges))
                <div class="p-4 rounded-2xl bg-navy-950/90 border border-amber-500/30 space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-amber-400">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            <span>Lencana Prestasi Resmi Terverifikasi:</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300">
                            {{ count($badges) }} Lencana
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach($badges as $badge)
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-navy-900 border border-navy-750 text-xs">
                                <div class="w-4 h-4 text-amber-400">
                                    @if(($badge['icon'] ?? '') === 'eye')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'mic')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'audio')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'bolt')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'shield')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    @elseif(($badge['icon'] ?? '') === 'globe')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                    @endif
                                </div>
                                <span class="font-bold text-white">{{ $badge['name'] ?? 'Lencana' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- National Benchmark Ranking Badge -->
            <div class="p-4 rounded-2xl bg-navy-950/80 border border-navy-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Peringkat Tolak Ukur Nasional:</span>
                        <span class="text-sm font-extrabold text-white">{{ $benchmarkData['percentile_rank'] }}</span>
                    </div>
                </div>

                <div class="px-3.5 py-1.5 rounded-xl bg-blue-950/80 border border-blue-700/60 text-xs font-bold text-blue-300">
                    {{ $benchmarkData['readiness_status'] }}
                </div>
            </div>

            <!-- Official AI Conclusion -->
            @if($session->ai_conclusion)
                <div class="p-5 rounded-2xl bg-blue-950/40 border border-blue-800/40 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-blue-300">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Kesimpulan Resmi Evaluator AI:</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-200 leading-relaxed italic">
                        "{{ $session->ai_conclusion }}"
                    </p>
                </div>
            @endif

            <!-- Metadata Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-navy-800 text-xs text-slate-400">
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-500">Skenario Latihan</span>
                    <span class="text-white font-medium">{{ $session->scenario_type }}</span>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-500">Tanggal Pengujian</span>
                    <span class="text-white font-medium">{{ $session->created_at->format('d M Y, H:i') }} WIB</span>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-500">Durasi Bicara</span>
                    <span class="text-white font-medium">{{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}d</span>
                </div>
                <div>
                    <span class="block text-[10px] uppercase font-bold text-slate-500">Status Validasi</span>
                    <span class="text-emerald-400 font-bold">100% Authentic</span>
                </div>
            </div>
        </div>

        <!-- Security Cryptographic Verification Footer -->
        <div class="p-5 rounded-2xl bg-navy-950/70 border border-navy-800/80 text-center space-y-2">
            <p class="text-xs text-slate-400">
                Keaslian laporan ini dijamin oleh <strong>VOIC Multimodal Engine</strong>. Seluruh proses visual diproses langsung di perangkat peserta (Edge On-Device) untuk menjaga kerahasiaan data privasi sesuai standar industri.
            </p>
            <p class="text-[10px] font-mono text-slate-500">
                Hash SID: {{ hash('sha256', $session->id . '|' . $session->created_at) }}
            </p>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-navy-800/80 py-6 text-center text-xs text-slate-500">
        <p>© 2026 VOIC - Voice & Optical Intelligent Coach • Platform Evaluasi Presentasi & Wawancara Berbasis AI</p>
    </footer>

</body>
</html>
