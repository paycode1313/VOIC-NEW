@php
    $accents = config('exam.accents');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
    $hours = intdiv($totalDurationSeconds, 3600);
    $minutes = intdiv($totalDurationSeconds % 3600, 60);
    $streak = (int) ($streakData['streak'] ?? 0);
@endphp

<x-app-layout title="Dashboard">
    <div class="mx-auto max-w-6xl px-4 pb-20 pt-8 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-slate-500">Halo, {{ $firstName }} 👋</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight">
                    @if ($totalSessions === 0)
                        Siap latihan pertama?
                    @elseif ($streakData['has_practiced_today'] ?? false)
                        Mantap, kamu sudah latihan hari ini.
                    @else
                        Yuk, satu sesi lagi hari ini.
                    @endif
                </h1>
            </div>
            <a href="{{ route('exam.setup', $nextMode) }}" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                Mulai latihan {{ $blueprint->text($modes[$nextMode]['title'], 'id') }} →
            </a>
        </div>

        {{-- Headline numbers --}}
        <section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['Total sesi', $totalSessions, null],
                ['Band terakhir', $progress ? number_format($progress['latest'], 1) : '–', $progress && $progress['delta'] !== null ? ($progress['delta'] >= 0 ? '+' : '').number_format($progress['delta'], 1).' dari sebelumnya' : null],
                ['Hari beruntun', $streak, $streak > 0 && ($streakData['is_streak_at_risk'] ?? false) ? 'Latihan hari ini agar tidak putus' : null],
                ['Waktu bicara', $hours > 0 ? $hours.' j '.$minutes.' m' : $minutes.' m', null],
            ] as [$label, $value, $hint])
                <div class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-extrabold tracking-tight">{{ $value }}</p>
                    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
                </div>
            @endforeach
        </section>

        {{-- Progress --}}
        <div class="mt-6">
            @if ($progress)
                @include('exam.partials.progress', ['progress' => $progress, 'drill' => $drill])
            @else
                <section class="rounded-3xl border border-dashed border-black/10 p-8 text-center dark:border-white/15">
                    <p class="font-bold">Grafik perkembanganmu muncul di sini</p>
                    <p class="mt-1 text-sm text-slate-500">Selesaikan satu simulasi untuk mendapat band score pertama.</p>
                </section>
            @endif
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            {{-- Recent sessions --}}
            <section class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold">Sesi terakhir</h2>
                    <a href="{{ route('practice.history') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">Lihat semua</a>
                </div>
                @forelse ($recentSessions as $session)
                    @php
                        $band = $session->feedback_notes['exam_report']['overall_band'] ?? null;
                        $finished = ! $session->isExam() || $session->hasReport();
                    @endphp
                    <a href="{{ $session->reportUrl() }}" class="mt-3 flex items-center gap-4 rounded-2xl px-2 py-2 hover:bg-black/[0.03] dark:hover:bg-white/5">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#f6f6f3] text-sm font-extrabold dark:bg-white/5">
                            {{ $band !== null ? number_format($band, 1) : ($session->isCoaching() ? '💬' : ($session->isExam() ? '…' : number_format($session->overall_score, 0))) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $session->scenario_type }}</span>
                            <span class="block text-xs text-slate-500">{{ $session->created_at->locale('id')->diffForHumans() }}{{ $finished ? '' : ' · belum selesai' }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @empty
                    <p class="mt-4 text-sm text-slate-500">Belum ada sesi. Pilih salah satu mode di samping untuk mulai.</p>
                @endforelse
            </section>

            {{-- Modes --}}
            <section class="space-y-3">
                <h2 class="text-sm font-bold">Pilih latihan</h2>
                @foreach ($modes as $key => $mode)
                    @php $accent = $accents[$mode['accent']] ?? $accents['indigo']; @endphp
                    <a href="{{ route('exam.setup', $key) }}" class="flex items-center gap-4 rounded-3xl border border-black/5 bg-white p-4 transition hover:shadow-sm dark:border-white/10 dark:bg-white/[0.03] {{ $accent['border'] }}">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $accent['soft'] }}">
                            @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-5 w-5'])
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold">{{ $blueprint->text($mode['title'], 'id') }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $mode['examiner']['name'] }} · {{ $blueprint->text($mode['examiner']['title'], 'id') }}</span>
                        </span>
                        <span class="text-sm font-semibold {{ $accent['text'] }}">Mulai</span>
                    </a>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
