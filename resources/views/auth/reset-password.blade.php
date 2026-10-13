<x-guest-layout title="Atur Ulang Kata Sandi">
    <div class="w-full max-w-md mx-auto"
         x-data="{
            password: '',
            passwordConfirmation: '',
            showPassword: false,
            showConfirmation: false,
            isSubmitting: false,
            get strengthScore() {
                let score = 0;
                if (this.password.length >= 8) score++;
                if (/[A-Z]/.test(this.password)) score++;
                if (/[0-9]/.test(this.password)) score++;
                if (/[^A-Za-z0-9]/.test(this.password)) score++;
                return score;
            },
            get strengthLabel() {
                if (!this.password) return '';
                if (this.strengthScore <= 1) return 'Lemah';
                if (this.strengthScore === 2) return 'Cukup';
                if (this.strengthScore === 3) return 'Kuat';
                return 'Sangat Kuat';
            },
            get strengthColor() {
                if (this.strengthScore <= 1) return 'bg-rose-500 text-rose-500';
                if (this.strengthScore === 2) return 'bg-amber-500 text-amber-500';
                if (this.strengthScore === 3) return 'bg-blue-500 text-blue-500';
                return 'bg-emerald-500 text-emerald-500';
            },
            get passwordsMatch() {
                return this.password && this.passwordConfirmation && this.password === this.passwordConfirmation;
            }
         }">

        <div class="p-6 sm:p-9 rounded-3xl bg-white/95 dark:bg-[#0c182b]/95 backdrop-blur-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl shadow-blue-950/15 dark:shadow-blue-950/60 relative overflow-hidden space-y-6">
            <!-- Top Accent Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600"></div>

            <!-- Header with Reset Shield Icon -->
            <div class="text-center space-y-2">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-md shadow-emerald-500/10">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Atur Ulang Kata Sandi
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Buat kata sandi baru yang kuat dan aman untuk akun VOIC Anda.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('password.store') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address Field -->
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
                               value="{{ old('email', $request->email) }}"
                               required
                               autofocus
                               autocomplete="username"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-white dark:bg-[#080f1b]/90 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500 focus:border-transparent transition shadow-xs">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Password Field with Live Strength Indicator -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="password"
                               :type="showPassword ? 'text' : 'password'"
                               name="password"
                               x-model="password"
                               required
                               autocomplete="new-password"
                               placeholder="Minimal 8 karakter"
                               class="w-full pl-10 pr-11 py-2.5 rounded-xl text-sm bg-white dark:bg-[#080f1b]/90 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500 focus:border-transparent transition shadow-xs">
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

                    <!-- Strength Meter -->
                    <div x-show="password.length > 0" x-cloak class="pt-1.5 space-y-1">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="text-slate-500 dark:text-slate-400">Kekuatan Kata Sandi:</span>
                            <span class="font-bold" :class="strengthColor.split(' ')[1]" x-text="strengthLabel"></span>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5 h-1.5">
                            <div class="rounded-full transition-colors" :class="strengthScore >= 1 ? strengthColor.split(' ')[0] : 'bg-slate-200 dark:bg-slate-700'"></div>
                            <div class="rounded-full transition-colors" :class="strengthScore >= 2 ? strengthColor.split(' ')[0] : 'bg-slate-200 dark:bg-slate-700'"></div>
                            <div class="rounded-full transition-colors" :class="strengthScore >= 3 ? strengthColor.split(' ')[0] : 'bg-slate-200 dark:bg-slate-700'"></div>
                            <div class="rounded-full transition-colors" :class="strengthScore >= 4 ? strengthColor.split(' ')[0] : 'bg-slate-200 dark:bg-slate-700'"></div>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Ulangi Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input id="password_confirmation"
                               :type="showConfirmation ? 'text' : 'password'"
                               name="password_confirmation"
                               x-model="passwordConfirmation"
                               required
                               autocomplete="new-password"
                               placeholder="Ulangi kata sandi baru"
                               class="w-full pl-10 pr-11 py-2.5 rounded-xl text-sm bg-white dark:bg-[#080f1b]/90 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500 focus:border-transparent transition shadow-xs">
                        <button type="button"
                                @click="showConfirmation = !showConfirmation"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                tabindex="-1">
                            <svg x-show="!showConfirmation" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showConfirmation" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />

                    <!-- Match indicator -->
                    <div x-show="passwordConfirmation.length > 0" x-cloak class="pt-1 text-[11px]">
                        <template x-if="passwordsMatch">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Kata sandi cocok
                            </span>
                        </template>
                        <template x-if="!passwordsMatch">
                            <span class="text-rose-500 font-semibold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Kata sandi belum sama
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:ring-4 focus:ring-blue-500/30 shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <span>Simpan Kata Sandi Baru</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Menyimpan...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-guest-layout>
