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
        <div class="grid items-center gap-14 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <span class="voic-rise inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    <span class="relative flex h-2 w-2"><span class="absolute inset-0 animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative h-2 w-2 rounded-full bg-emerald-500"></span></span>
                    {{ $online ? '3 penguji AI lagi online' : 'AI berjalan di laptop, tanpa internet' }}
                </span>
                <h1 data-split data-split-delay="0.1" class="mt-5 text-[2.75rem] font-extrabold leading-[1] sm:text-6xl xl:text-[3.9rem]">
                    Latihan <span class="voic-mark" style="--mark: #fde68a">ngomong</span><br class="hidden sm:block"> bareng
                    <span class="whitespace-nowrap bg-gradient-to-r from-amber-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent">penguji AI.</span>
                </h1>
                <p class="voic-rise mt-6 max-w-xl text-lg text-slate-600 dark:text-slate-400" style="--d: 160ms">
                    Wawancara kerja, sidang skripsi, atau pitching bisnis. Penguji menanggapi isi jawabanmu dan nanya balik, kayak ditelepon beneran. Selesai panggilan, kamu dapat rapor dan masukan.
                </p>
                <div class="voic-rise mt-8 flex flex-wrap gap-3" style="--d: 240ms">
                    <a href="{{ route('exam.index') }}" class="group inline-flex items-center gap-2 rounded-full bg-slate-900 px-6 py-3.5 text-base font-bold text-white shadow-[0_14px_30px_-12px_rgb(15_23_42/0.7)] transition hover:-translate-y-0.5 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        <svg class="h-5 w-5 transition group-hover:-rotate-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                        Mulai panggilan gratis
                    </a>
                    <a href="#cara-kerja" class="inline-flex items-center rounded-full border border-black/10 bg-white/60 px-6 py-3.5 text-base font-semibold backdrop-blur transition hover:bg-white dark:border-white/15 dark:hover:bg-white/10">Lihat cara kerja</a>
                </div>
                <div class="voic-rise mt-6 flex items-center gap-3" style="--d: 320ms">
                    <div class="flex shrink-0 -space-x-2.5">
                        @foreach ($modes as $mode)
                            <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-white" loading="lazy">
                        @endforeach
                    </div>
                    <p class="text-sm text-slate-500">Tanpa daftar akun. Daftar hanya kalau mau menyimpan perkembangan.</p>
                </div>
            </div>

            {{-- Preview of a call, built from the real room's pieces, with the examiners' catchphrases floating around it --}}
            <div class="voic-rise relative" style="--d: 200ms">
            @foreach ($modes as $mode)
                @php
                    $spot = [
                        'amber' => '-left-12 top-[58%] -rotate-6',
                        'indigo' => '-right-6 -top-7 rotate-3',
                        'violet' => '-bottom-6 left-14 rotate-[-2deg]',
                    ][$mode['accent']] ?? 'hidden';
                @endphp
                <div class="voic-bubble animate-float-subtle absolute z-10 hidden items-center gap-2 py-1.5 pl-1.5 pr-3 lg:flex {{ $spot }}" style="animation-delay: {{ $loop->index * 0.9 }}s" aria-hidden="true">
                    <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-7 w-7 rounded-full object-cover" loading="lazy">
                    <span class="text-xs font-bold text-slate-800">{{ $blueprint->text($mode['examiner']['catchphrase'] ?? '', 'id') }}</span>
                </div>
            @endforeach
            <div class="overflow-hidden rounded-3xl bg-[#eef1f8] p-3 shadow-2xl shadow-indigo-900/20 ring-1 ring-black/10 dark:ring-white/10" aria-hidden="true" style="--glow: 99 102 241">
                <div class="flex items-center justify-between gap-2 whitespace-nowrap px-2 pb-3 pt-1 text-xs font-semibold text-slate-900">
                    <span class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-rose-500/15 px-1.5 py-0.5 font-bold uppercase tracking-wider text-rose-600"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>Live</span>
                        VOIC-SIDANG <span class="font-mono text-slate-600">06:42</span>
                    </span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-slate-900">Bagian 3<span class="hidden sm:inline"> · Tanya Jawab</span></span>
                </div>
                <div class="relative isolate aspect-video overflow-hidden rounded-2xl bg-white">
                    <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(55%_70%_at_50%_40%,rgb(var(--glow)/0.55),transparent_72%)]"></div>
                    <div class="flex h-full items-start justify-center pt-[9%]">
                        <div class="relative">
                            <span class="voic-ring absolute inset-0 rounded-full border-2" style="border-color: rgb(var(--glow) / 0.6)"></span>
                            <img src="{{ asset('images/avatars/dosen-portrait.webp') }}" alt="" class="relative h-24 w-24 rounded-full object-cover ring-[3px] ring-white/90 sm:h-32 sm:w-32">
                        </div>
                    </div>
                    <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1.5 rounded-md bg-white/90 px-2 py-0.5 text-xs font-semibold text-slate-900">
                        <span class="flex h-2.5 items-end gap-[2px]"><span class="voic-eq w-[2px] rounded-full bg-slate-900"></span><span class="voic-eq w-[2px] rounded-full bg-slate-900 [animation-delay:150ms]"></span><span class="voic-eq w-[2px] rounded-full bg-slate-900 [animation-delay:300ms]"></span></span>
                        Sedang bicara
                    </span>
                    <div class="absolute right-2.5 top-2.5 flex aspect-video w-24 items-center justify-center rounded-lg bg-slate-100 ring-2 ring-sky-400/70 sm:w-32">
                        <span class="rounded-full bg-slate-900/[0.06] px-2 py-1 text-xs font-bold text-slate-900">Kamu</span>
                    </div>
                    <p class="absolute inset-x-4 bottom-3 mx-auto max-w-md rounded-lg bg-white/90 px-3 py-1.5 text-center text-xs leading-relaxed text-slate-900 sm:text-sm">
                        Delapan puluh tujuh persen di data pabrik itu lumayan kuat. Dari lima jenis cacat, mana yang paling susah dideteksi?
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pb-1 pt-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-500"><svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg></span>
                    <span class="h-8 w-8 rounded-full bg-slate-900/[0.06]"></span>
                    <span class="h-8 w-8 rounded-full bg-slate-900/[0.06]"></span>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-black text-slate-900">CC</span>
                    <span class="h-8 w-8 rounded-full bg-slate-900/[0.06]"></span>
                    <span class="ml-2 inline-flex h-8 items-center rounded-full bg-rose-600 px-3 text-xs font-bold text-white">Akhiri</span>
                </div>
            </div>
            </div>
        </div>
    </section>

    {{-- A ticker of real topics people practise, so the page feels alive --}}
    @php
        $topics = collect($modes)->flatMap(fn ($mode) => collect($mode['topics'] ?? [])->map(fn ($topic) => [$blueprint->text($topic, 'id'), $glows[$mode['accent']] ?? '99 102 241']))->shuffle(7)->values();
    @endphp
    <div class="relative -rotate-1 overflow-hidden border-y border-black/5 bg-white/80 py-3 backdrop-blur [mask-image:linear-gradient(to_right,transparent,black_8%,black_92%,transparent)]" aria-hidden="true">
        <div class="voic-ticker flex w-max gap-3">
            @foreach ([1, 2] as $copy)
                @foreach ($topics as [$topic, $glow])
                    <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3.5 py-1.5 text-sm font-bold" style="background: rgb({{ $glow }} / 0.1); color: rgb({{ $glow }})">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: rgb({{ $glow }})"></span>{{ $topic }}
                    </span>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Examiners, like contacts you can call --}}
    <section class="mx-auto max-w-6xl px-4 pb-4 pt-16 sm:px-6">
        <h2 data-split class="text-3xl font-extrabold sm:text-4xl">Pilih penguji yang mau kamu <span class="voic-mark" style="--mark: #c7d2fe">telepon</span></h2>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            @foreach ($modes as $key => $mode)
                <a href="{{ route('exam.setup', $key) }}" data-reveal="tilt" style="--glow: {{ $glows[$mode['accent']] ?? '99 102 241' }}"
                   class="group flex items-center gap-4 rounded-3xl border border-black/5 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="relative isolate flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white">
                        <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(circle,rgb(var(--glow)/0.6),transparent_70%)]"></div>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-14 w-14 rounded-full object-cover" loading="lazy">
                        <span class="absolute bottom-1.5 right-1.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-extrabold tracking-tight">{{ $mode['examiner']['name'] }}</h3>
                        <p class="text-sm font-semibold" style="color: rgb(var(--glow))">{{ $blueprint->text($mode['examiner']['title'], 'id') }}</p>
                        <p class="mt-1 text-xs italic text-slate-500">“{{ $blueprint->text($mode['examiner']['catchphrase'] ?? '', 'id') }}”</p>
                    </div>
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white transition group-hover:scale-110" style="background: rgb(var(--glow))" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <div id="cara-kerja" class="mx-auto max-w-6xl scroll-mt-20 px-4 sm:px-6">
        @include('exam.partials.steps', ['isEn' => false, 'title' => 'Seperti ujian sungguhan, dalam 12 menit', 'fx' => true, 'steps' => [
            ['Pemanasan', 'Pertanyaan singkat tentang dirimu. Penguji menanggapi jawabanmu dan bertanya lebih dalam kalau masih kabur.', '#fde68a'],
            ['Kartu topik', 'Satu menit menyiapkan catatan, lalu bicara sampai dua menit tanpa disela. Melatih kelancaran dan struktur.', '#c7d2fe'],
            ['Diskusi', 'Pertanyaan situasional dan metodologi. Penguji menguji alasan, bukti, dan batasan argumenmu.', '#ddd6fe'],
        ]])
    </div>

    {{-- Report + privacy --}}
    <section class="mx-auto grid max-w-6xl gap-6 px-4 py-20 sm:px-6 md:grid-cols-2">
        <div data-reveal class="relative overflow-hidden rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
            {{-- A miniature report, so "rapor" is something you can see, not just read about --}}
            <div class="float-right mb-3 ml-6 mr-1 mt-1 hidden w-40 rotate-3 rounded-2xl bg-slate-900 p-3.5 text-white shadow-xl sm:block" aria-hidden="true">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">Band score</p>
                <p class="font-display text-4xl font-extrabold">7.5</p>
                @foreach ([['Kelancaran', 80, 'bg-amber-400'], ['Kosakata', 70, 'bg-sky-400'], ['Isi', 85, 'bg-indigo-400'], ['Delivery', 65, 'bg-violet-400']] as [$label, $width, $color])
                    <div class="mt-1.5">
                        <div class="flex justify-between text-[10px] text-white/70"><span>{{ $label }}</span></div>
                        <div class="mt-0.5 h-1.5 rounded-full bg-white/15"><div class="h-1.5 rounded-full {{ $color }}" style="width: {{ $width }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <h2 data-split class="text-2xl font-extrabold">Rapor yang bisa ditindaklanjuti</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Band score 0–9 untuk kelancaran, kosakata, isi, dan delivery</span></li>
                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Kata pengisi ("eh", "ehm") ditandai langsung di transkrip</span></li>
                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Versi jawaban yang lebih kuat untuk jawaban terlemah, bisa didengarkan lalu dilatih ulang bareng coach</span></li>
                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Putar ulang rekamanmu, dan grafik perkembangan antar sesi</span></li>
                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Bawa pulang lewat QR code, tanpa perlu akun</span></li>
            </ul>
        </div>
        <div data-reveal class="relative overflow-hidden rounded-3xl bg-indigo-50 p-6 text-slate-900 ring-1 ring-indigo-100">
            <svg class="pointer-events-none absolute -bottom-6 -right-6 h-40 w-40 text-indigo-200/70" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/></svg>
            @if ($online)
                <h2 data-split class="relative text-2xl font-extrabold">Privasi: yang perlu kamu tahu</h2>
                <p class="relative mt-3 text-sm opacity-80">Supaya penguji bisa menjawab dalam sekejap, teks jawabanmu diproses AI cloud (Groq), suara penguji dibuat layanan suara Microsoft, dan browser mengenali ucapanmu. Rekaman dan rapor disimpan di laptop ini. Data tamu dihapus otomatis setelah {{ config('exam.retention_days') }} hari, dan kamu bisa menghapusnya kapan saja dari rapor.</p>
            @else
                <h2 data-split class="relative text-2xl font-extrabold">Privasi: semua di laptop ini</h2>
                <p class="relative mt-3 text-sm opacity-80">Pengenal suara (Whisper), otak AI (Ollama), dan suara penguji (Piper) berjalan di laptop. Suaramu tidak dikirim ke internet. Data tamu dihapus otomatis setelah {{ config('exam.retention_days') }} hari, dan kamu bisa menghapusnya kapan saja dari rapor.</p>
            @endif
            <a href="{{ route('exam.index') }}" class="relative mt-5 inline-block rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500 dark:bg-slate-900 dark:text-white">Coba sekarang</a>
        </div>
    </section>

    <footer class="border-t border-black/5 py-8 text-center text-sm text-slate-500 dark:border-white/5">
        <img src="/images/logo/voic-logo-stacked.svg" alt="VOIC" class="mx-auto mb-3 h-16 w-auto dark:hidden" width="84" height="64">
        <img src="/images/logo/voic-logo-stacked-dark.svg" alt="VOIC" class="mx-auto mb-3 hidden h-16 w-auto dark:block" width="84" height="64">
        Voice & Optics Intelligent Coach · Innofest 2026
    </footer>
</x-exam-layout>
