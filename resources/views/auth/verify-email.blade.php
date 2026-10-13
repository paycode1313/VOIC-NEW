<x-guest-layout title="Verifikasi Email">
    <div class="w-full max-w-md mx-auto"
         x-data="{ isSubmitting: false }">

        <div class="p-6 sm:p-9 rounded-3xl bg-white/95 dark:bg-[#0c182b]/95 backdrop-blur-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl shadow-blue-950/15 dark:shadow-blue-950/60 relative overflow-hidden space-y-6">
            <!-- Top Accent Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-600 via-sky-500 to-indigo-600"></div>

            <!-- Header with Mail Envelope Icon -->
            <div class="text-center space-y-2">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-sky-50 dark:bg-sky-950/70 border border-sky-200 dark:border-sky-800 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-md shadow-sky-500/10">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Verifikasi Alamat Email
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Terima kasih telah bergabung di VOIC! Sebelum memulai, silakan periksa kotak masuk email Anda dan klik tautan verifikasi yang telah kami kirimkan.
                </p>
            </div>

            <!-- Success Status Alert when Link is Re-sent -->
            @if (session('status') == 'verification-link-sent')
                <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-300 dark:border-emerald-800 text-xs font-semibold text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Tautan verifikasi baru berhasil dikirimkan ke alamat email Anda.</span>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="space-y-3 pt-2">
                <form method="POST" action="{{ route('verification.send') }}" @submit="isSubmitting = true">
                    @csrf
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 focus:ring-4 focus:ring-blue-500/30 shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Kirim Ulang Email Verifikasi</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Mengirim...</span>
                            </span>
                        </template>
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition cursor-pointer">
                        Keluar dari Akun (Logout)
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-guest-layout>
