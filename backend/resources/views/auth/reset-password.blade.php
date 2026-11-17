<x-guest-layout title="Atur ulang kata sandi">
    <div class="mb-6 flex flex-col items-center text-center">
        <div class="mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-b from-white to-slate-50 shadow-card ring-1 ring-black/[0.08]">
            <img src="{{ asset('images/logo/voic-logo-mark.svg') }}" alt="VOIC" class="h-8 w-8">
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Buat kata sandi baru</h1>
        <p class="mt-1 text-sm text-slate-500">Gunakan minimal 8 karakter untuk mengamankan akun VOIC kamu</p>
    </div>

    <div class="rounded-[1.75rem] border border-black/[0.07] bg-white/95 p-6 shadow-card backdrop-blur-xl sm:p-7">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <x-input-label for="email" value="Alamat Email" />
                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="password" value="Kata Sandi Baru" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang kata sandi baru" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>
            <x-primary-button class="w-full py-3.5">Simpan Kata Sandi Baru</x-primary-button>
        </form>
    </div>
</x-guest-layout>
