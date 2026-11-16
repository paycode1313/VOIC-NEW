<x-guest-layout title="Konfirmasi kata sandi">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Konfirmasi kata sandi</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Area ini aman. Masukkan kata sandimu untuk melanjutkan.</p>
        <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="password" value="Kata sandi" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <x-primary-button class="w-full py-3">Konfirmasi</x-primary-button>
        </form>
    </div>
</x-guest-layout>
