<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VOIC - AI Multimodal Public Speaking & Interview Coach</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 antialiased font-sans selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
                        V
                    </div>
                    <div>
                        <span class="font-black text-lg tracking-tight text-white block">VOIC AI</span>
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-widest block -mt-1">Multimodal Coach</span>
                    </div>
                </a>

                <!-- Nav Menu -->
                <div class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-300">
                    <a href="#fitur" class="hover:text-indigo-400 transition">Teknologi Multimodal</a>
                    <a href="#karakter-ai" class="hover:text-indigo-400 transition">3 Karakter Penguji</a>
                    <a href="#standar-industri" class="hover:text-indigo-400 transition">Tolak Ukur Industri</a>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition transform hover:-translate-y-0.5">
                            Buka Dashboard
                            <svg class="w-4 h-4 ms-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    @else
                        <!-- 1-Click Guest Exhibition Login Button -->
                        <a href="{{ route('demo.login') }}"
                           class="inline-flex items-center px-3.5 py-2 text-xs font-bold rounded-xl text-emerald-400 bg-emerald-950/60 border border-emerald-500/40 hover:bg-emerald-900/60 transition shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse me-1.5"></span>
                            Mode Tamu Pameran
                        </a>

                        <a href="{{ route('login') }}"
                           class="px-3.5 py-2 text-xs sm:text-sm font-semibold text-slate-300 hover:text-white transition">
                            Masuk
                        </a>

                        <a href="{{ route('register') }}"
                           class="hidden sm:inline-flex items-center px-4 py-2 text-xs sm:text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-md shadow-indigo-500/25 transition transform hover:-translate-y-0.5">
                            Daftar Akun
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-20 sm:pt-24 sm:pb-32">
        <!-- Radial Glow Background -->
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-900/30 via-slate-950 to-slate-950 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <!-- Event Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-950/70 border border-indigo-500/40 text-xs sm:text-sm font-semibold text-indigo-300 shadow-inner shadow-indigo-500/20">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                Inovasi Pameran Innofest 2026 • AI Multimodal Public Speaking & Interview Coach
            </div>

            <!-- Title -->
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white max-w-5xl mx-auto leading-tight sm:leading-none">
                Latih Kepercayaan Diri Bicara Bersama <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">VOIC AI</span>
            </h1>

            <!-- Subtitle -->
            <p class="text-base sm:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                Sparring partner AI interaktif pertama yang menggabungkan <strong>telemetri tatapan kamera (Gaze Tracking)</strong>, <strong>spektrum akustik suara</strong>, dan <strong>roleplay kontekstual</strong> untuk persiapan sidang skripsi, wawancara kerja, dan pitching startup.
            </p>

            <!-- CTA Action Buttons -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <a href="{{ route('practice.create') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-extrabold rounded-2xl text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 shadow-xl shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                        Mulai Ruang Simulasi Interaktif
                        <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('demo.login') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-extrabold rounded-2xl text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 shadow-xl shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 me-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Coba Demo 1-Klik (Pengunjung & Juri)
                    </a>
                    <a href="{{ route('register') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold rounded-2xl text-slate-200 bg-slate-900 border border-slate-700 hover:bg-slate-800 transition">
                        Daftar Akun Baru
                    </a>
                @endauth
            </div>

            <!-- Tech Trust Indicators -->
            <div class="pt-8 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-xs font-mono text-slate-400">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    EDGE AI CV (30 FPS)
                </span>
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                    ZERO CLOUD VIDEO (100% PRIVATE)
                </span>
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                    LOCAL OLLAMA / QWEN2.5 READY
                </span>
            </div>
        </div>
    </section>

    <!-- SECTION 2: 3 BRAND-BASED AI ROLES -->
    <section id="karakter-ai" class="py-16 sm:py-24 border-t border-slate-850 bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-bold uppercase tracking-wider">
                    <span>👥</span>
                    <span>Tiga Karakter AI Berwatak Manusia</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Pilih Sparring Partner Sesuai Kebutuhan Ujianmu
                </h2>
                <p class="text-sm sm:text-base text-slate-400">
                    Bukan bot kaku. Setiap AI memiliki persona lisan, tingkat kekritisan, dan fokus pengujian yang menantang selayaknya evaluator asli.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Role 1: VOIC-Dosen Penguji -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl hover:border-indigo-500/50 transition flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-3xl shadow-lg">
                            🎓
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Sidang Skripsi & Tugas Akhir</span>
                            <h3 class="text-xl font-bold text-white mt-1 group-hover:text-indigo-300 transition">VOIC-Dosen Penguji</h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Kritis, akademis, dan berwibawa. Menguliti keabsahan metodologi, batasan masalah, serta menegur santun jika mahasiswa tidak menatap kamera.
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-2">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-800/60">Uji Metodologi</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-800/60">Dasar Teori Bab 2</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-800/60">Level: Sulit</span>
                        </div>
                    </div>

                    <div class="pt-6 mt-6 border-t border-slate-800">
                        <a href="{{ auth()->check() ? route('practice.create', ['role' => 'dosen_penguji']) : route('demo.login') }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-md shadow-indigo-600/20">
                            Latih Sidang Skripsi Sekarang
                        </a>
                    </div>
                </div>

                <!-- Role 2: VOIC-HRD -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl hover:border-amber-500/50 transition flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-3xl shadow-lg">
                            💼
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Interview Kerja & Culture Fit</span>
                            <h3 class="text-xl font-bold text-white mt-1 group-hover:text-amber-300 transition">VOIC-HRD</h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Ramah, hangat, namun jeli membaca kepribadian dan kedewasaan emosional. Menguji pengalaman kerja nyata kandidat dengan metode STAR.
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-2">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-amber-950/80 text-amber-300 border border-amber-800/60">Metode STAR</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-amber-950/80 text-amber-300 border border-amber-800/60">Resolusi Konflik</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-amber-950/80 text-amber-300 border border-amber-800/60">Level: Sedang</span>
                        </div>
                    </div>

                    <div class="pt-6 mt-6 border-t border-slate-800">
                        <a href="{{ auth()->check() ? route('practice.create', ['role' => 'hrd']) : route('demo.login') }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 transition shadow-md shadow-amber-600/20">
                            Latih Wawancara HRD Sekarang
                        </a>
                    </div>
                </div>

                <!-- Role 3: VOIC-Investor -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl hover:border-purple-500/50 transition flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-3xl shadow-lg">
                            🚀
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-purple-400">Pitching Ide & Venture Capital</span>
                            <h3 class="text-xl font-bold text-white mt-1 group-hover:text-purple-300 transition">VOIC-Investor</h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Cepat, to-the-point, dan anti basa-basi teoritis. Menantang model bisnis riil, Customer Acquisition Cost (CAC), dan keunggulan kompetitif (moat).
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-2">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-950/80 text-purple-300 border border-purple-800/60">Validasi Pasar</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-950/80 text-purple-300 border border-purple-800/60">Competitive Moat</span>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-purple-950/80 text-purple-300 border border-purple-800/60">Level: Sedang</span>
                        </div>
                    </div>

                    <div class="pt-6 mt-6 border-t border-slate-800">
                        <a href="{{ auth()->check() ? route('practice.create', ['role' => 'investor']) : route('demo.login') }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-500 transition shadow-md shadow-purple-600/20">
                            Latih Pitching Investor Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: MULTIMODAL FEATURES & ADVANCED TELEMETRY -->
    <section id="fitur" class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase tracking-wider">
                    <span>🔬</span>
                    <span>Kecanggihan Arsitektur Multimodal</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Bukan Sekadar AI Chatbot Teks Biasa
                </h2>
                <p class="text-sm sm:text-base text-slate-400">
                    VOIC mengintegrasikan 4 pilar penginderaan komputer secara paralel tanpa membutuhkan perangkat keras mahal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pillar 1 -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl">
                        🎯
                    </div>
                    <h3 class="text-lg font-bold text-white">Vision AI Reticle & Gaze Tracking</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Reticle futuristik yang mengunci wajah di layar, menghitung persentase kontak mata ke audiens, dan mendeteksi apakah pandangan Anda melenceng ke bawah.
                    </p>
                </div>

                <!-- Pillar 2 -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-2xl">
                        🎙️
                    </div>
                    <h3 class="text-lg font-bold text-white">Live Acoustic Waveform & dB</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Visualisasi spektrum audio 32-bar yang berdenyut lincah mengikuti desibel dan dinamika nada suara Anda secara real-time via Web Audio API.
                    </p>
                </div>

                <!-- Pillar 3 -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl">
                        🚫
                    </div>
                    <h3 class="text-lg font-bold text-white">Pendeteksi Filler Words Otomatis</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Mendeteksi dan menghitung kata gumaman seperti <em>'ehm', 'anu', 'kayak'</em> agar ucapan presentasi Anda terdengar tegas dan meyakinkan.
                    </p>
                </div>

                <!-- Pillar 4 -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl">
                        🛡️
                    </div>
                    <h3 class="text-lg font-bold text-white">QR Code Sertifikat Terverifikasi</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Setiap sesi menghasilkan rapor resmi dengan QR Code yang dapat dipindai juri menggunakan smartphone untuk memvalidasi keaslian nilai di database.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: INVITATION / BANNER CTA -->
    <section class="py-16 sm:py-20 border-t border-slate-850">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-indigo-950/80 via-slate-900 to-purple-950/80 border border-indigo-500/40 text-center space-y-6 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                    Siap Berikan Kesan Terbaik?
                </div>

                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    Coba Langsung Simulasi VOIC AI Hari Ini
                </h2>

                <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Tunjukkan kepada dosen penguji dan rekruter bahwa Anda adalah pembicara yang percaya diri, artikulatif, dan menguasai panggung.
                </p>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ auth()->check() ? route('practice.create') : route('demo.login') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-600/30 transition transform hover:-translate-y-0.5">
                        Mulai Latihan Sekarang
                    </a>
                    <a href="{{ route('login') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition">
                        Masuk Akun Pengguna
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-850 py-10 bg-slate-950 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-xs">
                    V
                </div>
                <span>&copy; {{ date('Y') }} Tim VOIC (Voice & Optics Intelligent Coach) • Innofest 2026.</span>
            </div>
            <div>
                Ditenagai oleh Laravel 12, Python FastAPI, dan Client-Side Edge AI Engine.
            </div>
        </div>
    </footer>

</body>
</html>
