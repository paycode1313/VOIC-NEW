@php
    $blueprint = app(\App\Services\Exam\ExamBlueprint::class);
    $modes = $blueprint->modes();
    $accents = config('exam.accents');
    $glows = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'];
    // Be honest about where the AI runs: the online speed mode sends answers to cloud services.
    $online = config('exam.speed_mode') === 'online';
@endphp

<x-exam-layout title="Latihan wawancara, sidang & pitching dengan AI">
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-12 sm:px-6 sm:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.15fr]">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ $online ? '3 penguji AI lagi online' : 'AI berjalan di laptop, tanpa internet' }}
                </span>
                <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">Latihan ngomong lewat video call dengan penguji AI.</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600 dark:text-slate-400">
                    Wawancara kerja, sidang skripsi, atau pitching bisnis. Penguji menanggapi isi jawabanmu dan nanya balik, kayak ditelepon beneran. Selesai panggilan, kamu dapat rapor dan masukan.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('exam.index') }}" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-6 py-3.5 text-base font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                        Mulai panggilan gratis
                    </a>
                    <a href="#cara-kerja" class="inline-flex items-center rounded-full border border-black/10 px-6 py-3.5 text-base font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">Lihat cara kerja</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Tanpa daftar akun. Daftar hanya kalau mau menyimpan perkembangan.</p>
            </div>

            {{-- Preview of a call, built from the real room's pieces --}}
            <div class="overflow-hidden rounded-3xl bg-[#0b0d12] p-3 shadow-2xl shadow-black/20 ring-1 ring-black/10 dark:ring-white/10" aria-hidden="true" style="--glow: 99 102 241">
                <div class="flex items-center justify-between px-2 pb-3 pt-1 text-[11px] font-semibold text-white">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-rose-500/15 px-1.5 py-0.5 font-bold uppercase tracking-wider text-rose-300"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>Live</span>
                        VOIC-SIDANG <span class="font-mono text-white/40">06:42</span>
                    </span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-slate-900">Bagian 3 · Tanya Jawab</span>
                </div>
                <div class="relative isolate aspect-video overflow-hidden rounded-2xl bg-[#121521]">
                    <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(55%_70%_at_50%_40%,rgb(var(--glow)/0.55),transparent_72%)]"></div>
                    <div class="flex h-full items-start justify-center pt-[9%]">
                        <div class="relative">
                            <span class="voic-ring absolute inset-0 rounded-full border-2" style="border-color: rgb(var(--glow) / 0.6)"></span>
                            <img src="{{ asset('images/avatars/dosen-portrait.webp') }}" alt="" class="relative h-24 w-24 rounded-full object-cover ring-[3px] ring-white/90 sm:h-32 sm:w-32">
                        </div>
                    </div>
                    <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1.5 rounded-md bg-black/50 px-2 py-0.5 text-[10px] font-semibold text-white">
                        <span class="flex h-2.5 items-end gap-[2px]"><span class="voic-eq w-[2px] rounded-full bg-white"></span><span class="voic-eq w-[2px] rounded-full bg-white [animation-delay:150ms]"></span><span class="voic-eq w-[2px] rounded-full bg-white [animation-delay:300ms]"></span></span>
                        Sedang bicara
                    </span>
                    <div class="absolute right-2.5 top-2.5 flex aspect-video w-24 items-center justify-center rounded-lg bg-[#1b1f2e] ring-2 ring-sky-400/70 sm:w-32">
                        <span class="rounded-full bg-white/10 px-2 py-1 text-[10px] font-bold text-white">Kamu</span>
                    </div>
                    <p class="absolute inset-x-4 bottom-3 mx-auto max-w-md rounded-lg bg-black/65 px-3 py-1.5 text-center text-xs leading-relaxed text-white sm:text-sm">
                        Delapan puluh tujuh persen di data pabrik itu lumayan kuat. Dari lima jenis cacat, mana yang paling susah dideteksi?
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pb-1 pt-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-500"><svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg></span>
                    <span class="h-8 w-8 rounded-full bg-white/10"></span>
                    <span class="h-8 w-8 rounded-full bg-white/10"></span>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-[9px] font-black text-slate-900">CC</span>
                    <span class="h-8 w-8 rounded-full bg-white/10"></span>
                    <span class="ml-2 inline-flex h-8 items-center rounded-full bg-rose-600 px-3 text-[11px] font-bold text-white">Akhiri</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Examiners, like contacts you can call --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight">Pilih penguji yang mau kamu telepon</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            @foreach ($modes as $key => $mode)
                <a href="{{ route('exam.setup', $key) }}" style="--glow: {{ $glows[$mode['accent']] ?? '99 102 241' }}"
                   class="group flex items-center gap-4 rounded-3xl border border-black/5 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="relative isolate flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#121521]">
                        <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(circle,rgb(var(--glow)/0.6),transparent_70%)]"></div>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-14 w-14 rounded-full object-cover" loading="lazy">
                        <span class="absolute bottom-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#121521]"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-extrabold tracking-tight">{{ $mode['examiner']['name'] }}</h3>
                        <p class="text-sm font-semibold" style="color: rgb(var(--glow))">{{ $blueprint->text($mode['examiner']['title'], 'id') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $blueprint->text($mode['title'], 'id') }} · ~12 menit</p>
                    </div>
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white transition group-hover:scale-110" style="background: rgb(var(--glow))" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section id="cara-kerja" class="border-y border-black/5 bg-white py-16 dark:border-white/5 dark:bg-white/[0.02]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="text-2xl font-extrabold tracking-tight">Seperti ujian sungguhan, dalam 12 menit</h2>
            <div class="mt-8 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['Bagian 1 · Pemanasan', 'Pertanyaan singkat tentang dirimu. Penguji menanggapi jawabanmu dan bertanya lebih dalam kalau masih kabur.'],
                    ['Bagian 2 · Kartu topik', 'Satu menit menyiapkan catatan, lalu bicara sampai dua menit tanpa disela. Melatih kelancaran dan struktur.'],
                    ['Bagian 3 · Diskusi', 'Pertanyaan situasional dan metodologi. Penguji menguji alasan, bukti, dan batasan argumenmu.'],
                ] as $i => [$title, $body])
                    <div>
                        <span class="text-sm font-bold text-slate-400">0{{ $i + 1 }}</span>
                        <h3 class="mt-1 font-bold">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Report + privacy --}}
    <section class="mx-auto grid max-w-6xl gap-6 px-4 py-16 sm:px-6 md:grid-cols-2">
        <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-xl font-extrabold tracking-tight">Rapor yang bisa ditindaklanjuti</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-slate-600 dark:text-slate-400">
                <li>✓ Band score 0–9 untuk kelancaran, kosakata, isi, dan delivery</li>
                <li>✓ Kata pengisi ("eh", "ehm") ditandai langsung di transkrip</li>
                <li>✓ Versi jawaban yang lebih kuat untuk jawaban terlemah</li>
                <li>✓ Putar ulang rekamanmu, dan grafik perkembangan antar sesi</li>
                <li>✓ Bawa pulang lewat QR code, tanpa perlu akun</li>
            </ul>
        </div>
        <div class="rounded-3xl bg-slate-900 p-6 text-white dark:bg-white dark:text-slate-900">
            @if ($online)
                <h2 class="text-xl font-extrabold tracking-tight">Privasi: yang perlu kamu tahu</h2>
                <p class="mt-3 text-sm opacity-80">Supaya penguji bisa menjawab dalam sekejap, teks jawabanmu diproses AI cloud (Groq), suara penguji dibuat layanan suara Microsoft, dan browser mengenali ucapanmu. Rekaman dan rapor disimpan di laptop ini. Data tamu dihapus otomatis setelah {{ config('exam.retention_days') }} hari, dan kamu bisa menghapusnya kapan saja dari rapor.</p>
            @else
                <h2 class="text-xl font-extrabold tracking-tight">Privasi: semua di laptop ini</h2>
                <p class="mt-3 text-sm opacity-80">Pengenal suara (Whisper), otak AI (Ollama), dan suara penguji (Piper) berjalan di laptop. Suaramu tidak dikirim ke internet. Data tamu dihapus otomatis setelah {{ config('exam.retention_days') }} hari, dan kamu bisa menghapusnya kapan saja dari rapor.</p>
            @endif
            <a href="{{ route('exam.index') }}" class="mt-5 inline-block rounded-full bg-white px-5 py-2.5 text-sm font-bold text-slate-900 hover:bg-slate-100 dark:bg-slate-900 dark:text-white">Coba sekarang</a>
        </div>
    </section>

    <footer class="border-t border-black/5 py-8 text-center text-sm text-slate-500 dark:border-white/5">
        VOIC Prep · Voice & Optics Intelligent Coach · Innofest 2026
    </footer>
</x-exam-layout>
