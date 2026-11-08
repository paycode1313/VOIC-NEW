{{--
    Band-score progress: one line chart for the overall band (single series, no legend)
    plus small multiples for the four criteria, a hover tooltip, and a table view.
    Expects $progress from ExamProgressService::forUser() and $drill (callable criterion -> url).
--}}
@php
    $points = $progress['points'];
    $count = count($points);
    $w = 600; $h = 180; $padL = 28; $padR = 16; $padT = 12; $padB = 26;
    $x = fn (int $i) => $count > 1 ? $padL + $i * (($w - $padL - $padR) / ($count - 1)) : ($w + $padL - $padR) / 2;
    $y = fn (float $band) => $padT + (1 - $band / 9) * ($h - $padT - $padB);
    $linePath = collect($points)->map(fn ($p, $i) => ($i ? 'L' : 'M').round($x($i), 1).' '.round($y($p['overall']), 1))->implode(' ');
    $areaPath = $count > 1 ? $linePath.' L'.round($x($count - 1), 1).' '.($h - $padB).' L'.round($x(0), 1).' '.($h - $padB).' Z' : '';
    $tooltipPoints = collect($points)->map(fn ($p, $i) => $p + ['left' => round($x($i) / $w * 100, 2)])->all();
    $deltaText = fn (?float $d) => $d === null ? '' : ($d > 0 ? '▲ +'.number_format($d, 1) : ($d < 0 ? '▼ '.number_format($d, 1) : '• 0.0'));
@endphp

<section class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03] sm:p-6"
         x-data="{ active: null, points: @js($tooltipPoints) }">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold">Perkembangan band keseluruhan</h2>
            <p class="text-xs text-slate-500">{{ $count }} sesi terakhir · skala 0–9</p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-extrabold tracking-tight">{{ number_format($progress['latest'], 1) }}</p>
            @if ($progress['delta'] !== null)
                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $deltaText($progress['delta']) }} dari sesi sebelumnya</p>
            @endif
        </div>
    </div>

    <div class="relative mt-4">
        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-auto w-full text-slate-900 dark:text-white" role="img"
             aria-label="Grafik band keseluruhan {{ $count }} sesi terakhir, terakhir {{ number_format($progress['latest'], 1) }}">
            @foreach ([3, 6, 9] as $tick)
                <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" class="stroke-slate-200 dark:stroke-white/10" stroke-width="1" />
                <text x="{{ $padL - 8 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" class="fill-slate-400" font-size="8.5">{{ $tick }}</text>
            @endforeach
            <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $h - $padB }}" y2="{{ $h - $padB }}" class="stroke-slate-200 dark:stroke-white/10" stroke-width="1" />

            @if ($areaPath)
                <path d="{{ $areaPath }}" fill="currentColor" opacity="0.08" />
                <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
            @endif

            @foreach ($points as $i => $point)
                <circle cx="{{ $x($i) }}" cy="{{ $y($point['overall']) }}" r="4.5" fill="currentColor"
                        class="stroke-white dark:stroke-[#16171b]" stroke-width="2" :r="active === {{ $i }} ? 6 : 4.5" />
                @if ($i === 0 || $i === $count - 1)
                    <text x="{{ $x($i) }}" y="{{ $h - 8 }}" text-anchor="{{ $i === 0 && $count > 1 ? 'start' : ($i === $count - 1 && $count > 1 ? 'end' : 'middle') }}" class="fill-slate-400" font-size="8.5">{{ $point['date'] }}</text>
                @endif
                {{-- Wide invisible hit target per point --}}
                <rect x="{{ $x($i) - max(14, ($w - $padL - $padR) / max(1, $count) / 2) }}" y="0" width="{{ max(28, ($w - $padL - $padR) / max(1, $count)) }}" height="{{ $h }}"
                      fill="transparent" @mouseenter="active = {{ $i }}" @mouseleave="active = null" @focus="active = {{ $i }}" @blur="active = null"
                      tabindex="0" aria-label="{{ $point['date'] }}, {{ $point['label'] }}, band {{ number_format($point['overall'], 1) }}" />
            @endforeach
        </svg>

        <template x-if="active !== null">
            <div class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-xl border border-black/10 bg-white px-3 py-2 text-xs shadow-lg dark:border-white/10 dark:bg-[#1b1c21]"
                 :style="`left: ${points[active].left}%`">
                <p class="font-semibold" x-text="points[active].date + ' · ' + points[active].label"></p>
                <p class="mt-0.5 text-slate-600 dark:text-slate-300">Band <span class="font-bold text-slate-900 dark:text-white" x-text="points[active].overall.toFixed(1)"></span></p>
            </div>
        </template>
    </div>

    {{-- Small multiples: one quiet sparkline per criterion --}}
    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($progress['labels'] as $key => $label)
            @php
                $series = array_map(fn ($p) => $p['criteria'][$key] ?? 0, $points);
                $last = end($series);
                $prev = count($series) > 1 ? $series[count($series) - 2] : null;
                $sw = 120; $sh = 32;
                $sx = fn ($i) => count($series) > 1 ? 2 + $i * (($sw - 4) / (count($series) - 1)) : $sw / 2;
                $sy = fn ($v) => 2 + (1 - $v / 9) * ($sh - 4);
                $spark = collect($series)->map(fn ($v, $i) => round($sx($i), 1).','.round($sy($v), 1))->implode(' ');
                $isWeakest = $progress['weakest'] === $key;
            @endphp
            <div class="rounded-2xl bg-[#f6f6f3] p-3 dark:bg-white/5">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">{{ $label }}</p>
                    <p class="text-sm font-extrabold">{{ number_format($last, 1) }}</p>
                </div>
                <svg viewBox="0 0 {{ $sw }} {{ $sh }}" class="mt-1 h-8 w-full text-slate-900 dark:text-white" aria-hidden="true">
                    @if (count($series) > 1)
                        <polyline points="{{ $spark }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                    @endif
                    <circle cx="{{ $sx(count($series) - 1) }}" cy="{{ $sy($last) }}" r="3.5" fill="currentColor" />
                </svg>
                <div class="mt-1 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500">{{ $deltaText($prev === null ? null : round($last - $prev, 1)) }}</span>
                    @if ($isWeakest && isset($drill))
                        <a href="{{ $drill($key) }}" class="font-semibold text-slate-900 underline dark:text-white">Latih ini →</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <details class="mt-4 text-xs">
        <summary class="cursor-pointer font-semibold text-slate-500">Lihat sebagai tabel</summary>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left">
                <thead class="text-slate-500">
                    <tr>
                        <th class="py-1 pr-3 font-semibold">Tanggal</th>
                        <th class="py-1 pr-3 font-semibold">Mode</th>
                        <th class="py-1 pr-3 font-semibold">Band</th>
                        @foreach ($progress['labels'] as $label)
                            <th class="py-1 pr-3 font-semibold">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_reverse($points) as $point)
                        <tr class="border-t border-black/5 dark:border-white/10">
                            <td class="py-1 pr-3"><a href="{{ $point['url'] }}" class="underline">{{ $point['date'] }}</a></td>
                            <td class="py-1 pr-3">{{ $point['label'] }}</td>
                            <td class="py-1 pr-3 font-bold">{{ number_format($point['overall'], 1) }}</td>
                            @foreach ($progress['labels'] as $key => $label)
                                <td class="py-1 pr-3">{{ number_format($point['criteria'][$key] ?? 0, 1) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
