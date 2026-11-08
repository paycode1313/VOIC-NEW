@php
    $isEn = $language === 'en';
    $accent = config('exam.accents')[$mode['accent']] ?? config('exam.accents.indigo');
    $band = (float) $report['overall_band'];
    $stats = $report['stats'];
    $fillerWords = (array) config('exam.fillers', []);

    $highlight = function (string $text) use ($fillerWords): string {
        $escaped = e($text);
        foreach ($fillerWords as $filler) {
            $escaped = preg_replace('/(?<![\p{L}\p{N}])('.preg_quote(e($filler), '/').')(?![\p{L}\p{N}])/iu', '<mark class="rounded bg-amber-200/70 px-0.5 text-inherit dark:bg-amber-400/30">$1</mark>', $escaped) ?? $escaped;
        }

        return $escaped;
    };

    $bandColor = fn (float $value) => $value >= 7 ? 'bg-emerald-500' : ($value >= 5.5 ? 'bg-amber-500' : 'bg-rose-500');
    $minutes = intdiv((int) $session->duration_seconds, 60);
    $seconds = (int) $session->duration_seconds % 60;
@endphp

<x-exam-layout :title="$isEn ? 'Report' : 'Rapor'" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">

        {{-- Hero --}}
        <section class="grid gap-6 rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8 md:grid-cols-[auto_1fr] md:items-center">
            <div class="flex items-center gap-5">
                <div class="relative flex h-32 w-32 items-center justify-center">
                    <svg class="absolute inset-0 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" class="text-black/5 dark:text-white/10"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                                class="text-slate-900 dark:text-white" stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - $band / 9), 2) }}"/>
                    </svg>
                    <div class="text-center">
                        <p class="text-4xl font-extrabold tracking-tight">{{ number_format($band, 1) }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Band</p>
                    </div>
                </div>
            </div>
            <div>
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $accent['soft'] }}">
                    @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-4 w-4'])
                    {{ $session->scenario_type }}
                </span>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight">{{ $report['level'] }}</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">{{ $session->ai_conclusion }}</p>
                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-500">
                    <span>{{ $session->created_at->translatedFormat('d M Y, H:i') }}</span>
                    <span>{{ $minutes }}:{{ str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) }} {{ $isEn ? 'min' : 'menit' }}</span>
                    <span>{{ $stats['answers'] }} {{ $isEn ? 'answers' : 'jawaban' }}</span>
                </div>
            </div>
        </section>

        {{-- Take it home: QR to a signed, read-only copy of this report --}}
        @unless ($shared)
            <section class="mt-4 flex flex-col items-center gap-5 rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:flex-row">
                <div id="report-qr" data-url="{{ $shareUrl }}" class="h-32 w-32 shrink-0 rounded-xl bg-white p-1.5 [&_svg]:h-full [&_svg]:w-full" aria-label="{{ $isEn ? 'QR code to this report' : 'QR code rapor ini' }}"></div>
                <div class="text-center sm:text-left">
                    <p class="font-bold">{{ $isEn ? 'Take your report home' : 'Bawa pulang rapormu' }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        {{ $isEn ? 'Scan with your phone camera. The link works for 7 days, no account needed.' : 'Pindai dengan kamera HP. Tautan berlaku 7 hari, tanpa perlu akun.' }}
                    </p>
                    @if (str_contains($shareUrl, '://localhost') || str_contains($shareUrl, '://127.0.0.1'))
                        <p class="mt-2 rounded-lg bg-amber-50 px-2 py-1 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">
                            {{ $isEn ? 'Operator: set VOIC_PUBLIC_URL (LAN IP or tunnel) so phones can open this.' : 'Operator: isi VOIC_PUBLIC_URL di .env (IP LAN atau tunnel) agar QR bisa dibuka di HP.' }}
                        </p>
                    @endif
                    @if ($canManage)
                        <form method="POST" action="{{ route('exam.destroy', $session) }}" class="mt-3"
                              onsubmit="return confirm('{{ $isEn ? 'Delete this session, transcript and recordings permanently?' : 'Hapus sesi, transkrip, dan rekaman ini secara permanen?' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">{{ $isEn ? 'Delete my data' : 'Hapus data saya' }}</button>
                        </form>
                    @endif
                </div>
            </section>
            <script src="{{ asset('js/qrcode.min.js') }}"></script>
            <script>
                (function () {
                    var box = document.getElementById('report-qr');
                    if (!box || typeof qrcode !== 'function') return;
                    var qr = qrcode(0, 'M');
                    qr.addData(box.dataset.url);
                    qr.make();
                    box.innerHTML = qr.createSvgTag(3, 2);
                })();
            </script>
        @endunless

        {{-- Criteria --}}
        <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($report['criteria'] as $criterion)
                <div class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-bold">{{ $criterion['label'] }}</p>
                        <p class="text-2xl font-extrabold">{{ number_format($criterion['band'], 1) }}</p>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
                        <div class="h-full rounded-full {{ $bandColor($criterion['band']) }}" style="width: {{ round($criterion['band'] / 9 * 100) }}%"></div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ $criterion['description'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Stats + focus --}}
        <section class="mt-6 grid gap-4 lg:grid-cols-[1fr_1.2fr]">
            <div class="grid grid-cols-2 gap-4">
                @foreach ([
                    [$isEn ? 'Average pace' : 'Rata-rata tempo', $stats['average_wpm'] ? $stats['average_wpm'].' '.($isEn ? 'wpm' : 'kata/mnt') : '–', $isEn ? 'Ideal 105–165' : 'Ideal 105–165'],
                    [$isEn ? 'Filler words' : 'Kata pengisi', (string) $stats['filler_count'], collect($stats['fillers'])->take(3)->map(fn ($c, $w) => "{$w} ×{$c}")->implode(', ') ?: ($isEn ? 'Clean!' : 'Bersih!')],
                    [$isEn ? 'Words spoken' : 'Kata diucapkan', (string) $stats['total_words'], ''],
                    [$isEn ? 'Eye contact' : 'Kontak mata', $stats['eye_contact'] !== null ? $stats['eye_contact'].'%' : '–', $stats['eye_contact'] === null ? ($isEn ? 'Camera was off' : 'Kamera tidak aktif') : ''],
                ] as [$label, $value, $hint])
                    <div class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-extrabold">{{ $value }}</p>
                        @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
                    </div>
                @endforeach
            </div>
            <div class="rounded-3xl bg-slate-900 p-6 text-white dark:bg-white dark:text-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider opacity-60">{{ $isEn ? 'Focus for next practice' : 'Fokus latihan berikutnya' }}</p>
                <div class="mt-4 space-y-5">
                    @foreach ($report['focus'] as $index => $focus)
                        <div class="flex gap-4">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-bold dark:bg-black/10">{{ $index + 1 }}</span>
                            <div>
                                <p class="font-bold">{{ $focus['title'] }}</p>
                                <p class="mt-1 text-sm opacity-80">{{ $focus['drill'] }}</p>
                                @unless ($shared)
                                    <a href="{{ $drill($focus['criterion']) }}" class="mt-2 inline-block text-sm font-semibold underline">{{ $isEn ? 'Practise this now →' : 'Latih ini sekarang →' }}</a>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('exam.setup', ['mode' => $modeKey] + ($isEn ? ['lang' => 'en'] : [])) }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-bold text-slate-900 hover:bg-slate-100 dark:bg-slate-900 dark:text-white">{{ $isEn ? 'Practise again' : 'Latihan lagi' }}</a>
                    @if (! $shared && \App\Http\Controllers\Web\BoothController::isUnlocked(request()))
                        <form method="POST" action="{{ route('booth.next') }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-emerald-400 px-5 py-2.5 text-sm font-bold text-slate-900 hover:bg-emerald-300">{{ $isEn ? 'Next visitor' : 'Pengunjung berikutnya' }}</button>
                        </form>
                    @endif
                    <a href="{{ route('exam.index', $isEn ? ['lang' => 'en'] : []) }}" class="rounded-full border border-white/20 px-5 py-2.5 text-sm font-bold hover:bg-white/10 dark:border-black/15 dark:hover:bg-black/5">{{ $isEn ? 'Other modes' : 'Mode lain' }}</a>
                </div>
            </div>
        </section>

        {{-- Per answer --}}
        <section class="mt-10">
            <h2 class="text-xl font-extrabold tracking-tight">{{ $isEn ? 'Answer by answer' : 'Masukan per jawaban' }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($report['answers'] as $item)
                    <details class="group rounded-3xl border border-black/5 bg-white open:shadow-sm dark:border-white/10 dark:bg-white/[0.03]" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-start gap-4 p-5">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-extrabold text-white {{ $bandColor($item['band']) }}">{{ number_format($item['band'], 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-500">{{ $isEn ? 'Part' : 'Bagian' }} {{ $item['part'] }}{{ $item['kind'] === 'long_turn' ? ($isEn ? ' · Long turn' : ' · Cue card') : '' }}</p>
                                <p class="mt-0.5 font-semibold">{{ $item['question'] }}</p>
                            </div>
                            <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-4 px-5 pb-5 sm:pl-[4.75rem]">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $isEn ? 'Your answer' : 'Jawabanmu' }}</p>
                                @if ($canManage && ! empty($item['has_recording']) && ! empty($item['message_id']))
                                    <audio controls preload="none" class="mt-2 h-9 w-full max-w-md" src="{{ route('exam.recording', [$session, $item['message_id']]) }}">
                                        {{ $isEn ? 'Your browser cannot play this recording.' : 'Browser tidak bisa memutar rekaman ini.' }}
                                    </audio>
                                @endif
                                <p class="mt-1 leading-relaxed">{!! $item['answer'] !== '' ? $highlight($item['answer']) : '<span class="text-slate-400">'.($isEn ? '(no answer)' : '(tidak menjawab)').'</span>' !!}</p>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $item['metrics']['word_count'] }} {{ $isEn ? 'words' : 'kata' }}
                                    @if ($item['metrics']['wpm']) · {{ $item['metrics']['wpm'] }} {{ $isEn ? 'wpm' : 'kata/mnt' }} @endif
                                    @if ($item['metrics']['duration_seconds']) · {{ $item['metrics']['duration_seconds'] }}{{ $isEn ? 's' : ' dtk' }} @endif
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($item['scores'] as $key => $score)
                                    <div class="rounded-xl bg-[#f6f6f3] px-3 py-2 dark:bg-white/5">
                                        <p class="text-[11px] text-slate-500">{{ $report['criteria'][$key]['label'] }}</p>
                                        <p class="font-bold">{{ number_format($score, 1) }}</p>
                                    </div>
                                @endforeach
                            </div>

                            @if (! empty($item['notes']))
                                <ul class="space-y-1.5 text-sm">
                                    @foreach ($item['notes'] as $note)
                                        <li class="flex gap-2">
                                            <span aria-hidden="true">{{ ['good' => '✅', 'warn' => '⚠️', 'tip' => '💡'][$note['type']] ?? '•' }}</span>
                                            <span>{{ $note['text'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($item['improved'])
                                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-50 p-4 dark:bg-emerald-500/10">
                                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">
                                        {{ $item['improved_source'] === 'ai' ? ($isEn ? 'A stronger version' : 'Versi jawaban yang lebih kuat') : ($isEn ? 'Suggested answer framework' : 'Kerangka jawaban yang disarankan') }}
                                    </p>
                                    <p class="mt-1.5 leading-relaxed text-emerald-950 dark:text-emerald-100">{{ $item['improved'] }}</p>
                                </div>
                            @endif
                        </div>
                    </details>
                @empty
                    <p class="rounded-3xl border border-dashed border-black/10 p-8 text-center text-slate-500 dark:border-white/15">{{ $isEn ? 'No answers were recorded in this session.' : 'Belum ada jawaban yang terekam di sesi ini.' }}</p>
                @endforelse
            </div>
        </section>

        {{-- Transcript --}}
        <details class="mt-10 rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
            <summary class="cursor-pointer font-bold">{{ $isEn ? 'Full transcript' : 'Transkrip lengkap' }}</summary>
            @if ($canRate)
                <p class="mt-2 text-xs text-slate-500">{{ $isEn ? 'Rate the examiner replies: 👍 natural and on-topic, 👎 off. Good replies are used to train the local AI.' : 'Nilai balasan penguji: 👍 nyambung & natural, 👎 kurang pas. Balasan yang bagus dipakai untuk melatih AI lokal.' }}</p>
            @endif
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($transcript as $line)
                    @php $rateable = $canRate && $line->sender === 'ai' && in_array($line->meta['kind'] ?? null, ['react', 'follow_up', 'next', 'clarify'], true); @endphp
                    <div class="flex gap-3" @if ($rateable) x-data="{ rating: @js($line->meta['rating'] ?? null), async rate(value) { this.rating = value; await fetch(@js(route('exam.rate', [$session, $line->id])), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ rating: value }) }) } }" @endif>
                        <span class="w-24 shrink-0 font-semibold {{ $line->sender === 'user' ? '' : 'text-slate-500' }}">{{ $line->sender === 'user' ? ($isEn ? 'You' : 'Kamu') : $mode['examiner']['name'] }}</span>
                        <p class="flex-1 leading-relaxed">{{ $line->message }}</p>
                        @if ($rateable)
                            <div class="flex shrink-0 gap-1">
                                <button type="button" @click="rate('good')" :class="rating === 'good' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Good reply' : 'Balasan bagus' }}">👍</button>
                                <button type="button" @click="rate('bad')" :class="rating === 'bad' ? 'bg-rose-100 dark:bg-rose-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Bad reply' : 'Balasan kurang pas' }}">👎</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    </div>
</x-exam-layout>
