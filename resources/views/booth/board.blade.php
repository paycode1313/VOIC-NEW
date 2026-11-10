<x-exam-layout title="Papan Skor">
    <meta http-equiv="refresh" content="20">
    <div class="mx-auto grid max-w-6xl gap-8 px-6 pb-10 pt-10 lg:grid-cols-[1.6fr_1fr]">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-slate-500">Peringkat hari ini</p>
            <h1 class="mt-1 text-5xl font-extrabold tracking-tight">Siapa paling siap?</h1>
            <div class="mt-6 rounded-3xl border border-black/5 bg-white p-6 text-lg dark:border-white/10 dark:bg-white/[0.03]">
                @include('booth.partials.leaders', ['leaders' => $leaders])
            </div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-3xl bg-slate-900 p-8 text-center text-white dark:bg-white dark:text-slate-900">
            <p class="text-2xl font-extrabold">Coba sekarang, gratis</p>
            <p class="mt-2 opacity-70">Wawancara kerja · Sidang skripsi · Pitching startup</p>
            <div id="board-qr" data-url="{{ $startUrl }}" class="mt-6 h-56 w-56 rounded-2xl bg-white p-2"></div>
            <p class="mt-4 text-sm opacity-70">Pindai, lalu ngobrol dengan penguji AI</p>
        </div>
    </div>
    @include('booth.partials.qr', ['target' => 'board-qr'])
</x-exam-layout>
