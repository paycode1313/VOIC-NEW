@php
    $isEn = $language === 'en';
    $accents = config('exam.accents');
@endphp

<x-exam-layout :title="$isEn ? 'Practice' : 'Latihan'">
    <div class="mx-auto max-w-6xl px-4 pb-10 pt-8 sm:px-6 sm:pt-16">
        <section class="mx-auto max-w-2xl text-center">
            <span class="voic-rise inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                <span class="relative flex h-2 w-2"><span class="absolute inset-0 animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative h-2 w-2 rounded-full bg-emerald-500"></span></span>
                {{ $isEn ? 'Three AI examiners, online now' : 'Tiga penguji AI, lagi online' }}
            </span>
            <h1 class="voic-rise mt-5 text-[2.1rem] font-extrabold leading-[1.05] sm:text-6xl" style="--d: 80ms">
                @if ($isEn)
                    Who do you want to <span class="voic-mark" style="--mark: #fde68a">practise</span> with?
                @else
                    Mau latihan sama <span class="voic-mark" style="--mark: #fde68a">siapa</span> hari ini?
                @endif
            </h1>
            <p class="voic-rise mt-4 text-base text-slate-600 dark:text-slate-400 sm:text-lg" style="--d: 160ms">
                {{ $isEn
                    ? 'Pick an examiner and talk face to face, like a video call. Afterwards you get a report with feedback on every answer.'
                    : 'Pilih penguji, lalu ngobrol tatap muka seperti video call. Setelah selesai, kamu dapat rapor dan masukan untuk tiap jawaban.' }}
            </p>
            <div class="voic-rise mt-4 inline-flex rounded-full bg-black/5 p-1 text-xs font-semibold dark:bg-white/10" style="--d: 220ms">
                <a href="{{ route('exam.index') }}" class="rounded-full px-3 py-1 {{ $isEn ? 'text-slate-500' : 'bg-white text-slate-900 shadow-sm dark:bg-white/15 dark:text-white' }}">Indonesia</a>
                <a href="{{ route('exam.index', ['lang' => 'en']) }}" class="rounded-full px-3 py-1 {{ $isEn ? 'bg-white text-slate-900 shadow-sm dark:bg-white/15 dark:text-white' : 'text-slate-500' }}">English</a>
            </div>
        </section>

        {{-- Each examiner is a call you can join, like picking a contact in a video-call app. --}}
        <section class="mt-10 grid gap-4 sm:mt-12 sm:gap-6 md:grid-cols-3" aria-label="{{ $isEn ? 'Practice modes' : 'Mode latihan' }}">
            @foreach ($modes as $key => $mode)
                @php
                    $accent = $accents[$mode['accent']] ?? $accents['indigo'];
                    $glow = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'][$mode['accent']] ?? '99 102 241';
                    $traits = $mode['examiner']['traits'][$language] ?? $mode['examiner']['traits']['id'] ?? [];
                @endphp
                <a href="{{ route('exam.setup', ['mode' => $key] + ($isEn ? ['lang' => 'en'] : [])) }}" style="--glow: {{ $glow }}; --d: {{ 260 + $loop->index * 110 }}ms"
                   class="voic-rise voic-tilt group flex flex-col overflow-hidden rounded-[1.75rem] border border-black/5 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                    {{-- The examiner's "stage": their colour, a soft spotlight and their catchphrase. --}}
                    <div class="relative isolate flex aspect-[4/3] flex-col items-center justify-center overflow-hidden" style="background: linear-gradient(160deg, rgb(var(--glow) / 0.16), rgb(var(--glow) / 0.04) 55%, #fff)">
                        <div class="pointer-events-none absolute inset-0 -z-10 opacity-60 [background-image:repeating-linear-gradient(-45deg,rgb(var(--glow)/0.09)_0_2px,transparent_2px_14px)] [mask-image:radial-gradient(70%_70%_at_50%_40%,transparent_35%,black)]"></div>
                        <div class="pointer-events-none absolute inset-0 -z-10 opacity-80 transition duration-500 group-hover:opacity-100 [background:radial-gradient(45%_55%_at_50%_42%,rgb(var(--glow)/0.45),transparent_72%)]"></div>
                        <div class="relative">
                            <span class="voic-ring absolute inset-0 rounded-full border-2 opacity-0 transition group-hover:opacity-100" style="border-color: rgb(var(--glow) / 0.6)"></span>
                            <img src="{{ asset($mode['examiner']['avatar']) }}" alt="{{ $mode['examiner']['name'] }}" loading="lazy"
                                 class="relative h-28 w-28 rounded-full object-cover shadow-2xl ring-4 ring-white transition duration-500 group-hover:-rotate-3 group-hover:scale-105 sm:h-32 sm:w-32">
                            <span class="absolute -bottom-1 -right-1 inline-flex h-8 w-8 items-center justify-center rounded-full text-white ring-4 ring-white" style="background: rgb(var(--glow))">
                                <span class="voic-wave text-[13px]" aria-hidden="true"><i></i><i></i><i></i></span>
                            </span>
                        </div>
                        <p class="voic-bubble mt-4 max-w-[85%] px-3.5 py-2 text-center text-[13px] font-semibold leading-snug text-slate-800 transition duration-500 group-hover:-translate-y-0.5">
                            “{{ $blueprint->text($mode['examiner']['catchphrase'] ?? '', $language) }}”
                        </p>
                        <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-slate-900 backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Online
                        </span>
                        <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-slate-900 backdrop-blur">
                            @include('exam.partials.icon', ['name' => $mode['icon']])
                            {{ $blueprint->text($mode['title'], $language) }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-2xl font-extrabold">{{ $mode['examiner']['name'] }}</h2>
                                <p class="mt-0.5 text-sm font-semibold" style="color: rgb(var(--glow))">{{ $blueprint->text($mode['examiner']['title'], $language) }}</p>
                            </div>
                            <span class="mt-1 shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">~12 {{ $isEn ? 'min' : 'menit' }}</span>
                        </div>
                        @if ($traits !== [])
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($traits as $trait)
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" style="background: rgb(var(--glow) / 0.12); color: rgb(var(--glow))">{{ $trait }}</span>
                                @endforeach
                            </div>
                        @endif
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $blueprint->text($mode['tagline'], $language) }}</p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($mode['parts'] as $number => $part)
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $number }}. {{ $blueprint->text($part['title'], $language) }}</span>
                            @endforeach
                        </div>

                        <span class="mt-5 inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgb(var(--glow))] transition group-hover:brightness-110" style="background: rgb(var(--glow))">
                            <svg class="h-4 w-4 transition group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                            {{ $isEn ? 'Start the call' : 'Mulai panggilan' }}
                            <svg class="h-4 w-4 -translate-x-1 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </section>

        @include('exam.partials.steps', ['isEn' => $isEn, 'steps' => [
            [$isEn ? 'Pick a mode and topic' : 'Pilih mode & topik', $isEn ? 'Add your role, thesis title, or startup so questions fit you.' : 'Isi posisi, judul skripsi, atau startup-mu agar pertanyaannya relevan.', '#fde68a'],
            [$isEn ? 'Talk to the examiner' : 'Ngobrol dengan penguji', $isEn ? 'Answer out loud. The examiner reacts to what you said and digs deeper.' : 'Jawab dengan suara. Penguji menanggapi isi jawabanmu dan menggali lebih dalam.', '#c7d2fe'],
            [$isEn ? 'Get your report' : 'Terima rapor', $isEn ? 'Band score on four criteria, notes per answer, and a better version.' : 'Band score di empat kriteria, catatan per jawaban, dan versi jawaban yang lebih baik.', '#ddd6fe'],
        ]])

        @if ($progress)
            <section class="mt-14">
                @include('exam.partials.progress', ['progress' => $progress, 'drill' => fn ($criterion) => app(\App\Services\Exam\ExamProgressService::class)->drillUrl($lastMode, $criterion)])
            </section>
        @endif

        @if ($recentSessions->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">{{ $isEn ? 'Recent sessions' : 'Sesi terakhir' }}</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($recentSessions as $recent)
                        @php $recentReport = $recent->feedback_notes['exam_report'] ?? null; @endphp
                        <a href="{{ $recent->reportUrl() }}" class="rounded-2xl border border-black/5 bg-white p-4 text-sm hover:shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ \Illuminate\Support\Str::limit($recent->scenario_type, 28) }}</span>
                                @if ($recentReport)
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ number_format($recentReport['overall_band'], 1) }}</span>
                                @elseif ($recent->hasReport())
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300">{{ $isEn ? 'Coach' : 'Coaching' }}</span>
                                @else
                                    <span class="text-xs text-amber-600">{{ $isEn ? 'In progress' : 'Belum selesai' }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $recent->created_at->diffForHumans() }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-exam-layout>
