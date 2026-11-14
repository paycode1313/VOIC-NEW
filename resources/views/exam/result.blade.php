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
    $glow = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'][$mode['accent']] ?? '99 102 241';
@endphp

<x-exam-layout :title="$isEn ? 'Report' : 'Rapor'" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">

        {{-- Hero: the end screen of the call --}}
        <section class="relative isolate overflow-hidden rounded-3xl bg-[#121521] p-6 text-white sm:p-8" style="--glow: {{ $glow }}">
            <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(50%_90%_at_15%_50%,rgb(var(--glow)/0.45),transparent_70%)]"></div>
            <div class="grid gap-8 md:grid-cols-[auto_1fr] md:items-center">
                <div class="flex items-center justify-center gap-4">
                    <div class="relative flex h-36 w-36 items-center justify-center">
                        <svg class="absolute inset-0 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="9" class="text-white/10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="9" stroke-linecap="round" style="stroke: rgb(var(--glow))"
                                    stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - $band / 9), 2) }}"/>
                        </svg>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="absolute h-[6.5rem] w-[6.5rem] rounded-full object-cover opacity-25">
                        <div class="relative text-center">
                            <p class="text-4xl font-extrabold tracking-tight">{{ number_format($band, 1) }}</p>
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/60">Band</p>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1">
                            <svg class="h-3.5 w-3.5 text-rose-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08a.956.956 0 010-1.36C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.72c.18.18.29.43.29.71 0 .28-.11.53-.29.71l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28a11.27 11.27 0 00-2.67-1.85.996.996 0 01-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/></svg>
                            {{ $isEn ? 'Call ended' : 'Panggilan selesai' }} · {{ $minutes }}:{{ str_pad((string) $seconds, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="rounded-full bg-white/10 px-3 py-1">{{ $mode['examiner']['name'] }}</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-white/70">{{ $session->scenario_type }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $report['level'] }}</h1>
                    <p class="mt-2 max-w-2xl text-white/70">{{ $session->ai_conclusion }}</p>
                    <p class="mt-3 text-sm text-white/65">{{ $session->created_at->translatedFormat('d M Y, H:i') }} · {{ $stats['answers'] }} {{ $isEn ? 'answers' : 'jawaban' }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('exam.setup', ['mode' => $modeKey] + ($isEn ? ['lang' => 'en'] : [])) }}" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white hover:brightness-110" style="background: rgb(var(--glow))">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                            {{ $isEn ? 'Call again' : 'Telepon lagi' }}
                        </a>
                        <a href="{{ route('exam.index', $isEn ? ['lang' => 'en'] : []) }}" class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-bold hover:bg-white/15">{{ $isEn ? 'Other examiners' : 'Penguji lain' }}</a>
                    </div>
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

        @if ($study)
            @include('exam.partials.study')
        @endif

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
                    [$isEn ? 'Eye contact' : 'Kontak mata', $stats['eye_contact'] !== null ? $stats['eye_contact'].'%' : '–', $stats['eye_contact'] === null ? ($isEn ? 'Camera was off' : 'Kamera tidak aktif') : (isset($stats['composure']) ? ($isEn ? 'Relaxed face ' : 'Wajah tenang ').$stats['composure'].'%' : '')],
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
