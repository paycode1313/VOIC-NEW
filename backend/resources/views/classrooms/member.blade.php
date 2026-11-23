@php
    $points = $progress['points'];
    $latestCriteria = $points === [] ? [] : end($points)['criteria'];
    $barColor = fn (float $band) => $band >= 7 ? 'bg-emerald-500' : ($band >= 5.5 ? 'bg-amber-500' : 'bg-rose-500');
@endphp

<x-exam-layout :title="$student->name" :back="route('classrooms.show', $classroom)" :back-label="$classroom->name">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $classroom->name }}</p>
        <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">{{ $student->name }}</h1>
        <p class="mt-1 text-slate-600">{{ $summary['sessions'] }} simulasi ujian selesai{{ $summary['last_at'] ? ' · terakhir '.$summary['last_at']->locale('id')->diffForHumans() : '' }}</p>

        @if ($points === [])
            <div class="mt-8 rounded-3xl border border-black/5 bg-white p-8 text-center">
                <p class="text-lg font-extrabold">Belum ada rapor ujian</p>
                <p class="mt-1 text-sm text-slate-600">Rapor muncul di sini setelah {{ $student->name }} menyelesaikan simulasi ujian (bukan mode coaching).</p>
            </div>
        @else
            <div class="mt-8 grid gap-6 md:grid-cols-[1.3fr_1fr]">
                {{-- Band over time: one bar per finished exam, oldest first --}}
                <section class="rounded-3xl border border-black/5 bg-white p-6">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-lg font-extrabold">Perkembangan band</h2>
                        <p class="text-sm text-slate-500">terbaik {{ number_format($progress['best'], 1) }}</p>
                    </div>
                    <div class="mt-5 flex h-40 items-end gap-2" role="img" aria-label="Band score {{ count($points) }} sesi terakhir">
                        @foreach ($points as $point)
                            <a href="{{ $point['url'] }}" class="group flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $point['label'] }} · {{ $point['date'] }} · {{ number_format($point['overall'], 1) }}">
                                <span class="text-[11px] font-bold text-slate-600">{{ number_format($point['overall'], 1) }}</span>
                                <span class="w-full max-w-10 shrink-0 rounded-t-lg {{ $barColor($point['overall']) }} opacity-80 transition group-hover:opacity-100" style="height: {{ round(max(4, $point['overall'] / 9 * 112)) }}px"></span>
                                <span class="text-[10px] text-slate-500">{{ $point['date'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Criteria of the latest exam --}}
                <section class="rounded-3xl border border-black/5 bg-white p-6">
                    <h2 class="text-lg font-extrabold">Kriteria di sesi terakhir</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($latestCriteria as $key => $band)
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span @class(['font-semibold', 'text-rose-600' => $key === $progress['weakest']])>{{ $progress['labels'][$key] ?? $key }}{{ $key === $progress['weakest'] ? ' · perlu dilatih' : '' }}</span>
                                    <span class="font-mono">{{ number_format($band, 1) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="voic-grow h-2 rounded-full {{ $barColor($band) }}" style="width: {{ $band / 9 * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif

        @if ($sessions->isNotEmpty())
            <section class="mt-6 overflow-hidden rounded-3xl border border-black/5 bg-white">
                <h2 class="px-6 pb-2 pt-5 text-lg font-extrabold">Rapor</h2>
                <ul class="divide-y divide-slate-100">
                    @foreach ($sessions as $session)
                        @php
                            $report = $session->feedback_notes['exam_report'] ?? null;
                            $mode = $blueprint->hasMode($session->exam_mode) ? $blueprint->mode($session->exam_mode) : null;
                        @endphp
                        <li>
                            <a href="{{ route('exam.result', $session) }}" class="flex items-center justify-between gap-4 px-6 py-3.5 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $session->scenario_type }}</p>
                                    <p class="text-xs text-slate-500">{{ $mode ? $blueprint->text($mode['title'], 'id') : $session->exam_mode }} · {{ $report ? 'ujian' : 'coaching' }} · {{ $session->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</p>
                                </div>
                                <span class="font-display shrink-0 text-xl font-extrabold">{{ $report ? number_format((float) $report['overall_band'], 1) : '–' }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <form method="POST" action="{{ route('classrooms.member.remove', [$classroom, $student]) }}" class="mt-10 text-center" onsubmit="return confirm('Keluarkan mahasiswa ini dari kelas? Kamu tidak bisa melihat rapornya lagi.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Keluarkan dari kelas</button>
        </form>
    </div>
</x-exam-layout>
