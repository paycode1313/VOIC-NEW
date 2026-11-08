@php
    $blueprint = app(\App\Services\Exam\ExamBlueprint::class);
    $modes = $blueprint->modes();
    $accents = config('exam.accents');
@endphp

<x-exam-layout title="Latihan wawancara, sidang & pitching dengan AI">
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-12 sm:px-6 sm:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    AI berjalan di laptop, tanpa internet
                </span>
                <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl">Latihan bicara dengan penguji AI yang benar-benar mendengarkan.</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600 dark:text-slate-400">
                    Simulasi wawancara kerja, sidang skripsi, dan pitching startup. Ngobrol pakai suara, lalu dapat rapor band score dan masukan untuk setiap jawaban.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('exam.index') }}" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-6 py-3.5 text-base font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        Mulai latihan gratis
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                    </a>
                    <a href="#cara-kerja" class="inline-flex items-center rounded-full border border-black/10 px-6 py-3.5 text-base font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">Lihat cara kerja</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">Tanpa daftar akun. Daftar hanya jika ingin menyimpan perkembangan.</p>
            </div>

            {{-- Preview of a live turn, built from real UI pieces --}}
            <div class="rounded-3xl border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.03] sm:p-6" aria-hidden="true">
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-slate-900 px-2.5 py-1 text-white dark:bg-white dark:text-slate-900">Bagian 3 · Diskusi Mendalam</span>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">● AI lokal aktif</span>
                </div>
                <div class="mt-5 flex items-start gap-3">
                    <img src="{{ asset('images/avatars/dosen.webp') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                    <p class="rounded-2xl rounded-tl-md bg-[#f6f6f3] px-4 py-3 text-[15px] dark:bg-white/5">Kenapa Anda memilih YOLOv8 dan bukan metode lain?</p>
                </div>
                <div class="mt-3 flex justify-end">
                    <p class="max-w-[85%] rounded-2xl rounded-tr-md bg-slate-900 px-4 py-3 text-[15px] text-white dark:bg-white dark:text-slate-900">Karena butuh deteksi real-time di lini produksi, dan di data kami akurasinya 87 persen.</p>
                </div>
                <div class="mt-3 flex items-start gap-3">
                    <img src="{{ asset('images/avatars/dosen.webp') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                    <p class="rounded-2xl rounded-tl-md bg-[#f6f6f3] px-4 py-3 text-[15px] dark:bg-white/5">87 persen di data pabrik itu cukup kuat. Dari lima jenis cacat, mana yang paling sulit dideteksi, dan mengapa?</p>
                </div>
                <div class="mt-5 grid grid-cols-4 gap-2 border-t border-black/5 pt-4 text-center dark:border-white/10">
                    @foreach (['Kelancaran' => '6.5', 'Kosakata' => '7.0', 'Isi' => '6.0', 'Delivery' => '7.5'] as $label => $band)
                        <div class="rounded-xl bg-[#f6f6f3] py-2 dark:bg-white/5">
                            <p class="text-lg font-extrabold">{{ $band }}</p>
                            <p class="text-[11px] text-slate-500">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Modes --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight">Tiga ruang ujian</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            @foreach ($modes as $key => $mode)
                @php $accent = $accents[$mode['accent']] ?? $accents['indigo']; @endphp
                <a href="{{ route('exam.setup', $key) }}" class="group flex flex-col rounded-3xl border border-black/5 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/[0.03] {{ $accent['border'] }}">
                    <div class="flex items-start justify-between">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $accent['soft'] }}">@include('exam.partials.icon', ['name' => $mode['icon']])</span>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-12 w-12 rounded-full object-cover" loading="lazy">
                    </div>
                    <h3 class="mt-5 text-xl font-bold">{{ $blueprint->text($mode['title'], 'id') }}</h3>
                    <p class="mt-2 flex-1 text-sm text-slate-600 dark:text-slate-400">{{ $blueprint->text($mode['tagline'], 'id') }}</p>
                    <p class="mt-5 text-sm font-semibold {{ $accent['text'] }}">Latihan dengan {{ $mode['examiner']['name'] }} →</p>
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
            <h2 class="text-xl font-extrabold tracking-tight">Privasi: semua di laptop ini</h2>
            <p class="mt-3 text-sm opacity-80">Pengenal suara (Whisper), otak AI (Ollama), dan suara penguji (Piper) berjalan di laptop. Suaramu tidak dikirim ke internet. Data tamu dihapus otomatis setelah {{ config('exam.retention_days') }} hari, dan kamu bisa menghapusnya kapan saja dari rapor.</p>
            <a href="{{ route('exam.index') }}" class="mt-5 inline-block rounded-full bg-white px-5 py-2.5 text-sm font-bold text-slate-900 hover:bg-slate-100 dark:bg-slate-900 dark:text-white">Coba sekarang</a>
        </div>
    </section>

    <footer class="border-t border-black/5 py-8 text-center text-sm text-slate-500 dark:border-white/5">
        VOIC Prep · Voice & Optics Intelligent Coach · Innofest 2026
    </footer>
</x-exam-layout>
