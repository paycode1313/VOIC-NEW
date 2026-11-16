<x-guest-layout title="Masuk">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Masuk</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Simpan riwayat latihan dan lihat perkembangan band score-mu.</p>

        <x-auth-session-status class="mt-5" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" x-data="{ show: false }">
            @csrf
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <div class="flex items-center justify-between">
                    <x-input-label for="password" value="Kata sandi" />
                    @if (Route::has('password.request'))
                        <a class="text-xs font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                    @endif
                </div>
                <div class="relative mt-1.5">
                    <x-text-input id="password" class="block w-full pr-16" x-bind:type="show ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" />
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 text-xs font-semibold text-slate-500" x-text="show ? 'Sembunyi' : 'Lihat'"></button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input id="remember_me" type="checkbox" class="rounded border-black/20 text-slate-900 focus:ring-slate-900" name="remember">
                Ingat saya
            </label>
            <x-primary-button class="w-full py-3">Masuk</x-primary-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
            Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-slate-900 underline dark:text-white">Daftar</a>
            <span class="mx-1 text-slate-300">·</span>
            <a href="{{ route('exam.index') }}" class="font-semibold text-slate-900 underline dark:text-white">Coba tanpa akun</a>
        </p>
    </div>
</x-guest-layout>
