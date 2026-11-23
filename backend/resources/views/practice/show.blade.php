{{--
    Archive report for sessions from the first VOIC version (before the three-part mock exam).
    New sessions are exams with their own report (exam/result); this page only keeps older practice records readable:
    scores, the examiner's conclusion, strengths and improvements, the benchmark, the transcript and the certificate.
--}}
@php
    $notes = $session->feedback_notes ?? [];
    $isDemo = $isDemo ?? false;
    $overall = (float) ($session->overall_score ?? 0);
    $scoreColor = fn (?float $value) => $value === null ? 'bg-slate-300' : ($value >= 85 ? 'bg-emerald-500' : ($value >= 70 ? 'bg-amber-500' : 'bg-rose-500'));
    $scores = array_filter([
        'Wajah & tatapan' => $session->face_score,
        'Suara' => $session->voice_score,
        'Dinamika nada' => $notes['pitch_dynamics_score'] ?? null,
        'Kejelasan' => $notes['clarity_score'] ?? null,
    ], fn ($value) => $value !== null);
    $facial = [
        'tegang' => ['Tegang', 'bg-rose-500'],
        'tersenyum' => ['Senyum Rileks', 'bg-emerald-500'],
        'mata_melenceng' => ['Tatapan Melenceng', 'bg-amber-500'],
        'fokus' => ['Fokus', 'bg-sky-500'],
    ];
    $minutes = intdiv((int) $session->duration_seconds, 60);
    $seconds = (int) $session->duration_seconds % 60;
@endphp

<x-exam-layout title="Rapor latihan" :back="$isDemo ? route('exam.index') : route('practice.history')" :back-label="$isDemo ? 'Latihan' : 'Riwayat'">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">

        <p class="mb-4 rounded-2xl bg-slate-900/[0.05] px-4 py-2.5 text-sm text-slate-600 print:hidden">
            Ini rapor dari VOIC versi pertama. Latihan baru memakai simulasi ujian tiga bagian dengan band score 0–9.
            <a href="{{ route('exam.index') }}" class="font-semibold text-indigo-600 hover:underline">Coba simulasi ujian</a>
        </p>

        {{-- Score --}}
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">
            <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(50%_90%_at_12%_50%,rgb(99_102_241/0.25),transparent_70%)]"></div>
            <div class="grid gap-8 md:grid-cols-[auto_1fr] md:items-center">
                <div class="relative mx-auto flex h-36 w-36 items-center justify-center">
                    <svg class="absolute inset-0 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="9" class="text-slate-200"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke-width="9" stroke-linecap="round" class="voic-ring-draw" style="stroke: rgb(99 102 241); --full: {{ round(2 * M_PI * 52, 2) }}"
                                stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - min(100, $overall) / 100), 2) }}"/>
                    </svg>
                    <div class="text-center">
                        <p class="font-display text-4xl font-extrabold">{{ number_format($overall, 1) }}</p>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">dari 100</p>
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-slate-900/[0.06] px-3 py-1">{{ $role?->name ?? 'Evaluator VOIC' }}</span>
                        <span class="rounded-full bg-slate-900/[0.06] px-3 py-1 text-slate-600">{{ $session->scenario_type }}</span>
                        @if ($session->duration_seconds)
                            <span class="rounded-full bg-slate-900/[0.06] px-3 py-1 text-slate-600">{{ $minutes }}:{{ str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </div>
                    <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">{{ $benchmarkData['readiness_status'] }}</h1>
                    @if ($session->ai_conclusion)
                        <p class="mt-2 max-w-2xl text-slate-600">{{ $session->ai_conclusion }}</p>
                    @endif
                    <p class="mt-3 text-sm text-slate-500">{{ $session->getParticipantName() }} · {{ $session->created_at?->translatedFormat('d M Y, H:i') }}</p>
                </div>
            </div>

            @if ($scores)
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($scores as $label => $value)
                        <div>
                            <div class="flex items-baseline justify-between text-sm"><span class="font-semibold">{{ $label }}</span><span class="font-mono">{{ number_format((float) $value, 1) }}</span></div>
                            <div class="mt-1.5 h-2 rounded-full bg-slate-100"><div class="voic-grow h-2 rounded-full {{ $scoreColor((float) $value) }}" style="width: {{ min(100, (float) $value) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Strengths and improvements --}}
        @if (! empty($notes['strengths']) || ! empty($notes['improvements']))
            <section class="mt-6 grid gap-6 md:grid-cols-2">
                @foreach ([['Sudah bagus', $notes['strengths'] ?? [], 'text-emerald-500', 'M5 13l4 4L19 7'], ['Perlu diperbaiki', $notes['improvements'] ?? [], 'text-amber-500', 'M12 9v3.75m0 3.75h.008']] as [$heading, $items, $color, $icon])
                    @if ($items)
                        <div class="rounded-3xl border border-black/5 bg-white p-6">
                            <h2 class="text-lg font-extrabold">{{ $heading }}</h2>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                @foreach ((array) $items as $item)
                                    <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 {{ $color }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        {{-- Benchmark --}}
        <section class="mt-6 rounded-3xl border border-black/5 bg-white p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Tolak Ukur Standar Industri</p>
            <h2 class="mt-1 text-xl font-extrabold">Komparasi Metrik Performa Anda vs Standar Kelulusan</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $benchmarkData['community_label'] ?? $benchmarkData['percentile_rank'] }}. {{ $benchmarkData['personal_progression']['summary'] ?? '' }}</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[32rem] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="py-2 pr-4 font-semibold">Metrik</th><th class="py-2 pr-4 font-semibold">Kamu</th><th class="py-2 pr-4 font-semibold">Acuan</th><th class="py-2 font-semibold">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($benchmarkData['metrics'] as $metric)
                            <tr>
                                <td class="py-2.5 pr-4 font-semibold">{{ $metric['label'] }}</td>
                                <td class="py-2.5 pr-4 font-mono">{{ $metric['candidate_val'] }}</td>
                                <td class="py-2.5 pr-4 text-slate-500">{{ $metric['benchmark_val'] }}</td>
                                <td class="py-2.5"><span @class(['rounded-full px-2.5 py-0.5 text-xs font-bold', 'bg-emerald-100 text-emerald-800' => $metric['is_superior'], 'bg-amber-100 text-amber-800' => ! $metric['is_superior']])>{{ $metric['status_badge'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Transcript --}}
        @if ($messages->isNotEmpty())
            <section class="mt-6 rounded-3xl border border-black/5 bg-white p-6">
                <h2 class="text-xl font-extrabold">Transkrip</h2>
                <ol class="mt-4 space-y-3">
                    @foreach ($messages as $message)
                        @php($status = $facial[$message->facial_status['status'] ?? ''] ?? null)
                        <li @class(['max-w-[85%] rounded-2xl px-4 py-2.5 text-sm', 'ml-auto bg-indigo-50 text-slate-900' => $message->sender === 'user', 'bg-slate-100 text-slate-800' => $message->sender !== 'user'])>
                            <p class="text-xs font-bold text-slate-500">{{ $message->sender === 'user' ? 'Kamu' : ($role?->name ?? 'Penguji') }}</p>
                            <p class="mt-0.5 leading-relaxed">{{ $message->message }}</p>
                            @if ($status)
                                <span class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500"><span class="h-1.5 w-1.5 rounded-full {{ $status[1] }}"></span>{{ $status[0] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        {{-- Certificate --}}
        <section class="mt-6 flex flex-col items-center gap-6 rounded-3xl bg-slate-900 p-6 text-white sm:flex-row sm:p-8">
            <div class="flex-1 text-center sm:text-left">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-300">Kredensial digital VOIC</p>
                <h2 class="mt-1 text-2xl font-extrabold">Sertifikat Evaluasi Terverifikasi Sistem</h2>
                <p class="mt-2 text-sm text-slate-300">Pindai QR atau buka tautan verifikasi untuk memastikan nilai ini asli dari VOIC.</p>
                <p class="mt-3 font-mono text-xs text-indigo-200">No. Seri: <strong class="text-white">{{ $benchmarkData['serial'] }}</strong></p>
                <div class="mt-4 flex flex-wrap justify-center gap-2.5 sm:justify-start print:hidden" x-data="{ copied: false }">
                    <a href="{{ $session->verifyUrl() }}" target="_blank" rel="noopener" class="rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-900 hover:bg-slate-200">Halaman verifikasi</a>
                    <button type="button" @click="navigator.clipboard?.writeText(@js($session->verifyUrl())); copied = true; setTimeout(() => copied = false, 2000)"
                            class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10" x-text="copied ? 'Tautan disalin!' : 'Salin tautan'">Salin tautan</button>
                    <button type="button" onclick="window.print()" class="rounded-full border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">Cetak PDF</button>
                </div>
            </div>
            <div id="legacy-qr" data-url="{{ $session->verifyUrl() }}" class="h-36 w-36 shrink-0 rounded-2xl bg-white p-2 [&_svg]:h-full [&_svg]:w-full" aria-label="QR code verifikasi sertifikat"></div>
        </section>

        @if (! $isDemo && $session->user_id === auth()->id())
            <form method="POST" action="{{ route('practice.destroy', $session) }}" class="mt-6 text-center print:hidden" onsubmit="return confirm('Hapus rapor ini secara permanen?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Hapus rapor ini</button>
            </form>
        @endif
    </div>

    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script>
        (function () {
            var box = document.getElementById('legacy-qr');
            if (!box || typeof qrcode !== 'function') return;
            var qr = qrcode(0, 'M');
            qr.addData(box.dataset.url);
            qr.make();
            box.innerHTML = qr.createSvgTag(3, 2);
        })();
    </script>
</x-exam-layout>
