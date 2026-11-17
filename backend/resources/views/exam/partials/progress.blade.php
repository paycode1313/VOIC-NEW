{{--
    Band-score progress: one line chart for the overall band (single series, no legend)
    plus small multiples for the four criteria, a hover tooltip, and a table view.
    Expects $progress from ExamProgressService::forUser() and $drill (callable criterion -> url).
--}}
@php
    $points = $progress['points'];
    $count = count($points);
    $w = 600; $h = 180; $padL = 28; $padR = 16; $padT = 14; $padB = 26;
    $x = fn (int $i) => $count > 1 ? $padL + $i * (($w - $padL - $padR) / ($count - 1)) : ($w + $padL - $padR) / 2;
    $y = fn (float $band) => $padT + (1 - $band / 9) * ($h - $padT - $padB);
    $linePath = collect($points)->map(fn ($p, $i) => ($i ? 'L' : 'M').round($x($i), 1).' '.round($y($p['overall']), 1))->implode(' ');
    $areaPath = $count > 1 ? $linePath.' L'.round($x($count - 1), 1).' '.($h - $padB).' L'.round($x(0), 1).' '.($h - $padB).' Z' : '';
    $tooltipPoints = collect($points)->map(fn ($p, $i) => $p + ['left' => round($x($i) / $w * 100, 2)])->all();
    $deltaText = fn (?float $d) => $d === null ? 'Stabil' : ($d > 0 ? '▲ +'.number_format($d, 1) : ($d < 0 ? '▼ '.number_format($d, 1) : '• 0.0'));
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-6"
         x-data="{ active: null, points: @js($tooltipPoints) }">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-700">
                Analitik Performa
            </span>
            <h2 class="mt-1.5 text-base font-extrabold tracking-tight text-slate-900">Perkembangan band keseluruhan</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ $count }} sesi terakhir · skala evaluasi 0–9.0</p>
        </div>
        <div class="text-right">
            <div class="inline-flex items-baseline gap-1">
                <p class="text-3xl font-extrabold tabular-nums tracking-tight text-slate-900">{{ number_format($progress['latest'], 1) }}</p>
                <span class="text-xs font-bold text-slate-400">/ 9.0</span>
            </div>
            @if ($progress['delta'] !== null)
                <p class="mt-0.5 text-xs font-bold {{ $progress['delta'] >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $deltaText($progress['delta']) }} dari sesi sebelumnya
                </p>
            @endif
        </div>
    </div>

    <div class="relative mt-5">
        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-auto w-full text-brand-600" role="img"
             aria-label="Grafik band keseluruhan {{ $count }} sesi terakhir, terakhir {{ number_format($progress['latest'], 1) }}">
            <defs>
                <linearGradient id="voicBandAreaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.22" />
                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0" />
                </linearGradient>
            </defs>

            @foreach ([3, 6, 9] as $tick)
                <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" class="stroke-slate-100" stroke-dasharray="3 3" stroke-width="1" />
                <text x="{{ $padL - 8 }}" y="{{ $y($tick) + 3.5 }}" text-anchor="end" class="fill-slate-400 font-semibold" font-size="9">{{ $tick }}.0</text>
            @endforeach
            <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $h - $padB }}" y2="{{ $h - $padB }}" class="stroke-slate-200" stroke-width="1" />

            @if ($areaPath)
                <path d="{{ $areaPath }}" fill="url(#voicBandAreaGrad)" />
                <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
            @endif

            @foreach ($points as $i => $point)
                <circle cx="{{ $x($i) }}" cy="{{ $y($point['overall']) }}" r="4.5" fill="currentColor"
                        class="stroke-white" stroke-width="2.2" :r="active === {{ $i }} ? 6.5 : 4.5" />
                @if ($i === 0 || $i === $count - 1)
                    <text x="{{ $x($i) }}" y="{{ $h - 7 }}" text-anchor="{{ $i === 0 && $count > 1 ? 'start' : ($i === $count - 1 && $count > 1 ? 'end' : 'middle') }}" class="fill-slate-400 font-medium" font-size="9">{{ $point['date'] }}</text>
                @endif
                {{-- Wide invisible hit target per point --}}
                <rect x="{{ $x($i) - max(14, ($w - $padL - $padR) / max(1, $count) / 2) }}" y="0" width="{{ max(28, ($w - $padL - $padR) / max(1, $count)) }}" height="{{ $h }}"
                      fill="transparent" @mouseenter="active = {{ $i }}" @mouseleave="active = null" @focus="active = {{ $i }}" @blur="active = null"
                      tabindex="0" aria-label="{{ $point['date'] }}, {{ $point['label'] }}, band {{ number_format($point['overall'], 1) }}" />
            @endforeach
        </svg>

        <template x-if="active !== null">
            <div class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-xl border border-slate-200 bg-navy-950 px-3 py-2 text-xs text-white shadow-lg"
                 :style="`left: ${points[active].left}%`">
                <p class="font-bold text-slate-200" x-text="points[active].date + ' · ' + points[active].label"></p>
                <p class="mt-0.5 text-slate-400">Band <span class="font-extrabold text-sky-300" x-text="points[active].overall.toFixed(1)"></span></p>
            </div>
        </template>
    </div>

    {{-- Small multiples: 4 Criteria Cards --}}
    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($progress['labels'] as $key => $label)
            @php
                $series = array_map(fn ($p) => $p['criteria'][$key] ?? 0, $points);
                $last = end($series);
                $prev = count($series) > 1 ? $series[count($series) - 2] : null;
                $sw = 120; $sh = 30;
                $sx = fn ($i) => count($series) > 1 ? 2 + $i * (($sw - 4) / (count($series) - 1)) : $sw / 2;
                $sy = fn ($v) => 2 + (1 - $v / 9) * ($sh - 4);
                $spark = collect($series)->map(fn ($v, $i) => round($sx($i), 1).','.round($sy($v), 1))->implode(' ');
                $isWeakest = $progress['weakest'] === $key;
                $diff = $prev === null ? null : round($last - $prev, 1);
            @endphp
            <div class="flex flex-col justify-between rounded-2xl border {{ $isWeakest ? 'border-amber-200/90 bg-amber-50/40' : 'border-slate-200/70 bg-slate-50/70' }} p-3.5">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="truncate text-xs font-bold text-slate-700">{{ $label }}</p>
                    <p class="text-sm font-extrabold tabular-nums text-slate-900">{{ number_format($last, 1) }}</p>
                </div>
                <svg viewBox="0 0 {{ $sw }} {{ $sh }}" class="my-2 h-7 w-full {{ $isWeakest ? 'text-amber-600' : 'text-brand-600' }}" aria-hidden="true">
                    @if (count($series) > 1)
                        <polyline points="{{ $spark }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                    @endif
                    <circle cx="{{ $sx(count($series) - 1) }}" cy="{{ $sy($last) }}" r="3.5" fill="currentColor" />
                </svg>
                <div class="flex items-center justify-between gap-1 text-[11px]">
                    <span class="font-semibold {{ $diff > 0 ? 'text-emerald-600' : ($diff < 0 ? 'text-amber-600' : 'text-slate-400') }}">
                        {{ $deltaText($diff) }}
                    </span>
                    @if ($isWeakest && isset($drill))
                        <a href="{{ $drill($key) }}" class="font-bold text-brand-600 hover:text-brand-700 hover:underline">Latih ini →</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <details class="mt-4 border-t border-slate-100 pt-3.5 text-xs">
        <summary class="cursor-pointer select-none font-bold text-slate-500 hover:text-slate-800">Lihat sebagai tabel</summary>
        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200/70">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-bold">Tanggal</th>
                        <th class="px-3 py-2 font-bold">Mode</th>
                        <th class="px-3 py-2 font-bold">Band</th>
                        @foreach ($progress['labels'] as $label)
                            <th class="px-3 py-2 font-bold">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach (array_reverse($points) as $point)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-2 font-semibold text-brand-600"><a href="{{ $point['url'] }}" class="hover:underline">{{ $point['date'] }}</a></td>
                            <td class="px-3 py-2 font-medium text-slate-700">{{ $point['label'] }}</td>
                            <td class="px-3 py-2 font-extrabold tabular-nums text-slate-900">{{ number_format($point['overall'], 1) }}</td>
                            @foreach ($progress['labels'] as $key => $label)
                                <td class="px-3 py-2 tabular-nums text-slate-600">{{ number_format($point['criteria'][$key] ?? 0, 1) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
