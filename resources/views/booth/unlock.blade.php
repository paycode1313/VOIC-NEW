<x-exam-layout title="Operator Booth">
    <div class="mx-auto max-w-sm px-4 pt-20">
        <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h1 class="text-xl font-extrabold tracking-tight">Operator booth</h1>
            @if ($enabled)
                <form method="POST" action="{{ route('booth.unlock') }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="pin" class="block text-sm font-semibold">PIN operator</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="off" autofocus required
                           class="w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-lg tracking-widest focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                    <x-input-error :messages="$errors->get('pin')" />
                    <button type="submit" class="w-full rounded-full bg-slate-900 px-5 py-3 font-bold text-white dark:bg-white dark:text-slate-900">Masuk</button>
                </form>
            @else
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Mode operator belum aktif. Isi <code class="rounded bg-black/5 px-1 dark:bg-white/10">VOIC_BOOTH_PIN</code> di file <code class="rounded bg-black/5 px-1 dark:bg-white/10">.env</code>, lalu muat ulang halaman ini.</p>
            @endif
        </div>
    </div>
</x-exam-layout>
