<x-guest-layout title="Masuk">
    {{-- Brand Header --}}
    <div class="mb-6 flex flex-col items-center text-center sm:mb-7">
        <div class="relative mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-b from-white to-slate-50 shadow-card ring-1 ring-black/[0.08]">
            <img src="{{ asset('images/logo/voic-logo-mark.svg') }}" alt="VOIC" class="h-8 w-8">
            <span class="absolute -bottom-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-white shadow ring-1 ring-black/5">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-700 ring-1 ring-brand-600/15">
            Voice &amp; Optics Intelligent Coach
        </span>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-[28px]">Selamat datang kembali</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk melanjutkan latihan wawancara, sidang, &amp; pitching</p>
    </div>

    {{-- Elevated Auth Card --}}
    <div class="overflow-hidden rounded-[1.75rem] border border-black/[0.07] bg-white/95 p-5 shadow-card backdrop-blur-xl sm:p-7">
        {{-- Segmented Mode Switcher (Masuk / Daftar) --}}
        <div class="mb-6 grid grid-cols-2 rounded-2xl bg-slate-100/90 p-1 text-xs font-bold ring-1 ring-black/[0.04]">
            <a href="{{ route('login') }}" aria-current="page" class="flex items-center justify-center rounded-xl bg-white py-2.5 text-slate-900 shadow-xs ring-1 ring-black/[0.05] transition">
                Masuk Akun
            </a>
            <a href="{{ route('register') }}" class="flex items-center justify-center rounded-xl py-2.5 text-slate-500 transition hover:text-slate-800">
                Daftar Baru
            </a>
        </div>

        <x-auth-session-status class="mb-5" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ show: false }">
            @csrf

            {{-- Email --}}
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
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="block w-full rounded-xl border border-slate-200/90 bg-slate-50/70 py-3.5 pl-11 pr-4 text-[15px] font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>

            {{-- Password --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="text-xs font-bold uppercase tracking-wider text-slate-600">Kata Sandi</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-brand-600 transition hover:text-brand-700">Lupa sandi?</a>
                    @endif
                </div>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </span>
                    <input
                        id="password"
                        x-bind:type="show ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="block w-full rounded-xl border border-slate-200/90 bg-slate-50/70 py-3.5 pl-11 pr-12 text-[15px] font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10"
                    >
                    <button
                        type="button"
                        @click="show = !show"
                        :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        class="absolute inset-y-0 right-1.5 my-auto flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    >
                        <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            {{-- Remember me --}}
            <div class="flex items-center justify-between pt-0.5">
                <label for="remember_me" class="inline-flex cursor-pointer select-none items-center gap-2.5 text-sm font-medium text-slate-600">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600"
                    >
                    Tetap masuk di perangkat ini
                </label>
            </div>

            {{-- Primary CTA --}}
            <button
                type="submit"
                class="group relative mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 via-brand-700 to-indigo-700 py-3.5 text-[15px] font-bold text-white shadow-glow-blue transition duration-200 hover:brightness-105 active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
            >
                <span>Masuk ke Akun VOIC</span>
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        {{-- Divider --}}
        <div class="relative my-5 flex items-center justify-center">
            <div class="w-full border-t border-slate-200/80"></div>
            <span class="shrink-0 whitespace-nowrap bg-white px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">atau akses cepat</span>
            <div class="w-full border-t border-slate-200/80"></div>
        </div>

        {{-- Instant Guest Practice CTA --}}
        <a
            href="{{ route('exam.index') }}"
            class="group flex w-full items-center justify-between rounded-xl border border-slate-200/90 bg-slate-50/80 px-4 py-3 text-left transition hover:border-brand-300 hover:bg-brand-50/40 active:scale-[0.99]"
        >
            <div class="flex items-center gap-3">
                <div class="flex -space-x-2">
                    <img src="{{ asset('images/avatars/hrd-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-white">
                    <img src="{{ asset('images/avatars/dosen-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-white">
                    <img src="{{ asset('images/avatars/investor-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-white">
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">Coba Simulasi Tanpa Akun</p>
                    <p class="text-[11px] text-slate-500">Langsung bicara dengan 3 penguji AI</p>
                </div>
            </div>
            <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    </div>

    {{-- Bottom Switch Link --}}
    <p class="mt-5 text-center text-sm text-slate-600">
        Belum punya akun VOIC?
        <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:text-brand-700 hover:underline">Daftar gratis sekarang</a>
    </p>
</x-guest-layout>
