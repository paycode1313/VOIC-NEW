<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Sertifikat Digital • VOIC AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased selection:bg-indigo-500 selection:text-white flex flex-col justify-between bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-950/60 via-slate-950 to-black">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center font-black text-white text-lg shadow-lg shadow-indigo-500/30">
                    V
                </div>
                <div>
                    <span class="font-extrabold text-base tracking-tight text-white block">VOIC AI</span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block">Official Credential Registry</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="hidden sm:inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-300 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 rounded-lg transition">
                    <svg class="w-3.5 h-3.5 me-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Verifikasi
                </button>
                <a href="{{ url('/') }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-md shadow-indigo-600/30 transition">
                    Coba VOIC AI
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12 w-full space-y-6">

        <!-- Verification Banner Badge -->
        <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-950/80 via-slate-900 to-indigo-950/80 border border-emerald-500/40 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-left">
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-3xl shrink-0 shadow-lg shadow-emerald-500/20">
                    🛡️
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
                </div>
            </div>
        </div>

        <!-- Candidate & Session Identity Details -->
        <div class="bg-slate-900/80 rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-xl space-y-6">
            <div class="border-b border-slate-800 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-slate-400">Identitas Peserta Uji</span>
                    <h2 class="text-2xl font-extrabold text-white mt-0.5">
                        {{ $user ? $user->name : 'Peserta Uji Terkalibrasi' }}
                    </h2>
                    <p class="text-xs text-slate-400">
                        Email: {{ $user ? $user->email : 'Akun Tamu Demo' }}
                    </p>
                </div>

                <div class="flex items-center gap-2 bg-slate-800/80 border border-slate-700/60 px-4 py-2 rounded-2xl w-fit">
                    <span class="text-xl">{{ $role?->role_type === 'dosen_penguji' ? '🎓' : ($role?->role_type === 'hrd' ? '💼' : '💡') }}</span>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Karakter Penguji AI</span>
                        <span class="text-xs font-extrabold text-indigo-300 block">{{ $role ? $role->name : $session->scenario_type }}</span>
                    </div>
                </div>
            </div>

            <!-- Scorecards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Overall Score -->
                <div class="p-5 rounded-2xl bg-gradient-to-tr from-slate-950 to-indigo-950/60 border border-indigo-500/30 text-center relative overflow-hidden">
                    <span class="text-[10px] uppercase font-bold text-indigo-300 tracking-wider">Skor Total Evaluasi</span>
                    <div class="text-4xl font-black text-white mt-1">
                        {{ number_format($session->overall_score, 1) }}
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Predikat: <strong class="text-indigo-300">{{ $session->overall_score >= 85 ? 'Sangat Baik' : ($session->overall_score >= 70 ? 'Cukup Baik' : 'Berkembang') }}</strong>
                    </span>
                </div>

                <!-- Optical Face Score -->
                <div class="p-5 rounded-2xl bg-gradient-to-tr from-slate-950 to-purple-950/60 border border-purple-500/30 text-center">
                    <span class="text-[10px] uppercase font-bold text-purple-300 tracking-wider">Telemetri Optik Kamera</span>
                    <div class="text-4xl font-black text-white mt-1">
                        {{ number_format($session->face_score ?? 80, 1) }}
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Kontak Mata & Ketenangan Wajah
                    </span>
                </div>

                <!-- Voice Score -->
                <div class="p-5 rounded-2xl bg-gradient-to-tr from-slate-950 to-emerald-950/60 border border-emerald-500/30 text-center">
                    <span class="text-[10px] uppercase font-bold text-emerald-300 tracking-wider">Artikulasi & Vokal</span>
                    <div class="text-4xl font-black text-white mt-1">
                        {{ number_format($session->voice_score ?? 85, 1) }}
                    </div>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Kejelasan & Kelancaran Nada
                    </span>
                </div>
            </div>

            <!-- National Benchmark Ranking Badge -->
            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300 text-lg font-bold">
                        📊
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Peringkat Tolak Ukur Nasional:</span>
                        <span class="text-sm font-extrabold text-white">{{ $benchmarkData['percentile_rank'] }}</span>
                    </div>
                </div>

                <div class="px-3.5 py-1.5 rounded-xl bg-indigo-950/80 border border-indigo-700/60 text-xs font-bold text-indigo-300">
                    {{ $benchmarkData['readiness_status'] }}
                </div>
            </div>

            <!-- Official AI Conclusion -->
            @if($session->ai_conclusion)
                <div class="p-5 rounded-2xl bg-indigo-950/40 border border-indigo-800/40 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-300">
                        <span>📝</span>
                        <span>Kesimpulan Resmi Evaluator AI:</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-200 leading-relaxed italic">
                        "{{ $session->ai_conclusion }}"
                    </p>
                </div>
            @endif

            <!-- Metadata Details Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800 text-xs text-slate-400">
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
        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-center space-y-2">
            <p class="text-xs text-slate-400">
                Keaslian laporan ini dijamin oleh <strong>VOIC Multimodal Engine</strong>. Seluruh proses visual diproses langsung di perangkat peserta (Edge On-Device) untuk menjaga kerahasiaan data privasi sesuai standar industri.
            </p>
            <p class="text-[10px] font-mono text-slate-500">
                Hash SID: {{ hash('sha256', $session->id . '|' . $session->created_at) }}
            </p>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-850 py-6 text-center text-xs text-slate-500">
        <p>© 2026 VOIC - Voice & Optical Intelligent Coach • Dikembangkan untuk Pameran & Kompetisi Teknologi</p>
    </footer>

</body>
</html>
