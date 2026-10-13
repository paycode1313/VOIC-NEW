<x-guest-layout title="Masuk ke Akun">
    <div class="w-full max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center"
         x-data="{
            showPassword: false,
            isSubmitting: false
         }">

        <!-- Left Showcase Pane (Visible on Large Screens) -->
        <div class="hidden lg:flex lg:col-span-5 flex-col justify-between space-y-8 pr-4">
            <div class="space-y-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100/80 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 text-xs font-bold text-blue-700 dark:text-blue-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>AI Multimodal Defense & Pitch Coach</span>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
                    Kuasai Presentasi & Sidang dengan <span class="bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600 dark:from-blue-400 dark:via-sky-300 dark:to-indigo-300 bg-clip-text text-transparent">Percaya Diri Penuh</span>.
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Uji argumentasi Anda di hadapan 3 karakter evaluator AI interaktif dengan umpan balik tatapan kamera 30 FPS dan spektrum akustik real-time.
                </p>
            </div>

            <!-- 3 Evaluator Avatars Preview Card -->
            <div class="p-5 rounded-3xl bg-white/80 dark:bg-[#0c182b]/80 border border-slate-200/80 dark:border-slate-800/90 shadow-xl space-y-3">
                <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400 dark:text-slate-500 block">
                    Pilihan Karakter Evaluator AI
                </span>
                <div class="space-y-2.5">
                    <div class="flex items-center gap-3 p-2 rounded-2xl bg-slate-50 dark:bg-[#080f1b]/60 border border-slate-100 dark:border-slate-800">
                        <img src="{{ asset('images/avatars/dosen.jpg') }}" alt="Dosen" class="w-9 h-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700">
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">VOIC-Dosen Penguji</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block">Kritis, bariton berwibawa & mode interupsi</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2 rounded-2xl bg-slate-50 dark:bg-[#080f1b]/60 border border-slate-100 dark:border-slate-800">
                        <img src="{{ asset('images/avatars/hrd.jpg') }}?v=3" alt="HRD" class="w-9 h-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700">
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">VOIC-HRD Profesional</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block">Empatik, terstruktur & STAR methodology</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-2 rounded-2xl bg-slate-50 dark:bg-[#080f1b]/60 border border-slate-100 dark:border-slate-800">
                        <img src="{{ asset('images/avatars/investor.jpg') }}" alt="Investor" class="w-9 h-9 rounded-xl object-cover border border-slate-200 dark:border-slate-700">
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-900 dark:text-white block">VOIC-Investor Pitching</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block">Cepat, to-the-point & uji unit economics</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- On-Device Privacy Guarantee Badge -->
            <div class="flex items-center gap-2.5 text-xs text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Zero Cloud Video • Kamera diproses 100% lokal di browser Anda.</span>
            </div>
        </div>

        <!-- Right Form Pane -->
        <div class="w-full lg:col-span-7 max-w-md mx-auto lg:max-w-none">
            <div class="p-6 sm:p-9 rounded-3xl bg-white/95 dark:bg-[#0c182b]/95 backdrop-blur-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl shadow-blue-950/15 dark:shadow-blue-950/60 relative overflow-hidden">
                <!-- Top Accent Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600"></div>

                <!-- Form Header -->
                <div class="mb-6 space-y-1">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Selamat Datang Kembali
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        Masuk untuk melanjutkan sesi latihan dan memantau riwayat skor Anda.
                    </p>
                </div>

                <!-- Session Status (e.g. Password Reset Notification) -->
                <x-auth-session-status class="mb-5" :status="session('status')" />

                <!-- Standard Login Form -->
                <form method="POST" action="{{ route('login') }}" id="loginForm" @submit="isSubmitting = true" class="space-y-4">
                    @csrf

                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Alamat Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                                </svg>
                            </div>
                            <input id="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus
                                   autocomplete="username"
                                   placeholder="nama@email.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-white dark:bg-[#080f1b]/90 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500 focus:border-transparent transition shadow-xs">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Password Field with Show/Hide Toggle -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Kata Sandi
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                    Lupa kata sandi?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-11 py-2.5 rounded-xl text-sm bg-white dark:bg-[#080f1b]/90 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500 focus:border-transparent transition shadow-xs">
                            <!-- Toggle Visibility Button -->
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                    tabindex="-1">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer">
                            <input id="remember_me"
                                   type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 border-slate-300 dark:border-slate-700 bg-white dark:bg-[#080f1b] transition">
                            <span class="ms-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                                Ingat saya di peramban ini
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                                :disabled="isSubmitting"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:ring-4 focus:ring-blue-500/30 shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <template x-if="!isSubmitting">
                                <span class="flex items-center gap-2">
                                    <span>Masuk ke Akun</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </span>
                            </template>
                            <template x-if="isSubmitting">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Memproses...</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </form>

                <!-- Register Footer Link -->
                <div class="mt-6 pt-5 border-t border-slate-200/80 dark:border-slate-800 text-center">
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Belum memiliki akun?
                        <a href="{{ route('register') }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline ms-1">
                            Daftar Sekarang Secara Gratis
                        </a>
                    </p>
                </div>
            </div>
        </div>

    </div>
</x-guest-layout>
