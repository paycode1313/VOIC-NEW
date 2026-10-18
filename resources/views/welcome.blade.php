<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VOIC - AI Multimodal Public Speaking & Interview Coach</title>
    <meta name="theme-color" content="#0b1329">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icon.svg">

    <!-- Theme Initialization Script -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('voic_theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(err) {
                    console.warn('SW registration skipped:', err);
                });
            });
        }
    </script>

    <!-- Fonts & Preload Performance -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preload" as="image" href="{{ asset('images/avatars/dosen.jpg') }}">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-[#080f1b] text-slate-800 dark:text-slate-100 antialiased font-sans selection:bg-blue-600 selection:text-white transition-colors duration-200">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-50 backdrop-blur-2xl bg-white/75 dark:bg-[#080f1b]/75 border-b border-slate-200/70 dark:border-white/10 shadow-xs transition-colors duration-200"
         x-data="{ mobileNavOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <a href="/" class="flex items-center group">
                    <x-application-logo variant="horizontal" class="h-7 sm:h-11 w-auto max-w-[130px] sm:max-w-none group-hover:scale-[1.02] transition-transform" />
                </a>

                <!-- Nav Menu Desktop -->
                <div class="hidden md:flex items-center gap-6 text-sm font-bold text-slate-600 dark:text-slate-300">
                    <a href="#fitur" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Teknologi Multimodal</a>
                    <a href="#karakter-ai" class="hover:text-blue-600 dark:hover:text-blue-400 transition">3 Karakter Penguji</a>
                    <a href="#keunggulan" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Standar Industri</a>
                    <a href="#faq" class="hover:text-blue-600 dark:hover:text-blue-400 transition">FAQ</a>
                </div>

                <!-- Right Actions & Theme Switcher -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Dark / Light Theme Toggle Button -->
                    <button type="button"
                            onclick="
                                const isDark = document.documentElement.classList.toggle('dark');
                                localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                            "
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition cursor-pointer"
                            title="Ganti Mode Gelap / Terang">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5">
                            Buka Dashboard
                            <svg class="w-4 h-4 ms-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    @else
                        <!-- Instant Guest Practice Mode Button -->
                        <a href="{{ route('demo.create') }}"
                           class="hidden sm:inline-flex items-center px-3.5 py-2 text-xs font-bold rounded-xl text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse me-1.5"></span>
                            Coba Simulasi Instan
                        </a>

                        <a href="{{ route('login') }}"
                           class="px-3 py-2 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white transition">
                            Masuk
                        </a>

                        <a href="{{ route('register') }}"
                           class="hidden md:inline-flex items-center px-4 py-2 text-xs sm:text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-600/30 transition transform hover:-translate-y-0.5">
                            Daftar
                        </a>
                    @endauth

                    <!-- Mobile Hamburger Button -->
                    <button type="button"
                            @click="mobileNavOpen = !mobileNavOpen"
                            class="md:hidden p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                            aria-label="Toggle menu">
                        <svg x-show="!mobileNavOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileNavOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer (Slide Down) -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden px-4 pt-2 pb-6 bg-white/95 dark:bg-[#0b1329]/95 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 space-y-3 shadow-xl"
             x-cloak>
            <div class="flex flex-col space-y-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                <a @click="mobileNavOpen = false" href="#fitur" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Teknologi Multimodal</a>
                <a @click="mobileNavOpen = false" href="#karakter-ai" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">3 Karakter Penguji</a>
                <a @click="mobileNavOpen = false" href="#keunggulan" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Standar Industri</a>
                <a @click="mobileNavOpen = false" href="#faq" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Pertanyaan Umum (FAQ)</a>
            </div>

            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-2">
                @guest
                    <a href="{{ route('demo.create') }}" class="w-full text-center px-4 py-2.5 rounded-xl font-bold text-xs text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700/60">
                        🎪 Coba Mode Booth Tamu (Tanpa Login)
                    </a>
                    <a href="{{ route('register') }}" class="w-full text-center px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-blue-600 shadow-md">
                        Daftar Akun Baru
                    </a>
                @endguest
            </div>
        </div>
    </nav>

    <!-- Hero Section with Executive Video Conference Mockup HUD -->
    <section class="relative overflow-hidden pt-8 pb-16 sm:pt-16 sm:pb-28 bg-slate-50/50 dark:bg-[#070e1b] transition-colors duration-200"
             x-data="{
                activeRole: 'dosen',
                roles: {
                    dosen: {
                        id: 'dosen_penguji',
                        name: 'VOIC - Dosen Penguji',
                        tag: 'Ketua Dewan Penguji Sidang',
                        tabLabel: 'Dosen Penguji',
                        badge: 'Akademik & Metodologi',
                        avatar: '{{ asset('images/avatars/dosen.jpg') }}',
                        accent: 'blue',
                        statusText: 'Sedang Menguji Metodologi (Live Voice)',
                        quote: 'Saudara mahasiswa, coba jelaskan kebaruan metodologi penelitian Anda dan bagaimana mengantisipasi risiko overfitting pada model?',
                        candidateReply: 'Baik Dosen Penguji, untuk mencegah overfitting kami menerapkan teknik Data Augmentation serta Dropout 0.3...',
                        wpm: '128 WPM',
                        filler: '0',
                        pitch: '142 Hz (Stabil)',
                        gaze: '94.6% Terkunci',
                        cta: 'Mulai Simulasi Sidang Skripsi →'
                    },
                    hrd: {
                        id: 'hrd',
                        name: 'VOIC - HRD',
                        tag: 'Senior Talent Acquisition Lead',
                        tabLabel: 'HRD Recruiter',
                        badge: 'Interview & STAR Method',
                        avatar: '{{ asset('images/avatars/hrd.jpg') }}?v=4',
                        accent: 'sky',
                        statusText: 'Mengevaluasi Culture Fit (Live Voice)',
                        quote: 'Hmm... dari pengalaman memimpin tim Anda, ceritakan saat ada anggota yang pasif, bagaimana Anda mengatasinya?',
                        candidateReply: 'Saat itu saya mengadakan 1-on-1 empatik untuk memetakan kendala mereka, lalu mendelegasikan tugas sesuai keahlian...',
                        wpm: '134 WPM',
                        filler: '1',
                        pitch: '156 Hz (Hangat)',
                        gaze: '96.2% Terkunci',
                        cta: 'Mulai Simulasi Wawancara HRD →'
                    },
                    investor: {
                        id: 'investor',
                        name: 'VOIC - Investor',
                        tag: 'Managing Partner & Angel VC',
                        tabLabel: 'Investor Pitching',
                        badge: 'Finansial & Traksi Bisnis',
                        avatar: '{{ asset('images/avatars/investor.jpg') }}',
                        accent: 'indigo',
                        statusText: 'Menguji Validasi Finansial (Live Voice)',
                        quote: 'Waduh, angkanya menarik, tapi berapa Customer Acquisition Cost Anda dan kapan target break-even point tercapai?',
                        candidateReply: 'CAC kami saat ini di Rp 45.000 dengan rasio LTV:CAC 3.8x, dan proyeksi break-even dicapai pada bulan ke-14...',
                        wpm: '142 WPM',
                        filler: '0',
                        pitch: '148 Hz (Tegas)',
                        gaze: '93.8% Terkunci',
                        cta: 'Mulai Simulasi Pitching Investor →'
                    }
                }
             }">

        <!-- Ambient Mesh Light Aura Orbs & Minimalist Matrix Grid -->
        <div class="absolute inset-0 -z-10 pointer-events-none overflow-hidden select-none">
            <!-- Center Cyan/Blue Pulse Glow -->
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] sm:w-[64rem] h-[30rem] rounded-full bg-gradient-to-b from-blue-600/20 via-sky-500/15 to-transparent blur-[120px] animate-pulse-slow"></div>
            <!-- Left Emerald Accent Glow -->
            <div class="absolute top-1/4 -left-32 w-80 h-80 rounded-full bg-emerald-500/10 blur-[100px] animate-float-slow"></div>
            <!-- Right Indigo Accent Glow -->
            <div class="absolute top-1/2 -right-32 w-80 h-80 rounded-full bg-indigo-600/15 blur-[100px] animate-float-delayed"></div>
            <!-- High-Tech Micro Dot Grid Texture with Smooth Radial Mask -->
            <div class="absolute inset-0 bg-[radial-gradient(rgba(59,130,246,0.14)_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_15%,#000_70%,transparent_100%)] opacity-70 dark:opacity-90"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header Text & Hero Headline -->
            <div class="text-center max-w-4xl mx-auto space-y-5">
                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-1.5 rounded-full glass-pill-nav text-[11px] sm:text-xs font-bold text-blue-700 dark:text-blue-300 shadow-xs max-w-full text-center">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="leading-tight tracking-wide">AI Multimodal Public Speaking & Defense Coach</span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-7xl font-black tracking-tight text-slate-900 dark:text-white leading-[1.14] sm:leading-[1.08] break-words">
                    Kuasai Panggung Sidang & Wawancara Bersama <span class="bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600 dark:from-blue-400 dark:via-sky-300 dark:to-cyan-200 bg-clip-text text-transparent">VOIC AI</span>
                </h1>

                <!-- Subheadline -->
                <p class="text-sm sm:text-lg lg:text-xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto leading-relaxed">
                    Sparring partner AI interaktif pertama yang menggabungkan <strong>telemetri tatapan kamera (Gaze Tracking)</strong>, <strong>spektrum akustik suara</strong>, dan <strong>roleplay kontekstual</strong> untuk persiapan sidang skripsi, wawancara kerja, dan pitching startup.
                </p>

                <!-- CTA Action Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 sm:gap-4">
                    @auth
                        <a href="{{ route('practice.create') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-6 sm:px-8 py-3.5 sm:py-4 text-sm sm:text-base font-extrabold rounded-2xl text-white bg-blue-600 hover:bg-blue-500 shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5 text-center">
                            Mulai Ruang Simulasi Interaktif
                            <svg class="w-5 h-5 ms-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('demo.create') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-6 sm:px-8 py-3.5 sm:py-4 text-sm sm:text-base font-extrabold rounded-2xl text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 shadow-xl shadow-emerald-600/30 transition transform hover:-translate-y-0.5 text-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse me-2 shrink-0"></span>
                            Coba Demo Booth Tamu (Tanpa Login)
                        </a>
                        <a href="{{ route('register') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-6 sm:px-8 py-3.5 sm:py-4 text-sm sm:text-base font-bold rounded-2xl text-slate-800 dark:text-slate-200 glass-pill-nav hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-xs text-center border border-slate-300/80 dark:border-white/10">
                            Daftar Akun Baru
                        </a>
                    @endauth
                </div>

                <!-- Trust Badges with Glassmorphic Pills -->
                <div class="pt-3 sm:pt-4 flex flex-wrap items-center justify-center gap-2 sm:gap-3 text-xs font-mono font-bold text-slate-600 dark:text-slate-300">
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full glass-pill-nav hover:border-emerald-500/50 hover:scale-[1.02] transition shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                        <span class="text-[10px] sm:text-xs tracking-tight">EDGE CV TATAPAN (30 FPS)</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full glass-pill-nav hover:border-blue-500/50 hover:scale-[1.02] transition shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                        <span class="text-[10px] sm:text-xs tracking-tight">100% ZERO-CLOUD (PRIVAT)</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full glass-pill-nav hover:border-sky-500/50 hover:scale-[1.02] transition shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                        <span class="text-[10px] sm:text-xs tracking-tight">SPEKTRUM AKUSTIK 32-BAND</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full glass-pill-nav hover:border-indigo-500/50 hover:scale-[1.02] transition shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                        <span class="text-[10px] sm:text-xs tracking-tight">VALIDASI RISET r = 0.87</span>
                    </div>
                </div>
            </div>

            <!-- EXECUTIVE VIDEO CONFERENCE MOCKUP HUD WITH INTERACTIVE PERSONA TABS -->
            <div class="relative mt-8 max-w-5xl mx-auto">
                <!-- Floating Left Telemetry Pill (Eye Gaze Edge CV) -->
                <div class="hidden lg:flex items-center gap-3 px-4 py-2.5 rounded-2xl glass-ultra border border-white/60 dark:border-white/10 shadow-2xl absolute -left-8 top-20 z-20 animate-float-subtle">
                    <div class="relative flex h-3 w-3 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </div>
                    <div class="text-left font-mono">
                        <span class="block text-[9px] uppercase tracking-wider font-extrabold text-slate-400 dark:text-slate-400">Eye Gaze Edge CV</span>
                        <span class="block text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="roles[activeRole].gaze">94.6% Terkunci</span>
                    </div>
                </div>

                <!-- Floating Right Telemetry Pill (Acoustic Pitch DSP with Live Equalizer) -->
                <div class="hidden lg:flex items-center gap-3 px-4 py-2.5 rounded-2xl glass-ultra border border-white/60 dark:border-white/10 shadow-2xl absolute -right-8 bottom-20 z-20 animate-float-delayed">
                    <!-- Live mini equalizer bars -->
                    <div class="flex items-end gap-1 h-5 px-1.5 py-0.5 rounded-md bg-blue-900/30 dark:bg-blue-950/60 border border-blue-400/20 shrink-0" title="Audio Waveform">
                        <span class="w-1 bg-gradient-to-t from-blue-600 to-sky-400 rounded-full animate-eq-1"></span>
                        <span class="w-1 bg-gradient-to-t from-blue-600 to-sky-400 rounded-full animate-eq-2"></span>
                        <span class="w-1 bg-gradient-to-t from-blue-600 to-sky-400 rounded-full animate-eq-3"></span>
                        <span class="w-1 bg-gradient-to-t from-blue-600 to-sky-400 rounded-full animate-eq-4"></span>
                    </div>
                    <div class="text-left font-mono">
                        <span class="block text-[9px] uppercase tracking-wider font-extrabold text-slate-400 dark:text-slate-400">Acoustic Pitch DSP</span>
                        <span class="block text-xs font-black text-blue-600 dark:text-blue-400" x-text="roles[activeRole].pitch">142 Hz Dinamis</span>
                    </div>
                </div>

                <!-- Interactive Persona Switcher Tabs -->
                <div class="flex items-center justify-center mb-4 sm:mb-5">
                    <div class="inline-flex p-1 sm:p-1.5 rounded-2xl glass-pill-nav border border-slate-200/80 dark:border-white/10 shadow-lg gap-1 max-w-full overflow-x-auto">
                        <button type="button"
                                @click="activeRole = 'dosen'"
                                class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 cursor-pointer whitespace-nowrap"
                                :class="activeRole === 'dosen' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 ring-1 ring-blue-400/40 scale-[1.02]' : 'text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60'">
                            <span class="text-sm sm:text-base">🎓</span>
                            <span>VOIC - Dosen Penguji</span>
                            <span class="hidden sm:inline-block text-[10px] px-1.5 py-0.5 rounded-md font-semibold"
                                  :class="activeRole === 'dosen' ? 'bg-blue-700/60 text-blue-100' : 'bg-slate-200/60 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400'">Akademik</span>
                        </button>
                        <button type="button"
                                @click="activeRole = 'hrd'"
                                class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 cursor-pointer whitespace-nowrap"
                                :class="activeRole === 'hrd' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30 ring-1 ring-sky-400/40 scale-[1.02]' : 'text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60'">
                            <span class="text-sm sm:text-base">💼</span>
                            <span>VOIC - HRD</span>
                            <span class="hidden sm:inline-block text-[10px] px-1.5 py-0.5 rounded-md font-semibold"
                                  :class="activeRole === 'hrd' ? 'bg-sky-700/60 text-sky-100' : 'bg-slate-200/60 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400'">Wawancara</span>
                        </button>
                        <button type="button"
                                @click="activeRole = 'investor'"
                                class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 cursor-pointer whitespace-nowrap"
                                :class="activeRole === 'investor' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 ring-1 ring-indigo-400/40 scale-[1.02]' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60'">
                            <span class="text-sm sm:text-base">🚀</span>
                            <span>VOIC - Investor</span>
                            <span class="hidden sm:inline-block text-[10px] px-1.5 py-0.5 rounded-md font-semibold"
                                  :class="activeRole === 'investor' ? 'bg-indigo-700/60 text-indigo-100' : 'bg-slate-200/60 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400'">Pitching</span>
                        </button>
                    </div>
                </div>

                <!-- Window Shell (Glassmorphism Ultra) -->
                <div class="rounded-3xl p-3 sm:p-5 glass-ultra border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden backdrop-blur-2xl">
                    <!-- Glowing Specular Top Accent Line -->
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 dark:via-sky-400/40 to-transparent"></div>

                    <!-- Window Titlebar -->
                    <div class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-2xl bg-white/75 dark:bg-[#0b1424]/85 border border-slate-200/70 dark:border-white/10 flex items-center justify-between mb-3 text-xs shadow-xs gap-2 backdrop-blur-md">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-3 h-3 rounded-full bg-rose-500/90 inline-block shadow-sm shrink-0"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/90 inline-block shadow-sm shrink-0"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/90 inline-block shadow-sm shrink-0"></span>
                            <span class="ms-2 font-mono text-[10px] sm:text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate flex items-center gap-1.5">
                                <span>VOIC Telemetry HUD</span>
                                <span class="hidden sm:inline text-slate-400">•</span>
                                <span class="hidden sm:inline text-blue-600 dark:text-blue-400" x-text="'Aktif: ' + roles[activeRole].name"></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3 font-mono text-[10px] text-slate-500 dark:text-slate-400 shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/30 text-[9px] sm:text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                LIVE 30 FPS
                            </span>
                            <span class="hidden sm:inline">Latensi: 18ms</span>
                            <span class="hidden md:inline">Zero-Cloud CV</span>
                        </div>
                    </div>

                    <!-- Main Stage: AI Panelist Examiner View vs Candidate Telemetry -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Pane 1: AI Panelist Examiner View -->
                        <div class="relative rounded-2xl overflow-hidden bg-slate-900 min-h-[300px] sm:min-h-0 sm:aspect-4/3 flex flex-col justify-between p-3.5 sm:p-4 border border-slate-800/90 shadow-inner group">
                            <!-- Ambient Blurred Backdrop -->
                            <img :src="roles[activeRole].avatar" alt="" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover filter blur-2xl opacity-20 scale-125 pointer-events-none select-none transition-all duration-500">
                            
                            <!-- Naturally Framed Examiner Portrait with Dynamic Bind -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <img :src="roles[activeRole].avatar" :alt="roles[activeRole].name" width="380" height="380" fetchpriority="high" decoding="async" class="h-full w-auto max-w-[85%] object-contain object-top opacity-95 group-hover:scale-105 transition duration-700">
                            </div>
                            
                            <!-- Dark Vignette Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-slate-950/60 pointer-events-none"></div>

                            <!-- Top Overlay: Active Speaking Status & Animated Equalizer -->
                            <div class="relative z-10 flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/70 backdrop-blur-md text-[9px] sm:text-[10px] font-bold text-white border border-white/10">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                                    <span x-text="roles[activeRole].statusText">Sedang Berbicara (Live Voice)</span>
                                </span>
                                <div class="flex items-end gap-1 h-5 px-2 py-1 rounded-lg bg-black/70 backdrop-blur-md border border-white/15 shrink-0" title="Audio Equalizer">
                                    <span class="w-1 bg-blue-400 rounded-full animate-eq-1"></span>
                                    <span class="w-1 bg-sky-400 rounded-full animate-eq-2"></span>
                                    <span class="w-1 bg-blue-300 rounded-full animate-eq-3"></span>
                                    <span class="w-1 bg-sky-400 rounded-full animate-eq-4"></span>
                                </div>
                            </div>

                            <!-- Bottom Overlay: Subtitle Speech Bubble & Examiner Credentials -->
                            <div class="relative z-10 space-y-2">
                                <div class="p-2.5 sm:p-3 rounded-xl bg-slate-950/85 backdrop-blur-md border border-white/15 text-left shadow-lg">
                                    <p class="text-[11px] sm:text-xs text-blue-100 font-medium leading-relaxed" x-text="'&ldquo;' + roles[activeRole].quote + '&rdquo;'">
                                        "Saudara mahasiswa, coba jelaskan kebaruan metodologi penelitian Anda dan bagaimana mengantisipasi risiko overfitting pada model?"
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center justify-between text-white text-xs gap-1.5">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-extrabold text-[11px] sm:text-xs text-white" x-text="roles[activeRole].name">VOIC - Dosen Penguji</span>
                                        <span class="text-[9px] sm:text-[10px] text-blue-300 font-semibold px-1.5 py-0.5 rounded bg-blue-900/60 border border-blue-400/20" x-text="roles[activeRole].tag">Dosen Penguji Utama</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono text-emerald-400" x-text="roles[activeRole].badge">Akademik & Metodologi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pane 2: Candidate Realtime Computer Vision Telemetry View -->
                        <div class="relative rounded-2xl overflow-hidden bg-slate-950 min-h-[300px] sm:min-h-0 sm:aspect-4/3 flex flex-col justify-between p-3.5 sm:p-4 border border-cyan-500/40 shadow-inner group">
                            <!-- High Tech Grid Matrix Background -->
                            <div class="absolute inset-0 bg-[radial-gradient(#1e3a8a_1px,transparent_1px)] [background-size:16px_16px] opacity-40"></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-slate-950/80 pointer-events-none"></div>

                            <!-- Computer Vision Reticle Overlay (Candidate Face HUD) -->
                            <div class="absolute inset-x-3 inset-y-8 sm:inset-x-10 sm:inset-y-10 border border-cyan-400/30 rounded-2xl pointer-events-none flex flex-col justify-between p-2 overflow-hidden">
                                <!-- Sweeping Laser Scanline Beam -->
                                <div class="absolute left-0 right-0 h-16 bg-gradient-to-b from-transparent via-cyan-400/25 to-cyan-400/0 border-b border-cyan-400/70 pointer-events-none animate-scanline-beam"></div>

                                <div class="flex justify-between items-start z-10">
                                    <div class="w-3.5 h-3.5 border-t-2 border-l-2 border-cyan-400"></div>
                                    <span class="font-mono text-[8px] sm:text-[9px] font-bold text-cyan-300 bg-cyan-950/80 px-1.5 py-0.5 rounded border border-cyan-500/30" x-text="'TATAPAN: ' + roles[activeRole].gaze">TATAPAN: 94.6% OPTIMAL</span>
                                    <div class="w-3.5 h-3.5 border-t-2 border-r-2 border-cyan-400"></div>
                                </div>
                                <div class="text-center font-mono text-[8px] sm:text-[9px] text-cyan-400/80 tracking-widest uppercase z-10">
                                    [ FACE RETICLE LOCKED • 30 FPS ]
                                </div>
                                <div class="flex justify-between items-end z-10">
                                    <div class="w-3.5 h-3.5 border-b-2 border-l-2 border-cyan-400"></div>
                                    <span class="font-mono text-[8px] sm:text-[9px] font-bold text-emerald-300 bg-emerald-950/80 px-1.5 py-0.5 rounded border border-emerald-500/30" x-text="'PITCH: ' + roles[activeRole].pitch">PITCH: 142 Hz (STABIL)</span>
                                    <div class="w-3.5 h-3.5 border-b-2 border-r-2 border-cyan-400"></div>
                                </div>
                            </div>

                            <!-- Top Overlay: Telemetry Metrics Ribbons -->
                            <div class="relative z-10 flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/70 backdrop-blur-md text-[9px] sm:text-[10px] font-bold text-white border border-white/10">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse shrink-0"></span>
                                    Kamera Kandidat (Anda)
                                </span>
                                <div class="flex items-center gap-1.5 font-mono text-[9px] sm:text-[10px] shrink-0">
                                    <span class="px-1.5 sm:px-2 py-0.5 rounded bg-emerald-900/70 text-emerald-300 border border-emerald-400/30 font-bold" x-text="'Filler: ' + roles[activeRole].filler">Filler: 0</span>
                                    <span class="px-1.5 sm:px-2 py-0.5 rounded bg-blue-900/70 text-blue-300 border border-blue-400/30 font-bold" x-text="roles[activeRole].wpm">128 WPM</span>
                                </div>
                            </div>

                            <!-- Bottom Overlay: Realtime Speech Transcription Transcript & Score -->
                            <div class="relative z-10 space-y-2">
                                <div class="p-2.5 sm:p-3 rounded-xl bg-slate-950/85 backdrop-blur-md border border-cyan-500/30 text-left shadow-lg">
                                    <div class="flex items-center gap-1 text-[8px] sm:text-[9px] font-mono text-cyan-400 mb-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping shrink-0"></span>
                                        TRANSRIPSI AKUSTIK LANGSUNG:
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-white font-medium leading-relaxed" x-text="'&ldquo;' + roles[activeRole].candidateReply + '&rdquo;'">
                                        "Baik Dosen Penguji, untuk mencegah overfitting kami menerapkan teknik Data Augmentation serta Dropout 0.3..."
                                    </p>
                                </div>
                                <div class="flex items-center justify-between text-white text-xs gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-[11px] sm:text-xs">Peserta Ujian</span>
                                        <span class="text-[9px] sm:text-[10px] text-emerald-300 font-semibold px-1.5 py-0.5 rounded bg-emerald-900/60 border border-emerald-400/20">Kontak Mata: Prima</span>
                                    </div>
                                    <span class="font-mono text-[9px] sm:text-[10px] text-slate-400">32-Band DSP</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conference Action Dock -->
                    <div class="mt-3 p-3 sm:px-4 sm:py-2.5 rounded-2xl bg-white/70 dark:bg-[#0c1829]/80 border border-slate-200/70 dark:border-white/10 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs shadow-xs backdrop-blur-md">
                        <div class="flex flex-wrap items-center justify-around sm:justify-start gap-2.5 sm:gap-4 text-[11px] sm:text-xs">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                Mikrofon (Web Audio 48kHz)
                            </span>
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-bold">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                Kamera On-Device 30 FPS
                            </span>
                        </div>
                        <div class="flex items-center">
                            <a :href="'{{ auth()->check() ? route('practice.create') : route('demo.create') }}?role=' + roles[activeRole].id"
                               class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-blue-600 via-sky-600 to-blue-700 hover:from-blue-500 hover:to-sky-500 shadow-md shadow-blue-600/30 hover:shadow-blue-600/50 transition-all transform hover:-translate-y-0.5 text-center">
                                <span x-text="roles[activeRole].cta">Coba Langsung Simulasi di Browser Anda →</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: 3 BRAND-BASED AI ROLES WITH REAL HIGH-RES PORTRAITS & VOICE DEMO -->
    <section id="karakter-ai" class="py-16 sm:py-24 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50/70 dark:bg-[#0b1329]/50"
             x-data="{
                activePlayingVoice: null,
                activeAudioObj: null,
                async playVoiceSample(roleKey, text) {
                    if (this.activePlayingVoice === roleKey) {
                        if (this.activeAudioObj) {
                            this.activeAudioObj.pause();
                            this.activeAudioObj = null;
                        }
                        if (window.speechSynthesis) window.speechSynthesis.cancel();
                        this.activePlayingVoice = null;
                        return;
                    }
                    if (this.activeAudioObj) {
                        this.activeAudioObj.pause();
                        this.activeAudioObj = null;
                    }
                    if (window.speechSynthesis) window.speechSynthesis.cancel();

                    this.activePlayingVoice = roleKey;
                    const roleType = roleKey === 'dosen' ? 'dosen_penguji' : (roleKey === 'hrd' ? 'hrd' : 'investor');

                    // 1. Coba Edge-TTS studio-grade dari microservice AI lokal
                    try {
                        const res = await fetch('http://127.0.0.1:8001/tts', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                text: text,
                                role_type: roleType,
                                language: 'id'
                            })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && data.audio_url) {
                                const audio = new Audio('http://127.0.0.1:8001' + data.audio_url);
                                this.activeAudioObj = audio;
                                audio.onended = () => { this.activePlayingVoice = null; };
                                audio.onerror = () => { this.playBrowserVoice(roleKey, text); };
                                await audio.play();
                                return;
                            }
                        }
                    } catch (e) {
                        // Fallback ke browser synthesis
                    }

                    this.playBrowserVoice(roleKey, text);
                },

                playBrowserVoice(roleKey, text) {
                    if (!window.speechSynthesis) {
                        alert('Peramban Anda belum mendukung Speech Synthesis.');
                        this.activePlayingVoice = null;
                        return;
                    }
                    window.speechSynthesis.cancel();
                    const ut = new SpeechSynthesisUtterance(text);
                    ut.lang = 'id-ID';

                    if (roleKey === 'dosen') {
                        ut.pitch = 0.80; // Bariton berat Dosen Killer
                        ut.rate = 0.88;  // Tempo terukur & berwibawa
                    } else if (roleKey === 'hrd') {
                        ut.pitch = 1.12; // Nada ramah & hangat HRD
                        ut.rate = 1.02;  // Tempo natural percakapan
                    } else {
                        ut.pitch = 0.96; // Nada tegas percaya diri
                        ut.rate = 1.16;  // Tempo cepat pitch bisnis
                    }

                    const voices = window.speechSynthesis.getVoices();
                    const idVoices = voices.filter(v => v.lang.includes('id') || v.lang.includes('ID'));

                    if (idVoices.length > 0) {
                        if (roleKey === 'hrd') {
                            const female = idVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('gadis') || n.includes('female') || n.includes('wanita') || n.includes('siti');
                            });
                            ut.voice = female || idVoices[0];
                        } else if (roleKey === 'dosen') {
                            const male = idVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('ardi') || n.includes('male') || n.includes('pria') || n.includes('david');
                            });
                            ut.voice = male || idVoices[0];
                        } else {
                            ut.voice = idVoices[0];
                        }
                    }

                    ut.onend = () => { this.activePlayingVoice = null; };
                    ut.onerror = () => { this.activePlayingVoice = null; };
                    window.speechSynthesis.speak(ut);
                }
             }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Tiga Karakter AI Berwatak Nyata</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                    Pilih Sparring Partner Sesuai Kebutuhan Ujianmu
                </h2>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400">
                    Bukan bot kaku. Setiap AI memiliki persona lisan, tingkat kekritisan, dan fokus pengujian yang menantang selayaknya evaluator asli.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Role 1: VOIC - Dosen Penguji -->
                <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 shadow-xl hover:border-blue-500/50 transition flex flex-col justify-between group overflow-hidden">
                    <!-- Compact Proportionate Header -->
                    <div class="p-5 sm:p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 bg-gradient-to-b from-blue-50/60 to-transparent dark:from-blue-950/30 dark:to-transparent">
                        <div class="flex items-center justify-between mb-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                Sidang Skripsi & Tugas Akhir
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/80 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                Level: Sulit
                            </span>
                        </div>
                        <div class="flex items-center gap-3.5">
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shadow-md border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900 group-hover:scale-105 transition-transform duration-300">
                                <img src="{{ asset('images/avatars/dosen.jpg') }}" alt="VOIC - Dosen Penguji" width="80" height="80" loading="lazy" decoding="async" class="w-full h-full object-cover object-top">
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white truncate">VOIC - Dosen Penguji</h3>
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold truncate">Ketua Dewan Penguji Sidang Utama</p>
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Karakter Akademik Kritis
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role Details -->
                    <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                        <div class="space-y-3">
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Kritis, metodologis, dan akademis. Menguji keabsahan landasan teori bab 2, batasan masalah, dan konsistensi pengambilan sampel.
                            </p>
                            
                            <!-- Sample Challenging Question -->
                            <div class="p-3 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-800/60 text-left">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300 block mb-1">Pertanyaan Khas Penguji:</span>
                                <p class="text-[11px] text-slate-700 dark:text-slate-300 italic">
                                    "Saudara mahasiswa, apa parameter pembanding algoritma Anda dan bagaimana justifikasi ilmiah terhadap baseline sebelumnya?"
                                </p>
                            </div>

                            <!-- Interactive Audio Demo Button -->
                            <button type="button"
                                    @click="playVoiceSample('dosen', 'Saudara mahasiswa, coba jelaskan kebaruan metodologi penelitian Anda dan bagaimana mengantisipasi risiko overfitting pada model?')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition border cursor-pointer"
                                    :class="activePlayingVoice === 'dosen' ? 'bg-blue-600 text-white border-blue-600 shadow-md animate-pulse' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'">
                                <template x-if="activePlayingVoice === 'dosen'">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-3 bg-white rounded-full animate-bounce"></span>
                                        <span class="w-1.5 h-4 bg-white rounded-full animate-bounce [animation-delay:0.15s]"></span>
                                        <span class="w-1.5 h-2.5 bg-white rounded-full animate-bounce [animation-delay:0.3s]"></span>
                                        <span>Hentikan Suara Dosen</span>
                                    </span>
                                </template>
                                <template x-if="activePlayingVoice !== 'dosen'">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                        </svg>
                                        <span>Dengarkan Suara Ujian (Demo)</span>
                                    </span>
                                </template>
                            </button>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Uji Metodologi</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Dasar Teori Bab 2</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">Kontak Mata Ketat</span>
                            </div>
                        </div>

                        <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-800">
                            <a href="{{ auth()->check() ? route('practice.create', ['role' => 'dosen_penguji']) : route('demo.create', ['role' => 'dosen_penguji']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md shadow-blue-600/20">
                                Latih Sidang Skripsi Sekarang →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Role 2: VOIC - HRD -->
                <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 shadow-xl hover:border-sky-500/50 transition flex flex-col justify-between group overflow-hidden">
                    <!-- Compact Proportionate Header -->
                    <div class="p-5 sm:p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 bg-gradient-to-b from-sky-50/60 to-transparent dark:from-sky-950/30 dark:to-transparent">
                        <div class="flex items-center justify-between mb-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                Interview Kerja & Culture Fit
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                Level: Sedang
                            </span>
                        </div>
                        <div class="flex items-center gap-3.5">
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shadow-md border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900 group-hover:scale-105 transition-transform duration-300">
                                <img src="{{ asset('images/avatars/hrd.jpg') }}?v=4" alt="VOIC - HRD" width="80" height="80" loading="lazy" decoding="async" class="w-full h-full object-cover object-top">
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white truncate">VOIC - HRD</h3>
                                <p class="text-xs text-sky-600 dark:text-sky-400 font-semibold truncate">Senior HR Recruiter & Talent Lead</p>
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Karakter Empati & STAR
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role Details -->
                    <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                        <div class="space-y-3">
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Ramah, hangat, namun jeli membaca kepribadian dan kedewasaan emosional. Menguji pengalaman kerja nyata kandidat dengan metode STAR.
                            </p>

                            <!-- Sample Challenging Question -->
                            <div class="p-3 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200/60 dark:border-sky-800/60 text-left">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300 block mb-1">Pertanyaan Khas HRD:</span>
                                <p class="text-[11px] text-slate-700 dark:text-slate-300 italic">
                                    "Ceritakan momen saat Anda menghadapi perbedaan pandangan tajam dengan atasan, dan bagaimana kompromi dicapai?"
                                </p>
                            </div>

                            <!-- Interactive Audio Demo Button -->
                            <button type="button"
                                    @click="playVoiceSample('hrd', 'Ceritakan momen saat Anda menghadapi perbedaan pandangan tajam dengan rekan kerja atau atasan, dan bagaimana solusi terbaik dicapai?')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition border cursor-pointer"
                                    :class="activePlayingVoice === 'hrd' ? 'bg-sky-600 text-white border-sky-600 shadow-md animate-pulse' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'">
                                <template x-if="activePlayingVoice === 'hrd'">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-3 bg-white rounded-full animate-bounce"></span>
                                        <span class="w-1.5 h-4 bg-white rounded-full animate-bounce [animation-delay:0.15s]"></span>
                                        <span class="w-1.5 h-2.5 bg-white rounded-full animate-bounce [animation-delay:0.3s]"></span>
                                        <span>Hentikan Suara HRD</span>
                                    </span>
                                </template>
                                <template x-if="activePlayingVoice !== 'hrd'">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                        </svg>
                                        <span>Dengarkan Suara HRD (Demo)</span>
                                    </span>
                                </template>
                            </button>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">Metode STAR</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">Resolusi Konflik</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">Filler Words Check</span>
                            </div>
                        </div>

                        <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-800">
                            <a href="{{ auth()->check() ? route('practice.create', ['role' => 'hrd']) : route('demo.create', ['role' => 'hrd']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-md shadow-sky-600/20">
                                Latih Wawancara HRD Sekarang →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Role 3: VOIC - Investor -->
                <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 shadow-xl hover:border-blue-500/50 transition flex flex-col justify-between group overflow-hidden">
                    <!-- Compact Proportionate Header -->
                    <div class="p-5 sm:p-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 bg-gradient-to-b from-indigo-50/60 to-transparent dark:from-indigo-950/30 dark:to-transparent">
                        <div class="flex items-center justify-between mb-3.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                Pitching Ide & Venture Capital
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                Level: Sedang
                            </span>
                        </div>
                        <div class="flex items-center gap-3.5">
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden shadow-md border-2 border-white dark:border-slate-700 shrink-0 bg-slate-900 group-hover:scale-105 transition-transform duration-300">
                                <img src="{{ asset('images/avatars/investor.jpg') }}" alt="VOIC - Investor" width="80" height="80" loading="lazy" decoding="async" class="w-full h-full object-cover object-top">
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white truncate">VOIC - Investor</h3>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold truncate">Managing Partner & Angel Investor</p>
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Karakter Bisnis Strategis
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role Details -->
                    <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                        <div class="space-y-3">
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Cepat, to-the-point, dan anti basa-basi teoritis. Menantang model bisnis riil, Customer Acquisition Cost (CAC), dan keunggulan kompetitif (moat).
                            </p>

                            <!-- Sample Challenging Question -->
                            <div class="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/60 text-left">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 block mb-1">Pertanyaan Khas Investor:</span>
                                <p class="text-[11px] text-slate-700 dark:text-slate-300 italic">
                                    "Berapa Customer Acquisition Cost Anda dibanding LTV, dan apa parit pertahanan Anda jika pemain besar masuk?"
                                </p>
                            </div>

                            <!-- Interactive Audio Demo Button -->
                            <button type="button"
                                    @click="playVoiceSample('investor', 'Berapa Customer Acquisition Cost Anda dibanding Lifetime Value, dan apa benteng pertahanan Anda jika ada kompetitor raksasa meniru ide ini?')"
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold transition border cursor-pointer"
                                    :class="activePlayingVoice === 'investor' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md animate-pulse' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'">
                                <template x-if="activePlayingVoice === 'investor'">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-3 bg-white rounded-full animate-bounce"></span>
                                        <span class="w-1.5 h-4 bg-white rounded-full animate-bounce [animation-delay:0.15s]"></span>
                                        <span class="w-1.5 h-2.5 bg-white rounded-full animate-bounce [animation-delay:0.3s]"></span>
                                        <span>Hentikan Suara Investor</span>
                                    </span>
                                </template>
                                <template x-if="activePlayingVoice !== 'investor'">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                        </svg>
                                        <span>Dengarkan Suara Investor (Demo)</span>
                                    </span>
                                </template>
                            </button>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Validasi Pasar</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Competitive Moat</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">Pitching 60s</span>
                            </div>
                        </div>

                        <div class="pt-5 mt-4 border-t border-slate-100 dark:border-slate-800">
                            <a href="{{ auth()->check() ? route('practice.create', ['role' => 'investor']) : route('demo.create', ['role' => 'investor']) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#0f243e] hover:bg-[#1b3a63] border border-blue-900/60 transition shadow-md">
                                Latih Pitching Investor Sekarang →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: MULTIMODAL FEATURES -->
    <section id="fitur" class="py-16 sm:py-24 bg-white dark:bg-[#080f1b]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span>Kecanggihan Arsitektur Multimodal</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                    Bukan Sekadar AI Chatbot Teks Biasa
                </h2>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400">
                    VOIC mengintegrasikan 4 pilar sensor komputer secara paralel tanpa membutuhkan perangkat keras mahal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pillar 1 -->
                <div class="p-6 rounded-3xl bg-slate-50 dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 space-y-3 transition hover:border-blue-500/40">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-200/80 dark:border-blue-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Vision AI Reticle & Gaze Tracking</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Reticle presisi yang mengunci wajah di layar, menghitung persentase kontak mata ke audiens, dan mendeteksi apakah pandangan Anda melenceng ke bawah.
                    </p>
                </div>

                <!-- Pillar 2 -->
                <div class="p-6 rounded-3xl bg-slate-50 dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 space-y-3 transition hover:border-sky-500/40">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0 border border-sky-200/80 dark:border-sky-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Live Acoustic Waveform & Pitch</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Visualisasi spektrum audio 32-bar yang berdenyut lincah mengikuti desibel dan variasi intonasi nada bicara via Web Audio API.
                    </p>
                </div>

                <!-- Pillar 3 -->
                <div class="p-6 rounded-3xl bg-slate-50 dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 space-y-3 transition hover:border-amber-500/40">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-200/80 dark:border-amber-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Pendeteksi Filler Words Otomatis</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Mendeteksi dan menghitung kata gumaman seperti <em>'ehm', 'anu', 'kayak'</em> agar ucapan presentasi Anda terdengar tegas dan meyakinkan.
                    </p>
                </div>

                <!-- Pillar 4 -->
                <div class="p-6 rounded-3xl bg-slate-50 dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 space-y-3 transition hover:border-emerald-500/40">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-200/80 dark:border-emerald-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">QR Code Sertifikat Terverifikasi</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Setiap sesi menghasilkan rapor resmi dengan QR Code yang dapat dipindai juri menggunakan smartphone untuk memvalidasi keaslian nilai di database.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: CHAMPIONSHIP BENCHMARK MATRIX (TOLAK UKUR KEUNGGULAN VOIC) -->
    <section id="keunggulan" class="py-16 sm:py-24 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50/70 dark:bg-[#0b1329]/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <span>Tolak Ukur Keunggulan Teknis</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                    Mengapa VOIC Menjadi Pilihan Juara?
                </h2>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400">
                    Perbandingan langsung antara metode konvensional, chatbot teks generik, dan arsitektur multimodal terpadu VOIC.
                </p>
            </div>

            <!-- Comparison Table Card -->
            <div class="rounded-3xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                <div class="sm:hidden px-4 py-2.5 bg-blue-50/70 dark:bg-blue-950/40 text-[11px] text-blue-600 dark:text-blue-300 font-medium flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0 text-blue-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        Geser tabel ke samping untuk melihat detail
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Tabel Komparasi</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-slate-50 dark:bg-[#0c1728] border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th scope="col" class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                    Dimensi Pengujian & Sensor
                                </th>
                                <th scope="col" class="py-4 px-6 font-medium text-slate-500 dark:text-slate-400">
                                    Latihan Depan Cermin / Rekam HP
                                </th>
                                <th scope="col" class="py-4 px-6 font-medium text-slate-500 dark:text-slate-400">
                                    Chatbot AI Teks Biasa
                                </th>
                                <th scope="col" class="py-4 px-6 font-black text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-950/40">
                                    VOIC Multimodal Edge AI
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Kontak Mata & Eye Gaze Telemetry
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Subjektif (perkiraan sendiri)
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak ada sensor video
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        30 FPS On-Device Canvas CV
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Spektrum Akustik & Stabilitas Intonasi
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Mendengarkan manual
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Hanya ketik tanpa suara
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        DSP Web Audio 32-Band FFT
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Deteksi Gumaman (Filler Words)
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak sadar saat berbicara
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Hanya pada teks ketikan
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Ekstraksi Otomatis per Menit
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Kerahasiaan Rekaman Kamera (Privacy)
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        Aman di memori lokal HP
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-rose-500">
                                        <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Terkirim ke server cloud
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        100% Zero-Cloud Video (Privat)
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Validitas Skor dengan Asesor Manusia
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak terstandarisasi
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Halusinasi skor angka
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Korelasi Pearson r = 0.87
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">
                                    Sertifikat Rapor Berotentikasi QR
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak ada
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400">
                                    <span class="inline-flex items-center gap-1.5 text-slate-400">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak ada
                                    </span>
                                </td>
                                <td class="py-4 px-6 bg-blue-50/30 dark:bg-blue-950/20">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/30">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Verifikasi SHA-256 Valid
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scientific Credentials & Research Rigor Ribbon -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pt-2">
                <div class="p-3.5 sm:p-5 rounded-2xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 text-center space-y-1">
                    <span class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400">r = 0.87</span>
                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Korelasi Pearson</span>
                    <span class="block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Tingkat kesesuaian nilai dengan asesor</span>
                </div>
                <div class="p-3.5 sm:p-5 rounded-2xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 text-center space-y-1">
                    <span class="text-2xl sm:text-3xl font-black text-sky-600 dark:text-sky-400">30 FPS</span>
                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Edge Computer Vision</span>
                    <span class="block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Pelacakan pupil & wajah real-time tanpa lag</span>
                </div>
                <div class="p-3.5 sm:p-5 rounded-2xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 text-center space-y-1">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">0 KB</span>
                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Video Cloud Upload</span>
                    <span class="block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Kamera diproses 100% lokal di browser</span>
                </div>
                <div class="p-3.5 sm:p-5 rounded-2xl bg-white dark:bg-[#0f1c2f] border border-slate-200 dark:border-slate-800 text-center space-y-1">
                    <span class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400">32-Band</span>
                    <span class="block text-xs font-bold text-slate-900 dark:text-white">Web Audio DSP</span>
                    <span class="block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Analisis stabilitas desibel & dinamika nada</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION: FREQUENTLY ASKED QUESTIONS (FAQ) -->
    <section id="faq" class="py-16 sm:py-24 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-[#080f1b]"
             x-data="{ activeFaq: null }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Pusat Informasi & FAQ</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                    Pertanyaan yang Sering Diajukan
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Semua yang perlu Anda ketahui tentang arsitektur AI, privasi kamera, dan mekanisme penilaian VOIC.
                </p>
            </div>

            <!-- FAQ List -->
            <div class="space-y-3.5">
                <!-- Item 1 -->
                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden bg-slate-50/70 dark:bg-[#0f1c2f]/70 transition">
                    <button type="button"
                            @click="activeFaq = (activeFaq === 1 ? null : 1)"
                            class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white cursor-pointer">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">1</span>
                            <span>Apakah rekaman video kamera dan suara saya disimpan ke server cloud?</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="activeFaq === 1 ? 'rotate-180 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="activeFaq === 1" x-transition x-cloak class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/50 dark:border-slate-800/60">
                        <strong>Tidak sama sekali.</strong> VOIC beroperasi dengan prinsip <em>Zero-Cloud Video Privacy</em>. Analisis tatapan mata (Gaze Tracking) dan spektrum audio diproses 100% secara lokal pada browser Anda via HTML5 Canvas Chromaticity dan Web Audio DSP API. Rekaman suara kandidat hanya tersimpan di IndexedDB browser lokal Anda untuk evaluasi mandiri.
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden bg-slate-50/70 dark:bg-[#0f1c2f]/70 transition">
                    <button type="button"
                            @click="activeFaq = (activeFaq === 2 ? null : 2)"
                            class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white cursor-pointer">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">2</span>
                            <span>Apakah saya memerlukan laptop berspesifikasi tinggi atau GPU mahal?</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="activeFaq === 2 ? 'rotate-180 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="activeFaq === 2" x-transition x-cloak class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/50 dark:border-slate-800/60">
                        Tidak perlu. Karena telemetri dioptimasi dengan algoritma Edge Client-Side yang sangat efisien, VOIC dapat berjalan stabil di 30 FPS pada laptop biasa dengan webcam standar maupun smartphone melalui peramban Chrome, Edge, atau Safari.
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden bg-slate-50/70 dark:bg-[#0f1c2f]/70 transition">
                    <button type="button"
                            @click="activeFaq = (activeFaq === 3 ? null : 3)"
                            class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white cursor-pointer">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">3</span>
                            <span>Bagaimana cara memverifikasi keaslian Sertifikat Digital ber-QR Code?</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="activeFaq === 3 ? 'rotate-180 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="activeFaq === 3" x-transition x-cloak class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/50 dark:border-slate-800/60">
                        Setiap sesi yang tuntas menghasilkan rapor digital resmi dengan nomor seri SHA-256 unik dan QR Code. Siapa pun dapat memindai QR Code tersebut dengan kamera HP untuk langsung membuka halaman verifikasi publik yang membuktikan keaslian skor dan telemetri langsung dari pangkalan data VOIC.
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden bg-slate-50/70 dark:bg-[#0f1c2f]/70 transition">
                    <button type="button"
                            @click="activeFaq = (activeFaq === 4 ? null : 4)"
                            class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white cursor-pointer">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">4</span>
                            <span>Apakah platform ini mendukung simulasi dalam Bahasa Inggris?</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="activeFaq === 4 ? 'rotate-180 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="activeFaq === 4" x-transition x-cloak class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/50 dark:border-slate-800/60">
                        Ya. VOIC memiliki mode bilingual penuh (Bahasa Indonesia & English). Sistem pengenalan suara dan deteksi filler words akan otomatis menyesuaikan kamus akustik bahasa yang dipilih saat simulasi dimulai.
                    </div>
                </div>

                <!-- Item 5 -->
                <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden bg-slate-50/70 dark:bg-[#0f1c2f]/70 transition">
                    <button type="button"
                            @click="activeFaq = (activeFaq === 5 ? null : 5)"
                            class="w-full p-5 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white cursor-pointer">
                        <span class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">5</span>
                            <span>Apa perbedaan VOIC dibanding latihan berbicara di depan cermin?</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="activeFaq === 5 ? 'rotate-180 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="activeFaq === 5" x-transition x-cloak class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/50 dark:border-slate-800/60">
                        Latihan di depan cermin bersifat subjektif dan Anda tidak bisa menilai diri sendiri saat sedang berkonsentrasi bicara. VOIC menghitung metrik objektif (persentase kontak mata, WPM, kata jeda gumaman, dan variasi intonasi nada) serta menghadirkan AI yang bertanya balik secara kritis.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: INVITATION / BANNER CTA -->
    <section class="py-16 sm:py-20 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-[#080f1b]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-[#080f1b] via-[#0f243e] to-[#1e3a5f] border border-blue-900/40 text-center space-y-6 shadow-2xl relative overflow-hidden text-white">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-blue-200 text-xs font-bold uppercase tracking-wider border border-white/15">
                    Siap Berikan Kesan Terbaik?
                </div>

                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    Coba Langsung Simulasi VOIC AI Hari Ini
                </h2>

                <p class="text-sm sm:text-base text-blue-100 max-w-xl mx-auto leading-relaxed">
                    Tunjukkan kepada dosen penguji dan rekruter bahwa Anda adalah pembicara yang percaya diri, artikulatif, dan menguasai panggung.
                </p>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ auth()->check() ? route('practice.create') : route('demo.create') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-bold text-slate-900 bg-white hover:bg-slate-100 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        Mulai Latihan Sekarang
                    </a>
                    <a href="{{ route('login') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-bold text-white bg-blue-600/40 hover:bg-blue-600/60 border border-blue-400/30 rounded-xl transition">
                        Masuk Akun Pengguna
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-10 bg-white dark:bg-[#080f1b] text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-application-logo variant="mark" class="w-6 h-6" />
                <span>&copy; {{ date('Y') }} Tim VOIC (Voice & Optics Intelligent Coach). Seluruh hak cipta dilindungi.</span>
            </div>
            <div>
                Ditenagai oleh Laravel 12, Python FastAPI, dan Client-Side Edge AI Engine.
            </div>
        </div>
    </footer>

</body>
</html>
