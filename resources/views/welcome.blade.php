<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VOIC - AI Public Speaking & Presentation Coach</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased font-sans selection:bg-indigo-500 selection:text-white">
        <!-- Navigation Bar -->
        <nav class="sticky top-0 z-50 backdrop-blur-md bg-white/80 dark:bg-gray-900/80 border-b border-gray-200/80 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16 sm:h-20">
                    <a href="/" class="flex items-center gap-3">
                        <x-application-logo />
                    </a>

                    <div class="flex items-center gap-3">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                   class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-500/25 transition">
                                    Buka Dashboard
                                    <svg class="w-4 h-4 ms-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                    Masuk
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-md shadow-indigo-500/20 transition transform hover:-translate-y-0.5">
                                        Daftar Akun
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-28">
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80 pointer-events-none">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-indigo-500 to-purple-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 text-xs sm:text-sm font-semibold text-indigo-700 dark:text-indigo-300 mb-6">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    Karya Pameran Innofest 2026 • AI Public Speaking Coach
                </div>

                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-gray-900 dark:text-white max-w-4xl mx-auto leading-tight sm:leading-none">
                    Latih Kepercayaan Dirimu Bersama <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 bg-clip-text text-transparent">VOIC AI</span>
                </h1>

                <p class="mt-6 text-base sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                    Platform cerdas berbasis audio dan kamera web browser yang mengevaluasi kontak mata, ekspresi wajah, serta intonasi bicara untuk sidang skripsi dan pitching.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-bold rounded-2xl text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                            Menuju Dashboard Analisis
                            <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-bold rounded-2xl text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                            Mulai Latihan Sekarang
                            <svg class="w-5 h-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('login') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold rounded-2xl text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition shadow-xs">
                            Masuk Akun
                        </a>
                    @endauth
                </div>

                <!-- Feature Grid Preview -->
                <div class="mt-16 sm:mt-24 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                    <div class="bg-white dark:bg-gray-800/80 p-8 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xl shadow-gray-200/50 dark:shadow-none">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Optics & Face Tracking</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Menganalisis ekspresi wajah, kontak mata ke audiens, dan mendeteksi apakah kamu terlihat tegang atau rileks saat berbicara.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800/80 p-8 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xl shadow-gray-200/50 dark:shadow-none">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950 flex items-center justify-center text-purple-600 dark:text-purple-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Audio & Tempo Analysis</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Menghitung kata per menit (Words Per Minute) dan mendeteksi kejelasan intonasi suara agar presentasimu mudah dipahami.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800/80 p-8 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xl shadow-gray-200/50 dark:shadow-none">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Dashboard Perkembangan</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                            Simpan setiap riwayat latihan di database dan pantau peningkatan skor performamu melalui grafik interaktif.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 py-8 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <x-application-logo class="scale-75 origin-left" />
                    <span>&copy; {{ date('Y') }} Tim VOIC. Dipersiapkan untuk Pameran Innofest.</span>
                </div>
                <div>
                    Ditenagai oleh Laravel 12 & Client-Side AI Engine
                </div>
            </div>
        </footer>
    </body>
</html>
