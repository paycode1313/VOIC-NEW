<x-guest-layout title="Lupa kata sandi">
    <div class="mb-6 flex flex-col items-center text-center">
        <div class="mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-b from-white to-slate-50 shadow-card ring-1 ring-black/[0.08]">
            <img src="{{ asset('images/logo/voic-logo-mark.svg') }}" alt="VOIC" class="h-8 w-8">
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Atur ulang kata sandi</h1>
        <p class="mt-1 text-sm text-slate-500">Masukkan email akunmu dan kami akan mengirimkan tautan pemulihan</p>
    </div>

    <div class="rounded-[1.75rem] border border-black/[0.07] bg-white/95 p-6 shadow-card backdrop-blur-xl sm:p-7">
        <x-auth-session-status class="mb-5" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">Alamat Email</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                    </span>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="nama@email.com"
                        class="block w-full rounded-xl border border-slate-200/90 bg-slate-50/70 py-3.5 pl-11 pr-4 text-[15px] font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 via-brand-700 to-indigo-700 py-3.5 text-[15px] font-bold text-white shadow-glow-blue transition hover:brightness-105 active:scale-[0.99]"
            >
                Kirim Tautan Pemulihan
            </button>
        </form>

        <p class="mt-5 text-center text-sm">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-bold text-slate-600 hover:text-slate-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Kembali ke halaman masuk
            </a>
        </p>
    </div>
</x-guest-layout>
