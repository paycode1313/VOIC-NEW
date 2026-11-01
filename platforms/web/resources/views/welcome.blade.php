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
            if (savedTheme === 'dark') {
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
    <nav class="sticky top-0 z-50 backdrop-blur-2xl bg-white/70 dark:bg-[#070e1b]/70 border-b border-slate-200/50 dark:border-white/10 transition-colors duration-200"
         x-data="{ mobileNavOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <a href="/" class="flex items-center group">
                    <x-application-logo variant="horizontal" class="h-7 sm:h-10 w-auto max-w-[130px] sm:max-w-none group-hover:scale-[1.02] transition-transform" />
                </a>

                <!-- Nav Menu Desktop -->
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600 dark:text-slate-300">
                    <a href="#fitur" class="hover:text-slate-900 dark:hover:text-white transition">Teknologi</a>
                    <a href="#karakter-ai" class="hover:text-slate-900 dark:hover:text-white transition">3 Penguji AI</a>
                    <a href="#keunggulan" class="hover:text-slate-900 dark:hover:text-white transition">Standar Industri</a>
                    <a href="#faq" class="hover:text-slate-900 dark:hover:text-white transition">FAQ</a>
                </div>

                <!-- Right Actions & Theme Switcher -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Dark / Light Theme Toggle Button -->
                    <button type="button"
                            onclick="
                                const isDark = document.documentElement.classList.toggle('dark');
                                localStorage.setItem('voic_theme', isDark ? 'dark' : 'light');
                            "
                            class="p-2 rounded-full text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                            title="Ganti Mode Gelap / Terang">
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center px-5 py-2 text-xs sm:text-sm font-semibold rounded-full text-white bg-slate-900 hover:bg-black dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 shadow-sm transition transform hover:-translate-y-0.5">
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="hidden sm:inline-block px-4 py-2 text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition">
                            Masuk
                        </a>

                        <a href="{{ route('demo.create') }}"
                           class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold rounded-full text-white bg-slate-900 hover:bg-black dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 shadow-sm transition transform hover:-translate-y-0.5">
                            Coba Simulasi
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
             class="md:hidden px-4 pt-2 pb-6 bg-white/95 dark:bg-[#070e1b]/95 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800 space-y-3 shadow-xl"
             x-cloak>
            <div class="flex flex-col space-y-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                <a @click="mobileNavOpen = false" href="#fitur" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Teknologi</a>
                <a @click="mobileNavOpen = false" href="#karakter-ai" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">3 Penguji AI</a>
                <a @click="mobileNavOpen = false" href="#keunggulan" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Standar Industri</a>
                <a @click="mobileNavOpen = false" href="#faq" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">Pertanyaan Umum (FAQ)</a>
            </div>

            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-2">
                @guest
                    <a href="{{ route('demo.create') }}" class="w-full text-center px-4 py-2.5 rounded-full font-semibold text-xs text-white bg-slate-900 shadow-md">
                        Coba Demo Tanpa Login
                    </a>
                    <a href="{{ route('login') }}" class="w-full text-center px-4 py-2.5 rounded-full font-semibold text-xs text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800">
                        Masuk Akun
                    </a>
                @endguest
            </div>
        </div>
    </nav>

    <!-- HERO SECTION: ETHEREAL AQUATIC GLASSMORPHISM THEME -->
    <section class="relative overflow-hidden pt-6 pb-14 sm:pt-10 sm:pb-20 bg-slate-50 dark:bg-[#070e1b] transition-colors duration-300"
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
                        gaze: '94.6%',
                        score: '96.4',
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
                        gaze: '96.2%',
                        score: '94.8',
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
                        gaze: '93.8%',
                        score: '97.2',
                        cta: 'Mulai Simulasi Pitching Investor →'
                    }
                }
             }">

        <!-- Light Mode Ocean Background -->
        <div class="absolute inset-0 bg-cover bg-bottom dark:hidden pointer-events-none"
             style="background-image: url('{{ asset('images/hero-water-bg.jpg') }}');">
            <!-- Smooth clean white gradient to guarantee high text contrast -->
            <div class="absolute inset-0 bg-gradient-to-b from-white/95 via-white/50 to-transparent"></div>
        </div>

        <!-- Dark Mode Ocean Background -->
        <div class="absolute inset-0 bg-cover bg-bottom hidden dark:block pointer-events-none"
             style="background-image: url('{{ asset('images/hero-water-bg-dark.jpg') }}');">
            <!-- Smooth deep midnight gradient to guarantee high text contrast -->
            <div class="absolute inset-0 bg-gradient-to-b from-[#070e1b]/95 via-[#070e1b]/80 to-[#070e1b]/35"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
            
            <!-- HEADER TEXT: BADGE, HEADLINE, & SUBTITLE -->
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <!-- Verified Pill Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200/90 dark:border-white/10 shadow-xs text-[11px] sm:text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <svg class="w-3.5 h-3.5 text-blue-600 fill-current shrink-0" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>AI Multimodal Defense & Public Speaking Platform</span>
                </div>

                <!-- Main Hero Headline (Slim, Sleek, & Proportional) -->
                <h1 class="text-xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-950 dark:text-white leading-snug sm:leading-tight max-w-2xl mx-auto">
                    Kecerdasan Multimodal,<br class="hidden sm:inline">
                    Dirancang untuk Public Speaking Modern
                </h1>

                <!-- Subheadline -->
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-xl mx-auto leading-relaxed font-normal">
                    Dapatkan analisis objektif performa presentasi Anda secara instan. Pantau kontak mata, ukur stabilitas intonasi suara, deteksi kata jeda, dan roleplay interaktif bersama AI penguji.
                </p>

                <!-- Action CTA Pill Buttons -->
                <div class="pt-1 flex flex-wrap items-center justify-center gap-2.5">
                    <a href="{{ auth()->check() ? route('practice.create') : route('demo.create') }}"
                       class="rounded-full bg-slate-900 hover:bg-black text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 px-5 py-2 sm:px-6 sm:py-2.5 text-xs sm:text-sm font-semibold shadow-md shadow-slate-900/15 dark:shadow-white/10 transition-all hover:scale-105 inline-flex items-center gap-2">
                        <span>Mulai Latihan</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a href="#fitur"
                       class="rounded-full bg-white/90 hover:bg-white text-slate-800 border border-slate-300/80 dark:bg-slate-800/90 dark:hover:bg-slate-800 dark:text-white dark:border-white/15 px-5 py-2 sm:px-6 sm:py-2.5 text-xs sm:text-sm font-semibold shadow-xs transition-all hover:scale-105">
                        Pelajari Fitur
                    </a>
                </div>
            </div>

            <!-- THE HERO CENTERPIECE: CENTRAL GLASS DASHBOARD WITH 3D DEPTH CARDS -->
            <div class="relative mt-6 sm:mt-10 max-w-4xl mx-auto">

                <!-- 3D DEPTH CARD 1: Top-Left Floating Circle (Camera Telemetry Badge) -->
                <div class="hidden sm:flex absolute -top-5 -left-3 sm:-left-5 z-30 animate-float-subtle">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white dark:bg-slate-800 backdrop-blur-xl shadow-[0_12px_24px_rgba(0,0,0,0.1)] border border-slate-200/80 dark:border-slate-700 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                </div>

                <!-- 3D DEPTH CARD 2: Top-Right Stacked Blue Folder ("Total Balance" style) -->
                <div class="hidden sm:block absolute -top-8 -right-3 sm:-right-6 z-30 rotate-3 hover:rotate-0 transition-transform duration-300">
                    <!-- Layered folder flaps behind to create 3D stacked depth -->
                    <div class="absolute -top-2.5 left-4 right-4 h-7 rounded-2xl bg-sky-200/60 dark:bg-sky-400/20 backdrop-blur-xs"></div>
                    <div class="absolute -top-1.5 left-2 right-2 h-7 rounded-2xl bg-sky-300/80 dark:bg-sky-500/30 backdrop-blur-xs"></div>
                    <!-- Main folder card -->
                    <div class="relative rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 p-3.5 sm:p-4 text-white shadow-[0_18px_36px_rgba(37,99,235,0.3)] w-44 sm:w-50">
                        <div class="text-[9px] sm:text-[10px] font-semibold text-sky-100 opacity-90 tracking-wider uppercase">Skor Kelayakan Sidang</div>
                        <div class="mt-0.5 text-xl sm:text-2xl font-bold tracking-tight">
                            <span x-text="roles[activeRole].score">96.4</span> <span class="text-xs font-normal text-sky-100">/ 100</span>
                        </div>
                        <div class="mt-1.5 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-md text-[9px] sm:text-[10px] font-bold text-white">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                            <span>Status: Sangat Siap</span>
                        </div>
                    </div>
                </div>

                <!-- 3D DEPTH CARD 3: Bottom-Left Tilted Bar Chart ("Average Spend" style) -->
                <div class="hidden sm:block absolute -bottom-8 -left-3 sm:-left-6 z-30 -rotate-6 hover:rotate-0 transition-transform duration-300">
                    <div class="rounded-2xl bg-white dark:bg-slate-900 backdrop-blur-xl p-3.5 sm:p-4 shadow-[0_20px_40px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_40px_rgba(0,0,0,0.5)] border border-slate-200/80 dark:border-slate-800 w-42 sm:w-48">
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Distribusi Intonasi</div>
                        <div class="mt-2.5 flex items-end justify-between gap-1.5 h-12 px-1">
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-5"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">120</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-8"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">135</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-11"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">148</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-6"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">142</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-4"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">130</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <div class="w-2 sm:w-2.5 rounded-full bg-gradient-to-t from-blue-600 to-sky-400 h-10"></div>
                                <span class="text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">155</span>
                            </div>
                        </div>
                        <div class="mt-1 text-center text-[8px] sm:text-[9px] text-slate-500 dark:text-slate-400 font-mono">Hz Variasi Nada</div>
                    </div>
                </div>

                <!-- 3D DEPTH CARD 4: Bottom-Right Pill Badge ("USD 0.87" style) -->
                <div class="hidden sm:flex absolute -bottom-5 right-4 sm:right-10 z-30">
                    <div class="rounded-full bg-white dark:bg-slate-900 backdrop-blur-xl py-1.5 px-3.5 shadow-md border border-slate-200/80 dark:border-slate-800 flex items-center gap-2">
                        <div class="w-5 h-5 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 flex items-center justify-center bg-red-600 text-white font-bold text-[8px] shrink-0">
                            <span class="text-xs leading-none">🇮🇩</span>
                        </div>
                        <div class="text-[11px] sm:text-xs font-bold text-slate-800 dark:text-white">
                            Akurasi AI <span class="text-blue-600 dark:text-blue-400 font-mono">98.7%</span>
                        </div>
                    </div>
                </div>

                <!-- THE CENTRAL FROSTED GLASS CONTAINER -->
                <div class="rounded-3xl sm:rounded-[2.25rem] bg-white/80 dark:bg-slate-900/85 backdrop-blur-2xl border border-white dark:border-white/10 shadow-[0_20px_50px_-15px_rgba(14,165,233,0.18)] dark:shadow-[0_20px_50px_-15px_rgba(0,0,0,0.6)] p-5 sm:p-8 relative overflow-hidden ring-1 ring-slate-200/80 dark:ring-white/10">

                    <!-- Specular Highlight Top Edge -->
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white dark:via-white/20 to-transparent"></div>

                    <!-- Sleek Persona Selector Tabs on Top of Dashboard -->
                    <div class="flex items-center justify-center mb-6 sm:mb-7">
                        <div class="inline-flex p-1 rounded-full bg-slate-100/90 dark:bg-slate-800/90 backdrop-blur-md border border-slate-200/80 dark:border-white/10 shadow-xs gap-1">
                            <button type="button" @click="activeRole = 'dosen'"
                                    class="px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5"
                                    :class="activeRole === 'dosen' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'">
                                <span>🎓</span>
                                <span>Dosen Penguji</span>
                            </button>
                            <button type="button" @click="activeRole = 'hrd'"
                                    class="px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5"
                                    :class="activeRole === 'hrd' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'">
                                <span>💼</span>
                                <span>HRD Recruiter</span>
                            </button>
                            <button type="button" @click="activeRole = 'investor'"
                                    class="px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer flex items-center gap-1.5"
                                    :class="activeRole === 'investor' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'">
                                <span>🚀</span>
                                <span>Investor VC</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3-COLUMN METRICS GRID (Exact match to reference photo!) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-200/80 dark:divide-white/10 gap-5 md:gap-0">

                        <!-- Column 1: Total Cash style -> Kontak Mata (Eye Gaze) -->
                        <div class="relative md:pr-6 pb-4 md:pb-0 flex flex-col justify-between overflow-hidden">
                            <div>
                                <div class="text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak Mata (Eye Contact)</div>
                                <div class="mt-1.5 text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-900 dark:text-white" x-text="roles[activeRole].gaze">
                                    94.6%
                                </div>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 text-[10px] sm:text-xs font-bold">▲ 4.5%</span>
                                    <span class="font-medium text-slate-600 dark:text-slate-400 text-[11px] sm:text-xs">Fokus Optimal</span>
                                </div>
                            </div>
                            <!-- Subtle Wave Background SVG -->
                            <div class="mt-3 h-10 sm:h-12 w-full text-blue-500/20 dark:text-blue-400/15">
                                <svg viewBox="0 0 200 60" class="w-full h-full" fill="currentColor">
                                    <path d="M0,45 C40,20 80,55 120,30 C160,10 180,40 200,25 L200,60 L0,60 Z" opacity="0.4"/>
                                    <path d="M0,50 C50,30 90,45 130,20 C170,35 190,15 200,35 L200,60 L0,60 Z" opacity="0.6"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Column 2: Net Cash Flow style -> Kata Jeda (Filler Words) -->
                        <div class="relative md:px-6 pt-4 md:pt-0 pb-4 md:pb-0 flex flex-col justify-between overflow-hidden">
                            <div>
                                <div class="text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kata Jeda (Filler Words)</div>
                                <div class="mt-1.5 text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    <span x-text="roles[activeRole].filler">0</span> <span class="text-base font-normal text-slate-400 dark:text-slate-500">kata</span>
                                </div>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 text-[10px] sm:text-xs font-bold">▼ -8.9%</span>
                                    <span class="font-medium text-slate-600 dark:text-slate-400 text-[11px] sm:text-xs">Minim Gumaman</span>
                                </div>
                            </div>
                            <!-- Subtle Wave Background SVG -->
                            <div class="mt-3 h-10 sm:h-12 w-full text-sky-500/20 dark:text-sky-400/15">
                                <svg viewBox="0 0 200 60" class="w-full h-full" fill="currentColor">
                                    <path d="M0,35 C30,50 70,25 110,40 C150,20 180,45 200,30 L200,60 L0,60 Z" opacity="0.4"/>
                                    <path d="M0,40 C40,25 80,45 120,30 C160,40 180,20 200,35 L200,60 L0,60 Z" opacity="0.6"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Column 3: Month Revenue style -> Kecepatan Bicara (Pace) -->
                        <div class="relative md:pl-6 pt-4 md:pt-0 flex flex-col justify-between overflow-hidden">
                            <div>
                                <div class="text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kecepatan Bicara (Pace)</div>
                                <div class="mt-1.5 text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-900 dark:text-white" x-text="roles[activeRole].wpm">
                                    128 WPM
                                </div>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 text-[10px] sm:text-xs font-bold">▲ 3.1%</span>
                                    <span class="font-medium text-slate-600 dark:text-slate-400 text-[11px] sm:text-xs">Ritme Terjaga</span>
                                </div>
                            </div>
                            <!-- Line Chart Curve with Dot SVG -->
                            <div class="mt-3 h-10 sm:h-12 w-full">
                                <svg viewBox="0 0 200 60" class="w-full h-full overflow-visible">
                                    <path d="M0,45 C40,20 70,50 110,30 C150,15 180,40 200,20" fill="none" stroke="#0ea5e9" stroke-width="2.5" stroke-linecap="round"/>
                                    <circle cx="200" cy="20" r="4" fill="#0284c7" stroke="#ffffff" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- EXAMINER SPEECH & VOICE PLAYBACK BAR -->
                    <div class="mt-8 pt-6 border-t border-slate-200/80 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <img :src="roles[activeRole].avatar" :alt="roles[activeRole].name" class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-200 dark:ring-white/20 shadow-xs shrink-0">
                            <div class="text-left text-xs min-w-0">
                                <div class="font-bold text-slate-900 dark:text-white truncate" x-text="roles[activeRole].name">VOIC - Dosen Penguji</div>
                                <div class="text-slate-500 dark:text-slate-400 text-[11px] truncate" x-text="roles[activeRole].statusText">Sedang Menguji Metodologi (Live Voice)</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <a :href="'{{ auth()->check() ? route('practice.create') : route('demo.create') }}?role=' + roles[activeRole].id"
                               class="rounded-full bg-slate-900 hover:bg-black text-white dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 px-5 py-2 text-xs font-semibold shadow-xs hover:shadow-md transition-all flex items-center gap-1.5 w-full sm:w-auto justify-center">
                                <span x-text="roles[activeRole].cta">Mulai Simulasi Sidang Skripsi →</span>
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
