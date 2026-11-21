@php
    $accents = config('exam.accents');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
    $hours = intdiv($totalDurationSeconds, 3600);
    $minutes = intdiv($totalDurationSeconds % 3600, 60);
    $streak = (int) ($streakData['streak'] ?? 0);
    $nextModeData = $modes[$nextMode] ?? reset($modes);
    $modeColors = [
        'amber' => [
            'badge' => 'bg-amber-50 text-amber-700 ring-amber-500/20',
            'text' => 'text-amber-600',
            'ring' => 'ring-amber-400/40',
            'btn' => 'group-hover:bg-amber-600 group-hover:text-white',
            'glow' => 'from-amber-500/10 via-amber-500/[0.02] to-transparent',
        ],
        'indigo' => [
            'badge' => 'bg-indigo-50 text-indigo-700 ring-indigo-500/20',
            'text' => 'text-indigo-600',
            'ring' => 'ring-indigo-400/40',
            'btn' => 'group-hover:bg-indigo-600 group-hover:text-white',
            'glow' => 'from-indigo-500/10 via-indigo-500/[0.02] to-transparent',
        ],
        'violet' => [
            'badge' => 'bg-violet-50 text-violet-700 ring-violet-500/20',
            'text' => 'text-violet-600',
            'ring' => 'ring-violet-400/40',
            'btn' => 'group-hover:bg-violet-600 group-hover:text-white',
            'glow' => 'from-violet-500/10 via-violet-500/[0.02] to-transparent',
        ],
    ];
@endphp

<x-app-layout title="Dashboard">
    <div class="mx-auto max-w-6xl px-4 pb-12 pt-5 sm:px-6 sm:pt-8">

        {{-- ========== 1. EXECUTIVE COMMAND HERO BANNER ========== --}}
        <section class="relative overflow-hidden rounded-[1.75rem] bg-navy-950 p-6 text-white shadow-card ring-1 ring-white/10 sm:rounded-[2rem] sm:p-8">
            {{-- Ambient background glows --}}
            <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-brand-600/30 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-28 left-1/4 h-72 w-72 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 opacity-20 [background-image:radial-gradient(rgb(255_255_255/0.14)_1px,transparent_1px)] [background-size:20px_20px]" aria-hidden="true"></div>

            <div class="relative z-10 grid items-center gap-6 lg:grid-cols-[1.35fr_1fr]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-sky-300 backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            VOIC AI Workspace
                        </span>
                        @if ($streak > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/15 px-3 py-1 text-[11px] font-bold text-amber-300 ring-1 ring-amber-400/30">
                                <svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M13.5 4.938a7 7 0 11-9.006 1.737c.202-.257.59-.218.793.039.278.352.594.672.943.954.332.269.786-.049.773-.476a5.977 5.977 0 01.572-2.759 6.026 6.026 0 012.486-2.665c.247-.14.55-.016.677.238A6.967 6.967 0 0013.5 4.938zM14 12a4 4 0 01-4 4c-1.913 0-3.52-1.398-3.91-3.182-.093-.429.44-.643.814-.413a4.043 4.043 0 001.601.564c.303.038.531-.24.51-.544a5.975 5.975 0 011.315-4.192.447.447 0 01.431-.16A4.001 4.001 0 0114 12z" clip-rule="evenodd"/>
                                </svg>
                                {{ $streak }} Hari Beruntun
                            </span>
                        @endif
                    </div>

                    <p class="mt-4 text-sm font-medium text-slate-300">Halo, <span class="font-bold text-white">{{ $firstName }}</span></p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl lg:text-[32px] lg:leading-tight">
                        @if ($totalSessions === 0)
                            Siap memulai simulasi bicara pertamamu?
                        @elseif ($streakData['has_practiced_today'] ?? false)
                            Target latihan hari ini sudah tercapai.
                        @else
                            Lanjutkan satu sesi latihan hari ini.
                        @endif
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300/85">
                        Latih kelancaran bicara, ketajaman argumen, dan kontak mata secara langsung bersama penguji AI standar internasional.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('exam.setup', $nextMode) }}"
                           class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-brand-500 via-brand-600 to-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-glow-blue transition hover:brightness-110 active:scale-[0.99] sm:w-auto">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                            <span>Mulai {{ $blueprint->text($nextModeData['title'], 'id') }}</span>
                        </a>
                        <a href="{{ route('exam.index') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] px-4 py-3.5 text-sm font-semibold text-slate-200 backdrop-blur transition hover:bg-white/10 hover:text-white sm:w-auto">
                            <span>Semua Mode Ujian</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Right Spotlight Card: Recommended Examiner --}}
                <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl sm:p-5">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rekomendasi Sesi Berikutnya</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-[11px] font-bold text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Siap Menguji
                        </span>
                    </div>
                    <div class="mt-4 flex items-center gap-4">
                        <div class="relative shrink-0">
                            <img src="{{ asset($nextModeData['examiner']['avatar']) }}"
                                 alt="{{ $nextModeData['examiner']['name'] }}"
                                 class="h-14 w-14 rounded-2xl object-cover ring-2 ring-brand-400/60">
                            <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 ring-2 ring-navy-950">
                                <span class="flex h-2.5 items-end gap-[1.5px]">
                                    <span class="voic-eq w-[1.5px] rounded-full bg-white"></span>
                                    <span class="voic-eq w-[1.5px] rounded-full bg-white [animation-delay:150ms]"></span>
                                    <span class="voic-eq w-[1.5px] rounded-full bg-white [animation-delay:300ms]"></span>
                                </span>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-extrabold text-white">{{ $blueprint->text($nextModeData['title'], 'id') }}</p>
                            <p class="truncate text-xs font-semibold text-sky-300">{{ $nextModeData['examiner']['name'] }} · {{ $blueprint->text($nextModeData['examiner']['title'], 'id') }}</p>
                            <p class="mt-1 text-xs text-slate-400">3 Bagian Ujian · ~12 Menit · Rapor Instan</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ========== 2. 4-COLUMN BENTO KPI GRID ========== --}}
        <section class="mt-6 grid grid-cols-2 gap-3.5 sm:gap-4 lg:grid-cols-4" aria-label="Statistik Performa">
            {{-- Card 1: Total Sesi --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:rounded-3xl sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sesi</span>
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-500/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $totalSessions }}</p>
                <p class="mt-2 truncate text-xs font-medium text-slate-500">
                    @if ($bestBand !== null)
                        {{-- Bands everywhere else (0-9); the internal 0-100 score would read as a different scale. --}}
                        Band terbaik <span class="font-bold text-slate-700">{{ number_format($bestBand, 1) }}</span>
                    @elseif ($bestScore > 0)
                        Skor terbaik <span class="font-bold text-slate-700">{{ number_format($bestScore, 0) }}</span>
                    @else
                        Belum ada riwayat sesi
                    @endif
                </p>
            </div>

            {{-- Card 2: Band Terakhir --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:rounded-3xl sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Band Terakhir</span>
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-500/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <p class="text-2xl font-extrabold tabular-nums tracking-tight text-slate-900 sm:text-3xl">
                        {{ $progress ? number_format($progress['latest'], 1) : ($averageScore > 0 ? number_format($averageScore, 0) : '–') }}
                    </p>
                    @if ($progress)
                        <span class="text-xs font-bold text-slate-400">/ 9.0</span>
                    @endif
                </div>
                <p class="mt-2 truncate text-xs font-medium text-slate-500">
                    @if ($progress && $progress['delta'] !== null)
                        <span class="font-bold {{ $progress['delta'] >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ ($progress['delta'] >= 0 ? '+' : '').number_format($progress['delta'], 1) }}
                        </span>
                        dari sesi sebelumnya
                    @elseif ($averageScore > 0)
                        Rata-rata skor <span class="font-bold text-slate-700">{{ number_format($averageScore, 1) }}</span>
                    @else
                        Skala evaluasi 0–9.0
                    @endif
                </p>
            </div>

            {{-- Card 3: Hari Beruntun --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:rounded-3xl sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Hari Beruntun</span>
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <p class="text-2xl font-extrabold tabular-nums tracking-tight text-slate-900 sm:text-3xl">{{ $streak }}</p>
                    <span class="text-xs font-bold text-slate-400">hari</span>
                </div>
                <p class="mt-2 truncate text-xs font-medium text-slate-500">
                    @if ($streak > 0 && ($streakData['is_streak_at_risk'] ?? false))
                        <span class="font-bold text-amber-600">Latihan hari ini!</span>
                    @elseif ($streak > 0)
                        Konsistensi terjaga
                    @else
                        Mulai streak pertamamu
                    @endif
                </p>
            </div>

            {{-- Card 4: Waktu Bicara --}}
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:rounded-3xl sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Waktu Bicara</span>
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tabular-nums tracking-tight text-slate-900 sm:text-3xl">
                    {{ $hours > 0 ? $hours.' j '.$minutes.' m' : $minutes.' m' }}
                </p>
                <p class="mt-2 truncate text-xs font-medium text-slate-500">Total durasi latihan aktif</p>
            </div>
        </section>

        {{-- ========== 3. PILIH LATIHAN (3-COLUMN EXAMINER SPOTLIGHT GRID) ========== --}}
        <section class="mt-8" aria-label="Pilih latihan">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">Pilih Mode Simulasi</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Berlatih tatap muka bersama penguji AI sesuai kebutuhan karirmu</p>
                </div>
                <a href="{{ route('exam.index') }}" class="hidden items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700 sm:inline-flex">
                    <span>Detail mode</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($modes as $key => $mode)
                    @php
                        $palette = $modeColors[$mode['accent']] ?? $modeColors['indigo'];
                    @endphp
                    <a href="{{ route('exam.setup', $key) }}"
                       class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-card sm:rounded-3xl">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b {{ $palette['glow'] }}" aria-hidden="true"></div>

                        <div class="relative z-10">
                            <div class="flex items-start justify-between gap-3">
                                <div class="relative shrink-0">
                                    <img src="{{ asset($mode['examiner']['avatar']) }}"
                                         alt="{{ $mode['examiner']['name'] }}"
                                         loading="lazy"
                                         class="h-13 w-13 rounded-2xl object-cover ring-2 {{ $palette['ring'] }}"
                                         style="width: 52px; height: 52px;">
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-white" aria-hidden="true"></span>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $palette['badge'] }}">
                                    @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-3.5 w-3.5'])
                                    <span>~12 mnt</span>
                                </span>
                            </div>

                            <h3 class="mt-4 text-base font-extrabold tracking-tight text-slate-900">{{ $blueprint->text($mode['title'], 'id') }}</h3>
                            <p class="mt-0.5 text-xs font-bold {{ $palette['text'] }}">{{ $mode['examiner']['name'] }} · {{ $blueprint->text($mode['examiner']['title'], 'id') }}</p>
                            <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $blueprint->text($mode['tagline'], 'id') }}</p>
                        </div>

                        <div class="relative z-10 mt-5 flex items-center justify-between border-t border-slate-100 pt-3.5">
                            <span class="text-xs font-bold text-slate-700 group-hover:text-slate-900">Mulai Simulasi</span>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-700 transition {{ $palette['btn'] }}">
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ========== 4. ANALYTICS & RECENT ACTIVITY BENTO SPLIT ========== --}}
        <div class="mt-8 grid items-start gap-6 lg:grid-cols-12">
            {{-- Left Column (7 cols): Progress Chart or Structured Empty State --}}
            <div class="lg:col-span-7">
                @if ($progress)
                    @include('exam.partials.progress', ['progress' => $progress, 'drill' => $drill])
                @else
                    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xs sm:rounded-3xl sm:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-700">
                                    Analitik Evaluasi AI
                                </span>
                                <h2 class="mt-2.5 text-lg font-extrabold tracking-tight text-slate-900">Grafik perkembanganmu muncul di sini</h2>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500 sm:text-sm">
                                    Selesaikan satu simulasi ujian penuh untuk membuka analisis 4 dimensi kemampuan komunikasi dan pelacakan skor otomatis.
                                </p>
                            </div>
                        </div>

                        {{-- 4 Evaluation Pillars Preview Grid --}}
                        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ([
                                ['Kelancaran', 'Tempo & jeda bicara', 'bg-brand-50 text-brand-600'],
                                ['Struktur', 'Alur logika & STAR', 'bg-indigo-50 text-indigo-600'],
                                ['Kosakata', 'Diksi profesional', 'bg-violet-50 text-violet-600'],
                                ['Penyampaian', 'Intonasi & tatapan', 'bg-emerald-50 text-emerald-600'],
                            ] as [$pillar, $desc, $tone])
                                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5">
                                    <span class="inline-flex h-2 w-6 rounded-full {{ explode(' ', $tone)[0] }}"></span>
                                    <p class="mt-2.5 text-xs font-extrabold text-slate-900">{{ $pillar }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ $desc }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                            <span class="text-xs font-semibold text-slate-600">Siap mengambil band score pertamamu?</span>
                            <a href="{{ route('exam.setup', $nextMode) }}" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-brand-600 hover:text-brand-700">
                                <span>Mulai Sekarang</span>
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>
                    </section>
                @endif
            </div>

            {{-- Right Column (5 cols): Recent Sessions --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xs sm:rounded-3xl lg:col-span-5">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900">Sesi terakhir</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $recentSessions->count() }}</span>
                    </div>
                    <a href="{{ route('practice.history') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700">
                        <span>Lihat semua</span>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($recentSessions as $session)
                        @php
                            $band = $session->feedback_notes['exam_report']['overall_band'] ?? null;
                            $finished = ! $session->isExam() || $session->hasReport();
                            $scoreDisplay = $band !== null
                                ? number_format($band, 1)
                                : ($session->isCoaching() ? 'COACH' : ($session->isExam() ? '…' : number_format($session->overall_score, 0)));
                        @endphp
                        <a href="{{ $session->reportUrl() }}"
                           class="group flex items-center gap-3.5 px-5 py-3.5 transition hover:bg-slate-50/90 active:bg-slate-100/80 sm:px-6">
                            <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50 text-slate-900 transition group-hover:border-brand-200 group-hover:bg-brand-50 group-hover:text-brand-700">
                                <span class="text-xs font-extrabold tabular-nums leading-none">{{ $scoreDisplay }}</span>
                                <span class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-brand-600">
                                    {{ $band !== null ? 'Band' : ($session->isCoaching() ? 'Mode' : 'Skor') }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-900 group-hover:text-brand-700">{{ $session->scenario_type }}</p>
                                <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                    <span>{{ $session->created_at->locale('id')->diffForHumans() }}</span>
                                    @if ($session->duration_seconds)
                                        <span>·</span>
                                        <span class="tabular-nums">{{ intdiv($session->duration_seconds, 60) }}:{{ str_pad((string) ($session->duration_seconds % 60), 2, '0', STR_PAD_LEFT) }} mnt</span>
                                    @endif
                                    @unless ($finished)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Belum selesai</span>
                                    @endunless
                                </div>
                            </div>

                            <svg class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-slate-800">Belum ada riwayat sesi</p>
                            <p class="mt-1 text-xs text-slate-500">Pilih salah satu mode simulasi di atas untuk memulai latihan pertamamu.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
