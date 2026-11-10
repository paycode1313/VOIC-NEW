<x-guest-layout title="Daftar">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Buat akun</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Gratis. Semua latihanmu tersimpan dan bisa dibandingkan dari waktu ke waktu.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="name" value="Nama" />
                <x-text-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" value="Kata sandi" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            <x-primary-button class="w-full py-3">Daftar</x-primary-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-slate-900 underline dark:text-white">Masuk</a>
        </p>
    </div>
</x-guest-layout>
