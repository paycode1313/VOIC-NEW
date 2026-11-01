<x-guest-layout title="Lupa Kata Sandi">
    <div class="w-full max-w-md mx-auto"
         x-data="{ isSubmitting: false }">

        <div class="p-6 sm:p-9 rounded-3xl glass-ultra border border-white/80 dark:border-white/10 shadow-2xl relative overflow-hidden space-y-6 backdrop-blur-2xl">
            <!-- Glowing Specular Top Accent Line -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 dark:via-sky-400/40 to-transparent"></div>

            <!-- Header with Key / Lock Icon -->
            <div class="text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl glass-pill-nav border border-blue-400/30 dark:border-blue-500/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-lg shadow-blue-500/10 animate-float-subtle">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Lupa Kata Sandi?
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mt-1">
                        Tidak masalah. Masukkan alamat email akun VOIC Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi baru.
                    </p>
                </div>
            </div>

            <!-- Session Status Alert -->
            <x-auth-session-status :status="session('status')" />

            <!-- Forgot Password Form -->
            <form method="POST" action="{{ route('password.email') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf

                <!-- Email Address Field -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Alamat Email Akun
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
                               placeholder="nama@email.com"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-white/70 dark:bg-[#080f1b]/80 border border-slate-200/80 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 dark:focus:ring-blue-500/50 focus:border-transparent transition shadow-2xs backdrop-blur-sm">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 via-sky-600 to-blue-700 hover:from-blue-500 hover:to-sky-500 focus:ring-4 focus:ring-blue-500/30 shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>Kirim Tautan Atur Ulang Kata Sandi</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Mengirim Tautan...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>

            <!-- Back to Login Navigation -->
            <div class="pt-4 border-t border-slate-200/70 dark:border-white/10 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 glass-pill-nav hover:scale-[1.02] transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Halaman Masuk</span>
                </a>
            </div>
        </div>

    </div>
</x-guest-layout>
