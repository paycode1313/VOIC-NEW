@php
    $isEn = $language === 'en';
    $accent = config('exam.accents')[$mode['accent']] ?? config('exam.accents.indigo');
    $stats = $report['stats'];
    $minutes = intdiv((int) $session->duration_seconds, 60);
    $seconds = (int) $session->duration_seconds % 60;
    $fillerWords = (array) config('exam.fillers', []);
    $coachName = $mode['examiner']['name'];
@endphp

<x-exam-layout :title="$isEn ? 'Coaching notes' : 'Catatan coaching'" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto max-w-4xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">

        <section class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03] sm:p-8">
            <div class="flex items-start gap-4">
                <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-14 w-14 rounded-full object-cover">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $accent['soft'] }}">
                        @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-4 w-4'])
                        {{ $report['coach_title'] }} · {{ $coachName }}
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight">{{ $isEn ? 'Your coaching notes' : 'Catatan coaching kamu' }}</h1>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">{{ $isEn ? 'No score, just what to keep and what to practise next.' : 'Tanpa nilai. Ini yang sudah bagus dan yang perlu kamu latih berikutnya.' }}</p>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    [$isEn ? 'Duration' : 'Durasi', $minutes.':'.str_pad((string) $seconds, 2, '0', STR_PAD_LEFT)],
                    [$isEn ? 'Your turns' : 'Giliran bicara', $stats['turns']],
                    [$isEn ? 'Pace' : 'Tempo', $stats['average_wpm'] ? $stats['average_wpm'].($isEn ? ' wpm' : ' kata/mnt') : '–'],
                    [$isEn ? 'Filler words' : 'Kata pengisi', $stats['filler_count']],
                ] as [$label, $value])
                    <div class="rounded-2xl bg-[#f6f6f3] px-4 py-3 dark:bg-white/5">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-0.5 text-xl font-extrabold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 class="font-bold">✅ {{ $isEn ? 'Already good' : 'Sudah bagus' }}</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    @foreach ($report['strengths'] as $item)
                        <li class="flex gap-2"><span class="text-slate-400">•</span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-3xl bg-slate-900 p-6 text-white dark:bg-white dark:text-slate-900">
                <h2 class="font-bold">🎯 {{ $isEn ? 'Practise next' : 'Latih berikutnya' }}</h2>
                <ul class="mt-3 space-y-2 text-sm opacity-90">
                    @foreach ($report['practice'] as $item)
                        <li class="flex gap-2"><span class="opacity-60">•</span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
        </section>

        @if (! empty($report['phrases']))
            <section class="mt-4 rounded-3xl border border-emerald-500/20 bg-emerald-50 p-6 dark:bg-emerald-500/10">
                <h2 class="font-bold text-emerald-900 dark:text-emerald-100">💬 {{ $isEn ? 'Sentences you can reuse' : 'Kalimat yang bisa kamu pakai' }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($report['phrases'] as $phrase)
                        <li class="rounded-2xl bg-white/70 px-4 py-3 text-[15px] leading-relaxed text-emerald-950 dark:bg-black/20 dark:text-emerald-100">“{{ $phrase }}”</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @unless ($shared)
            <section class="mt-4 flex flex-col items-center gap-5 rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:flex-row">
                <div id="report-qr" data-url="{{ $shareUrl }}" class="h-28 w-28 shrink-0 rounded-xl bg-white p-1.5"></div>
                <div class="flex-1 text-center sm:text-left">
                    <p class="font-bold">{{ $isEn ? 'Take these notes home' : 'Bawa pulang catatan ini' }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $isEn ? 'Scan with your phone. Valid for 7 days.' : 'Pindai dengan kamera HP. Berlaku 7 hari.' }}</p>
                    <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                        <a href="{{ route('exam.setup', ['mode' => $modeKey] + ($isEn ? ['lang' => 'en'] : [])) }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-900">{{ $isEn ? 'Chat again' : 'Ngobrol lagi' }}</a>
                        <a href="{{ route('exam.setup', ['mode' => $modeKey, 'type' => 'exam'] + ($isEn ? ['lang' => 'en'] : [])) }}" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">{{ $isEn ? 'Try the mock exam' : 'Coba simulasi ujian' }}</a>
                        @if (\App\Http\Controllers\Web\BoothController::isUnlocked(request()))
                            <form method="POST" action="{{ route('booth.next') }}">@csrf<button class="rounded-full bg-emerald-400 px-4 py-2 text-sm font-bold text-slate-900">{{ $isEn ? 'Next visitor' : 'Pengunjung berikutnya' }}</button></form>
                        @endif
                    </div>
                    @if ($canManage)
                        <form method="POST" action="{{ route('exam.destroy', $session) }}" class="mt-3" onsubmit="return confirm('{{ $isEn ? 'Delete this chat and recordings permanently?' : 'Hapus obrolan dan rekaman ini secara permanen?' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">{{ $isEn ? 'Delete my data' : 'Hapus data saya' }}</button>
                        </form>
                    @endif
                </div>
            </section>
            @include('booth.partials.qr', ['target' => 'report-qr'])
        @endunless

        {{-- Full conversation --}}
        <section class="mt-6 rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:p-6">
            <h2 class="font-bold">{{ $isEn ? 'The conversation' : 'Isi obrolan' }}</h2>
            @if ($canRate)
                <p class="mt-1 text-xs text-slate-500">{{ $isEn ? 'Rate the coach: 👍 helpful and natural, 👎 off. Good replies train the local AI.' : 'Nilai balasan coach: 👍 membantu & natural, 👎 kurang pas. Balasan yang bagus dipakai melatih AI lokal.' }}</p>
            @endif
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($transcript as $line)
                    @php $rateable = $canRate && $line->sender === 'ai' && in_array($line->meta['kind'] ?? null, ['coach', 'coach_close'], true); @endphp
                    <div class="flex gap-3 {{ $line->sender === 'user' ? 'flex-row-reverse text-right' : '' }}"
                         @if ($rateable) x-data="{ rating: @js($line->meta['rating'] ?? null), async rate(value) { this.rating = value; await fetch(@js(route('exam.rate', [$session, $line->id])), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ rating: value }) }) } }" @endif>
                        <div class="max-w-[85%]">
                            <p class="mb-0.5 text-[11px] font-semibold text-slate-400">{{ $line->sender === 'user' ? ($isEn ? 'You' : 'Kamu') : $coachName }}</p>
                            <p class="inline-block rounded-2xl px-3 py-2 text-left leading-relaxed {{ $line->sender === 'user' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-[#f6f6f3] dark:bg-white/5' }}">{{ $line->message }}</p>
                            @if ($canManage && $line->sender === 'user' && ! empty($line->meta['recording']))
                                <audio controls preload="none" class="mt-1 h-8 w-full max-w-xs" src="{{ route('exam.recording', [$session, $line->id]) }}"></audio>
                            @endif
                            @if ($rateable)
                                <div class="mt-1 flex gap-1">
                                    <button type="button" @click="rate('good')" :class="rating === 'good' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Helpful' : 'Membantu' }}">👍</button>
                                    <button type="button" @click="rate('bad')" :class="rating === 'bad' ? 'bg-rose-100 dark:bg-rose-500/20' : 'opacity-40 hover:opacity-100'" class="rounded-full px-1.5 py-0.5" aria-label="{{ $isEn ? 'Not helpful' : 'Kurang pas' }}">👎</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-exam-layout>
