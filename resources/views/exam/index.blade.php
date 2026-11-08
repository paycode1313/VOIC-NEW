@php
    $isEn = $language === 'en';
    $accents = config('exam.accents');
@endphp

<x-exam-layout :title="$isEn ? 'Practice' : 'Latihan'">
    <div class="mx-auto max-w-6xl px-4 pb-20 pt-10 sm:px-6 sm:pt-16">
        <section class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                {{ $isEn ? 'AI examiner that actually listens' : 'Penguji AI yang benar-benar mendengarkan' }}
            </span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-tight sm:text-5xl">
                {{ $isEn ? 'Practise speaking like it is the real thing.' : 'Latihan bicara, rasa ujian sungguhan.' }}
            </h1>
            <p class="mt-4 text-base text-slate-600 dark:text-slate-400 sm:text-lg">
                {{ $isEn
                    ? 'A three-part mock session with a voice examiner, then a band-score report with feedback on every answer.'
                    : 'Simulasi tiga bagian dengan penguji bersuara, lalu rapor band score dan masukan untuk setiap jawabanmu.' }}
            </p>
            <div class="mt-4 inline-flex rounded-full bg-black/5 p-1 text-xs font-semibold dark:bg-white/10">
                <a href="{{ route('exam.index') }}" class="rounded-full px-3 py-1 {{ $isEn ? 'text-slate-500' : 'bg-white text-slate-900 shadow-sm dark:bg-white/15 dark:text-white' }}">Indonesia</a>
                <a href="{{ route('exam.index', ['lang' => 'en']) }}" class="rounded-full px-3 py-1 {{ $isEn ? 'bg-white text-slate-900 shadow-sm dark:bg-white/15 dark:text-white' : 'text-slate-500' }}">English</a>
            </div>
        </section>

        <section class="mt-12 grid gap-4 md:grid-cols-3" aria-label="{{ $isEn ? 'Practice modes' : 'Mode latihan' }}">
            @foreach ($modes as $key => $mode)
                @php $accent = $accents[$mode['accent']] ?? $accents['indigo']; @endphp
                <a href="{{ route('exam.setup', ['mode' => $key] + ($isEn ? ['lang' => 'en'] : [])) }}"
                   class="group relative flex flex-col rounded-3xl border border-black/5 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-white/[0.03] {{ $accent['border'] }}">
                    <div class="flex items-start justify-between">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $accent['soft'] }}">
                            @include('exam.partials.icon', ['name' => $mode['icon']])
                        </span>
                        <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-12 w-12 rounded-full object-cover ring-4 ring-white dark:ring-[#16171b]" loading="lazy">
                    </div>
                    <h2 class="mt-5 text-xl font-bold tracking-tight">{{ $blueprint->text($mode['title'], $language) }}</h2>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $blueprint->text($mode['tagline'], $language) }}</p>

                    <ol class="mt-5 space-y-2 border-t border-black/5 pt-4 text-sm dark:border-white/10">
                        @foreach ($mode['parts'] as $number => $part)
                            <li class="flex items-center gap-3">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $number }}</span>
                                <span class="font-medium">{{ $blueprint->text($part['title'], $language) }}</span>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-6 flex items-center justify-between text-sm">
                        <span class="text-slate-500">{{ $mode['examiner']['name'] }} · ~12 {{ $isEn ? 'min' : 'menit' }}</span>
                        <span class="inline-flex items-center gap-1 font-semibold {{ $accent['text'] }}">
                            {{ $isEn ? 'Start' : 'Mulai' }}
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </section>

        <section class="mt-16 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['1', $isEn ? 'Pick a mode and topic' : 'Pilih mode & topik', $isEn ? 'Add your role, thesis title, or startup so questions fit you.' : 'Isi posisi, judul skripsi, atau startup-mu agar pertanyaannya relevan.'],
                ['2', $isEn ? 'Talk to the examiner' : 'Ngobrol dengan penguji', $isEn ? 'Answer out loud. The examiner reacts to what you said and digs deeper.' : 'Jawab dengan suara. Penguji menanggapi isi jawabanmu dan menggali lebih dalam.'],
                ['3', $isEn ? 'Get your report' : 'Terima rapor', $isEn ? 'Band score on four criteria, notes per answer, and a better version.' : 'Band score di empat kriteria, catatan per jawaban, dan versi jawaban yang lebih baik.'],
            ] as [$step, $heading, $body])
                <div class="rounded-2xl p-2">
                    <span class="text-sm font-bold text-slate-400">0{{ $step }}</span>
                    <h3 class="mt-1 font-bold">{{ $heading }}</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $body }}</p>
                </div>
            @endforeach
        </section>

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
                        <a href="{{ $recentReport ? route('exam.result', $recent) : route('exam.room', $recent) }}" class="rounded-2xl border border-black/5 bg-white p-4 text-sm hover:shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ \Illuminate\Support\Str::limit($recent->scenario_type, 28) }}</span>
                                @if ($recentReport)
                                    <span class="rounded-full bg-slate-900 px-2 py-0.5 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ number_format($recentReport['overall_band'], 1) }}</span>
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
