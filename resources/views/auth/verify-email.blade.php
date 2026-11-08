<x-guest-layout title="Verifikasi email">
    <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight">Cek email kamu</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Klik tautan verifikasi yang kami kirim. Belum menerima? Kirim ulang di bawah.</p>
        @if (session('status') == 'verification-link-sent')
            <x-auth-session-status class="mt-5" status="Tautan verifikasi baru sudah dikirim ke email kamu." />
        @endif
        <div class="mt-6 flex items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>Kirim ulang</x-primary-button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-slate-500 underline hover:text-slate-900 dark:hover:text-white">Keluar</button>
            </form>
        </div>
    </div>
</x-guest-layout>
