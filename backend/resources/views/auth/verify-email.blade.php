<x-guest-layout title="Verifikasi email">
    <div class="mb-6 flex flex-col items-center text-center">
        <div class="mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-b from-white to-slate-50 shadow-card ring-1 ring-black/[0.08]">
            <img src="{{ asset('images/logo/voic-logo-mark.svg') }}" alt="VOIC" class="h-8 w-8">
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Verifikasi alamat email</h1>
        <p class="mt-1 text-sm text-slate-500">Kami telah mengirimkan tautan aktivasi ke kotak masuk emailmu</p>
    </div>

    <div class="rounded-[1.75rem] border border-black/[0.07] bg-white/95 p-6 shadow-card backdrop-blur-xl sm:p-7">
        <p class="text-sm leading-relaxed text-slate-600">
            Klik tautan verifikasi yang kami kirim ke email kamu untuk mengaktifkan seluruh fitur Dashboard &amp; penyimpanan riwayat rapor. Belum menerima email?
        </p>
        @if (session('status') == 'verification-link-sent')
            <x-auth-session-status class="mt-4" status="Tautan verifikasi baru sudah dikirim ke email kamu." />
        @endif
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                @csrf
                <x-primary-button class="w-full sm:w-auto">Kirim Ulang Email Verifikasi</x-primary-button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 text-center text-sm font-bold text-slate-500 transition hover:text-slate-900">Keluar</button>
            </form>
        </div>
    </div>
</x-guest-layout>
