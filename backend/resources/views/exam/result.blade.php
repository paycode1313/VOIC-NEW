@php
    $isEn = $language === 'en';
    $accent = config('exam.accents')[$mode['accent']] ?? config('exam.accents.indigo');
    $band = (float) $report['overall_band'];
    $stats = $report['stats'];
    // One pass over listed fillers (longest first) and drawn-out hesitations ("eee", "hmmm"), like the analyzer counts them.
    $fillerWords = collect((array) config('exam.fillers', []))->sortByDesc(fn ($w) => mb_strlen($w))->map(fn ($w) => preg_quote(e($w), '/'));
    $fillerPattern = '/(?<![\p{L}\p{N}])('.$fillerWords->push(\App\Services\Exam\SpeakingAnalyzer::HESITATION)->implode('|').')(?![\p{L}\p{N}])/iu';

    $highlight = fn (string $text): string => preg_replace($fillerPattern, '<mark class="rounded bg-amber-200/70 px-0.5 text-inherit dark:bg-amber-400/30">$1</mark>', e($text)) ?? e($text);

    $bandColor = fn (float $value) => $value >= 7 ? 'bg-emerald-500' : ($value >= 5.5 ? 'bg-amber-500' : 'bg-rose-500');
    $minutes = intdiv((int) $session->duration_seconds, 60);
    $seconds = (int) $session->duration_seconds % 60;
    $glow = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'][$mode['accent']] ?? '99 102 241';
@endphp

<x-exam-layout :title="$isEn ? 'Report' : 'Rapor'" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">

        {{-- Hero: the end screen of the call --}}
        <section class="voic-rise relative isolate overflow-hidden rounded-[2rem] bg-white p-6 text-slate-900 shadow-sm sm:p-8" style="--glow: {{ $glow }}">
            <div class="pointer-events-none absolute inset-0 -z-10 opacity-50 [background-image:repeating-linear-gradient(-45deg,rgb(var(--glow)/0.07)_0_2px,transparent_2px_16px)] [mask-image:linear-gradient(to_left,black,transparent_60%)]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(50%_90%_at_15%_50%,rgb(var(--glow)/0.45),transparent_70%)]"></div>
            <div @class(["grid gap-8 md:grid-cols-[auto_1fr] md:items-center", "lg:grid-cols-[auto_1fr_auto]" => ! $shared])>
                <div class="flex items-center justify-center gap-4">
                    <div class="relative flex h-36 w-36 items-center justify-center">
                        <svg class="absolute inset-0 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="9" class="text-slate-200"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="9" stroke-linecap="round" class="voic-ring-draw" style="stroke: rgb(var(--glow)); --full: {{ round(2 * M_PI * 52, 2) }}"
                                    stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - $band / 9), 2) }}"/>
                        </svg>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="absolute h-[6.5rem] w-[6.5rem] rounded-full object-cover opacity-25">
                        <div class="relative text-center">
                            <p class="font-display text-5xl font-extrabold">{{ number_format($band, 1) }}</p>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Band</p>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-900/[0.06] px-3 py-1">
                            <svg class="h-3.5 w-3.5 text-rose-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08a.956.956 0 010-1.36C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.72c.18.18.29.43.29.71 0 .28-.11.53-.29.71l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28a11.27 11.27 0 00-2.67-1.85.996.996 0 01-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/></svg>
                            {{ $isEn ? 'Call ended' : 'Panggilan selesai' }} · {{ $minutes }}:{{ str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="rounded-full bg-slate-900/[0.06] px-3 py-1">{{ $mode['examiner']['name'] }}</span>
                        <span class="rounded-full bg-slate-900/[0.06] px-3 py-1 text-slate-600">{{ $session->scenario_type }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-extrabold sm:text-5xl">{{ $report['level'] }}</h1>
                    <p class="mt-2 max-w-2xl text-slate-600">{{ $session->ai_conclusion }}</p>
                    <p class="mt-3 text-sm text-slate-600">{{ $session->created_at->translatedFormat('d M Y, H:i') }} · {{ $stats['answers'] }} {{ $isEn ? 'answers' : 'jawaban' }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-2.5" x-data="{ copied: false }">
                        <a href="{{ route('exam.setup', ['mode' => $modeKey] + ($isEn ? ['lang' => 'en'] : [])) }}" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:brightness-110" style="background: rgb(var(--glow))">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                            {{ $isEn ? 'Call again' : 'Telepon lagi' }}
                        </a>
                        <a href="{{ route('exam.index', $isEn ? ['lang' => 'en'] : []) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">{{ $isEn ? 'Other examiners' : 'Penguji lain' }}</a>
                        @unless ($shared)
                            <button type="button"
                                    @click="navigator.clipboard?.writeText(@js($shareUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <svg x-show="!copied" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                <svg x-show="copied" x-cloak class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                <span x-text="copied ? '{{ $isEn ? 'Link copied!' : 'Tautan disalin!' }}' : '{{ $isEn ? 'Copy report link' : 'Salin link rapor' }}'"></span>
                            </button>
                        @endunless
                        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 print:hidden">
                            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
                            <span>{{ $isEn ? 'Print / PDF' : 'Cetak PDF' }}</span>
                        </button>
                    </div>
                </div>
                @unless ($shared)
                    {{-- Take it home: QR to a signed, read-only copy of this report --}}
                    <div class="flex flex-col items-center gap-3 rounded-2xl bg-slate-900/[0.06] p-4 text-center ring-1 ring-slate-900/10 md:col-span-2 md:flex-row md:text-left lg:col-span-1 lg:flex-col lg:text-center">
                        <div id="report-qr" data-url="{{ $shareUrl }}" class="h-40 w-40 shrink-0 rounded-2xl bg-white p-2 [&_svg]:h-full [&_svg]:w-full" aria-label="{{ $isEn ? 'QR code to this report' : 'QR code rapor ini' }}"></div>
                        <div>
                            <p class="flex items-center justify-center gap-1.5 font-extrabold md:justify-start lg:justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                                {{ $isEn ? 'Take your report home' : 'Bawa pulang rapormu' }}
                            </p>
                            <p class="mt-1 max-w-[12rem] text-xs text-slate-600 md:max-w-none lg:max-w-[12rem]">{{ $isEn ? 'Scan with your phone camera. Works for 7 days, no account.' : 'Pindai pakai kamera HP. Berlaku 7 hari, tanpa akun.' }}</p>
                        </div>
                    </div>
                @endunless
            </div>
        </section>

        @unless ($shared)
            @if (str_contains($shareUrl, '://localhost') || str_contains($shareUrl, '://127.0.0.1') || $canManage)
            <section class="mt-4 flex flex-col items-center gap-2 rounded-3xl border border-black/5 bg-white px-5 py-3 text-center dark:border-white/10 dark:bg-white/[0.03] sm:flex-row sm:justify-between sm:text-left">
                <div>
                    @if (str_contains($shareUrl, '://localhost') || str_contains($shareUrl, '://127.0.0.1'))
                        <p class="rounded-lg bg-amber-50 px-2 py-1 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">
                            {{ $isEn ? 'Operator: set VOIC_PUBLIC_URL (LAN IP or tunnel) so phones can open this.' : 'Operator: isi VOIC_PUBLIC_URL di .env (IP LAN atau tunnel) agar QR bisa dibuka di HP.' }}
                        </p>
                    @endif
                    @if ($canManage)
                        <form method="POST" action="{{ route('exam.destroy', $session) }}" class="mt-1"
                              onsubmit="return confirm('{{ $isEn ? 'Delete this session, transcript and recordings permanently?' : 'Hapus sesi, transkrip, dan rekaman ini secara permanen?' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">{{ $isEn ? 'Delete my data' : 'Hapus data saya' }}</button>
                        </form>
                    @endif
                </div>
            </section>
            @endif
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

        @if ($study)
            @include('exam.partials.study')
        @endif

        {{-- Criteria --}}
        <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($report['criteria'] as $criterion)
                <div class="voic-rise rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]" style="--d: {{ 120 + $loop->index * 80 }}ms">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-bold">{{ $criterion['label'] }}</p>
                        <p class="font-display text-3xl font-extrabold">{{ number_format($criterion['band'], 1) }}</p>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
                        <div class="voic-grow h-full rounded-full {{ $bandColor($criterion['band']) }}" style="--d: {{ 400 + $loop->index * 80 }}ms; width: {{ round($criterion['band'] / 9 * 100) }}%"></div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ $criterion['description'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Stats + focus --}}
        <section class="voic-rise mt-6 grid gap-4 lg:grid-cols-[1fr_1.2fr]" style="--d: 450ms">
            <div class="grid grid-cols-2 gap-4">
                @foreach ([
                    [$isEn ? 'Average pace' : 'Rata-rata tempo', $stats['average_wpm'] ? $stats['average_wpm'].' '.($isEn ? 'wpm' : 'kata/mnt') : '–', $isEn ? 'Ideal 105–165' : 'Ideal 105–165'],
                    [$isEn ? 'Filler words' : 'Kata pengisi', (string) $stats['filler_count'], collect($stats['fillers'])->take(3)->map(fn ($c, $w) => "{$w} ×{$c}")->implode(', ') ?: ($isEn ? 'Clean!' : 'Bersih!')],
                    [$isEn ? 'Words spoken' : 'Kata diucapkan', (string) $stats['total_words'], $isEn ? 'Total across session' : 'Total selama sesi'],
                    [$isEn ? 'Eye contact' : 'Kontak mata', $stats['eye_contact'] !== null ? $stats['eye_contact'].'%' : '–', $stats['eye_contact'] === null ? ($isEn ? 'Camera was off' : 'Kamera tidak aktif') : (isset($stats['composure']) ? ($isEn ? 'Relaxed face ' : 'Wajah tenang ').$stats['composure'].'%' : '')],
                ] as [$label, $value, $hint])
                    <div class="flex flex-col justify-between rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="font-display mt-1 text-2xl font-extrabold tabular-nums sm:text-[1.7rem]">{{ $value }}</p>
                        @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
                    </div>
                @endforeach
            </div>
            <div class="rounded-3xl bg-indigo-50 p-6 text-slate-900 ring-1 ring-indigo-100">
                <p class="text-xs font-bold uppercase tracking-wider opacity-60">{{ $isEn ? 'Focus for next practice' : 'Fokus latihan berikutnya' }}</p>
                <div class="mt-4 space-y-5">
                    @foreach ($report['focus'] as $index => $focus)
                        <div class="flex gap-4">
                            <span class="voic-sticker font-display h-8 w-8 shrink-0 justify-center bg-indigo-600 text-sm font-extrabold text-white" style="transform: rotate({{ $index % 2 ? 4 : -5 }}deg)">{{ $index + 1 }}</span>
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
                    <a href="{{ route('exam.setup', ['mode' => $modeKey] + ($isEn ? ['lang' => 'en'] : [])) }}" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">{{ $isEn ? 'Practise again' : 'Latihan lagi' }}</a>
                    @if (! $shared && \App\Http\Controllers\Web\BoothController::isUnlocked(request()))
                        <form method="POST" action="{{ route('booth.next') }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-emerald-400 px-5 py-2.5 text-sm font-bold text-slate-900 hover:bg-emerald-300">{{ $isEn ? 'Next visitor' : 'Pengunjung berikutnya' }}</button>
                        </form>
                    @endif
                    <a href="{{ route('exam.index', $isEn ? ['lang' => 'en'] : []) }}" class="rounded-full border border-indigo-200 bg-white/80 px-5 py-2.5 text-sm font-bold text-indigo-900 hover:bg-white">{{ $isEn ? 'Other modes' : 'Mode lain' }}</a>
                </div>
            </div>
        </section>

        {{-- Per answer --}}
        <section class="mt-10">
            <h2 class="text-3xl font-extrabold">{{ $isEn ? 'Answer by answer' : 'Masukan per jawaban' }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($report['answers'] as $item)
                    <details class="group rounded-3xl border border-black/5 bg-white open:shadow-sm dark:border-white/10 dark:bg-white/[0.03]" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-start gap-4 p-5">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-extrabold text-white {{ $bandColor($item['band']) }}">{{ number_format($item['band'], 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-500">{{ $isEn ? 'Part' : 'Bagian' }} {{ $item['part'] }}{{ $item['kind'] === 'long_turn' ? ($isEn ? ' · Long turn' : ' · Cue card') : '' }}</p>
                                <p class="mt-0.5 font-semibold">{{ $item['question'] }}</p>
                            </div>
                            <svg class="mt-1 h-5 w-5 shrink-0 text-slate-500 dark:text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="space-y-4 px-5 pb-5 sm:pl-[4.75rem]">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $isEn ? 'Your answer' : 'Jawabanmu' }}</p>
                                @if ($canManage && ! empty($item['has_recording']) && ! empty($item['message_id']))
                                    <audio controls preload="none" class="mt-2 h-9 w-full max-w-md" src="{{ route('exam.recording', [$session, $item['message_id']]) }}">
                                        {{ $isEn ? 'Your browser cannot play this recording.' : 'Browser tidak bisa memutar rekaman ini.' }}
                                    </audio>
                                @endif
                                <p class="mt-1 leading-relaxed">{!! $item['answer'] !== '' ? $highlight($item['answer']) : '<span class="text-slate-500 dark:text-slate-400">'.($isEn ? '(no answer)' : '(tidak menjawab)').'</span>' !!}</p>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $item['metrics']['word_count'] }} {{ $isEn ? 'words' : 'kata' }}
                                    @if ($item['metrics']['wpm']) · {{ $item['metrics']['wpm'] }} {{ $isEn ? 'wpm' : 'kata/mnt' }} @endif
                                    @if ($item['metrics']['duration_seconds']) · {{ $item['metrics']['duration_seconds'] }}{{ $isEn ? 's' : ' dtk' }} @endif
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($item['scores'] as $key => $score)
                                    <div class="rounded-xl bg-[#f6f6f3] px-3 py-2 dark:bg-white/5">
                                        <p class="text-xs text-slate-500">{{ $report['criteria'][$key]['label'] }}</p>
                                        <p class="font-bold">{{ number_format($score, 1) }}</p>
                                    </div>
                                @endforeach
                            </div>

                            @if (! empty($item['notes']))
                                <ul class="space-y-1.5 text-sm">
                                    @foreach ($item['notes'] as $note)
                                        <li class="flex gap-2">
                                            @php $noteColor = ['good' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'tip' => 'bg-sky-500'][$note['type']] ?? 'bg-slate-400'; @endphp
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $noteColor }}" aria-hidden="true"></span>
                                            <span class="sr-only">{{ ['good' => $isEn ? 'Strength:' : 'Kekuatan:', 'warn' => $isEn ? 'Watch out:' : 'Perhatikan:', 'tip' => $isEn ? 'Tip:' : 'Saran:'][$note['type']] ?? '' }}</span>
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
                <p class="mt-2 text-xs text-slate-500">{{ $isEn ? 'Rate the examiner replies: thumbs up if natural and on-topic, thumbs down if off. Good replies are used to train the local AI.' : 'Nilai balasan penguji: jempol ke atas kalau nyambung & natural, ke bawah kalau kurang pas. Balasan yang bagus dipakai untuk melatih AI lokal.' }}</p>
            @endif
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($transcript as $line)
                    @php $rateable = $canRate && $line->sender === 'ai' && in_array($line->meta['kind'] ?? null, ['react', 'follow_up', 'next', 'clarify'], true); @endphp
                    <div class="flex gap-3" @if ($rateable) x-data="{ rating: @js($line->meta['rating'] ?? null), async rate(value) { this.rating = value; await fetch(@js(route('exam.rate', [$session, $line->id])), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ rating: value }) }) } }" @endif>
                        <span class="w-24 shrink-0 font-semibold {{ $line->sender === 'user' ? '' : 'text-slate-500' }}">{{ $line->sender === 'user' ? ($isEn ? 'You' : 'Kamu') : $mode['examiner']['name'] }}</span>
                        <p class="flex-1 leading-relaxed">{{ $line->message }}</p>
                        @if ($rateable)
                            <div class="flex shrink-0 gap-1">
                                <button type="button" @click="rate('good')" :class="rating === 'good' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Good reply' : 'Balasan bagus' }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V2.75a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48a4.53 4.53 0 01-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M6.633 10.25H5.25a2.25 2.25 0 00-2.25 2.25v6a2.25 2.25 0 002.25 2.25h.654"/></svg></button>
                                <button type="button" @click="rate('bad')" :class="rating === 'bad' ? 'bg-rose-100 dark:bg-rose-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Bad reply' : 'Balasan kurang pas' }}"><svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V2.75a.75.75 0 01.75-.75 2.25 2.25 0 012.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48a4.53 4.53 0 01-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M6.633 10.25H5.25a2.25 2.25 0 00-2.25 2.25v6a2.25 2.25 0 002.25 2.25h.654"/></svg></button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    </div>
</x-exam-layout>
