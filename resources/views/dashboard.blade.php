@php
    $accents = config('exam.accents');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
    $hours = intdiv($totalDurationSeconds, 3600);
    $minutes = intdiv($totalDurationSeconds % 3600, 60);
    $streak = (int) ($streakData['streak'] ?? 0);
@endphp

<x-app-layout title="Dashboard">
    <div class="mx-auto max-w-6xl px-4 pb-10 pt-6 sm:px-6 sm:pt-8">

        {{-- Greeting + CTA --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Halo, {{ $firstName }} 👋</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">
                    @if ($totalSessions === 0)
                        Siap latihan pertama?
                    @elseif ($streakData['has_practiced_today'] ?? false)
                        Mantap, kamu sudah latihan hari ini.
                    @else
                        Yuk, satu sesi lagi hari ini.
                    @endif
                </h1>
            </div>
            <a href="{{ route('exam.setup', $nextMode) }}"
               class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-bold text-white active:bg-slate-700 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 w-full sm:w-auto transition">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                Mulai {{ $blueprint->text($modes[$nextMode]['title'], 'id') }}
            </a>
        </div>

        {{-- Stat cards --}}
        <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Statistik">
            @foreach ([
                ['Total sesi', $totalSessions, null, '🎯'],
                ['Band terakhir', $progress ? number_format($progress['latest'], 1) : '–', $progress && $progress['delta'] !== null ? ($progress['delta'] >= 0 ? '+' : '').number_format($progress['delta'], 1).' dari sblm' : null, '📊'],
                ['Hari beruntun', $streak, $streak > 0 && ($streakData['is_streak_at_risk'] ?? false) ? 'Latihan hari ini!' : null, '🔥'],
                ['Waktu bicara', $hours > 0 ? $hours.' j '.$minutes.' m' : $minutes.' m', null, '🎙️'],
            ] as [$label, $value, $hint, $emoji])
                <div class="rounded-2xl border border-black/5 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03] sm:rounded-3xl sm:p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <p class="mt-1.5 text-3xl font-extrabold tracking-tight leading-none">{{ $value }}</p>
                    @if ($hint)
                        <p class="mt-1.5 text-[11px] font-medium text-slate-500 leading-snug">{{ $hint }}</p>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Progress chart --}}
        <div class="mt-5">
            @if ($progress)
                @include('exam.partials.progress', ['progress' => $progress, 'drill' => $drill])
            @else
                <section class="rounded-2xl border border-dashed border-black/10 p-8 text-center dark:border-white/15 sm:rounded-3xl">
                    <p class="text-2xl mb-2">📈</p>
                    <p class="font-bold">Grafik perkembanganmu muncul di sini</p>
                    <p class="mt-1 text-sm text-slate-500">Selesaikan satu simulasi untuk mendapat band score pertama.</p>
                </section>
            @endif
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[1.3fr_1fr]">
            {{-- Recent sessions --}}
            <section class="rounded-2xl border border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.03] sm:rounded-3xl">
                <div class="flex items-center justify-between px-4 pt-4 sm:px-5 sm:pt-5">
                    <h2 class="font-bold">Sesi terakhir</h2>
                    <a href="{{ route('practice.history') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">Lihat semua</a>
                </div>
                <div class="px-2 pb-2 mt-1 sm:px-3 sm:pb-3">
                    @forelse ($recentSessions as $session)
                        @php
                            $band = $session->feedback_notes['exam_report']['overall_band'] ?? null;
                            $finished = ! $session->isExam() || $session->hasReport();
                        @endphp
                        <a href="{{ $session->reportUrl() }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-black/[0.03] active:bg-black/[0.06] dark:hover:bg-white/5 transition">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#f6f6f3] text-sm font-extrabold dark:bg-white/5">
                                {{ $band !== null ? number_format($band, 1) : ($session->isCoaching() ? '💬' : ($session->isExam() ? '…' : number_format($session->overall_score, 0))) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-[15px]">{{ $session->scenario_type }}</span>
                                <span class="block text-xs text-slate-500 mt-0.5">{{ $session->created_at->locale('id')->diffForHumans() }}{{ $finished ? '' : ' · belum selesai' }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @empty
                        <p class="px-2 py-4 text-sm text-slate-500">Belum ada sesi. Pilih salah satu mode di bawah untuk mulai.</p>
                    @endforelse
                </div>
            </section>

            {{-- Practice mode cards --}}
            <section>
                <h2 class="text-sm font-bold mb-3">Pilih latihan</h2>
                <div class="space-y-2.5">
                    @foreach ($modes as $key => $mode)
                        @php $accent = $accents[$mode['accent']] ?? $accents['indigo']; @endphp
                        <a href="{{ route('exam.setup', $key) }}"
                           class="flex items-center gap-4 rounded-2xl border border-black/5 bg-white p-4 transition hover:shadow-sm active:bg-black/[0.02] dark:border-white/10 dark:bg-white/[0.03] {{ $accent['border'] }} sm:rounded-3xl">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $accent['soft'] }}">
                                @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-5 w-5'])
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-[15px]">{{ $blueprint->text($mode['title'], 'id') }}</span>
                                <span class="block truncate text-xs text-slate-500 mt-0.5">{{ $mode['examiner']['name'] }} · {{ $blueprint->text($mode['examiner']['title'], 'id') }}</span>
                            </span>
                            <span class="text-sm font-bold {{ $accent['text'] }} shrink-0">Mulai →</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
