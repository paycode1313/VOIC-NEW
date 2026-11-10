<x-guest-layout title="Atur ulang kata sandi">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Kata sandi baru</h1>
        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" value="Kata sandi baru" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            <x-primary-button class="w-full py-3">Simpan kata sandi</x-primary-button>
        </form>
    </div>
</x-guest-layout>
