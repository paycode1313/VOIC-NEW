<x-guest-layout title="Lupa kata sandi">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Lupa kata sandi</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Masukkan email akunmu, kami kirimkan tautan untuk membuat kata sandi baru.</p>
        <x-auth-session-status class="mt-5" :status="session('status')" />
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <x-primary-button class="w-full py-3">Kirim tautan reset</x-primary-button>
        </form>
        <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold underline">Kembali ke halaman masuk</a></p>
    </div>
</x-guest-layout>
