{{-- Leaderboard for a second screen at the booth (auto-refreshes). Dark, big type, readable from a few metres. --}}
<x-exam-layout title="Papan Skor" :call="true" :full-height="true">
    <meta http-equiv="refresh" content="20">
    <div class="relative isolate flex h-full flex-col overflow-hidden">
        <div class="pointer-events-none absolute -z-10 h-[70vmax] w-[70vmax] rounded-full opacity-50 blur-3xl voic-drift [background:radial-gradient(circle,rgb(99_102_241/0.5),transparent_65%)]" style="left: 40%; top: -30%"></div>
        <div class="pointer-events-none absolute -z-10 h-[50vmax] w-[50vmax] rounded-full opacity-40 blur-3xl voic-drift-slow [background:radial-gradient(circle,rgb(245_158_11/0.35),transparent_65%)]" style="left: -15%; bottom: -30%"></div>

        <div class="flex shrink-0 items-center justify-between px-8 py-5 lg:px-12">
            <img src="/images/logo/voic-logo-horizontal-dark.svg" alt="VOIC" class="h-10 w-auto" width="142" height="40">
            <span class="rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold">Innofest 2026 · {{ now()->translatedFormat('d F') }}</span>
        </div>

        <div class="mx-auto grid min-h-0 w-full max-w-7xl flex-1 gap-8 overflow-y-auto px-8 pb-8 lg:grid-cols-[1.6fr_1fr] lg:px-12">
            <div class="flex min-h-0 flex-col">
                <p class="text-sm font-bold uppercase tracking-[0.25em] text-white/75">Peringkat hari ini</p>
                <h1 class="mt-1 text-5xl font-extrabold tracking-tight xl:text-6xl">Siapa paling siap?</h1>

                @if (empty($leaders))
                    <div class="mt-8 flex flex-1 flex-col items-center justify-center rounded-3xl bg-white/[0.06] p-10 text-center ring-1 ring-white/10">
                        <p class="text-2xl font-extrabold">Belum ada juara hari ini</p>
                        <p class="mt-2 text-lg text-white/75">Jadi yang pertama di papan ini.</p>
                    </div>
                @else
                    <ol class="mt-6 space-y-2.5">
                        @foreach ($leaders as $rank => $leader)
                            @php
                                $medal = [0 => 'from-amber-300 to-amber-500 text-amber-950', 1 => 'from-slate-200 to-slate-400 text-slate-900', 2 => 'from-orange-300 to-orange-600 text-orange-950'][$rank] ?? null;
                            @endphp
                            <li @class([
                                'flex items-center gap-5 rounded-2xl px-5 ring-1',
                                'bg-white/[0.12] py-4 ring-white/20' => $rank < 3,
                                'bg-white/[0.05] py-2.5 ring-white/10' => $rank >= 3,
                            ])>
                                @if ($medal)
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-xl font-black {{ $medal }}">{{ $rank + 1 }}</span>
                                @else
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center text-lg font-extrabold text-white/75">{{ $rank + 1 }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p @class(['truncate font-extrabold', 'text-2xl' => $rank < 3, 'text-lg' => $rank >= 3])>{{ $leader['name'] }}</p>
                                    <p class="text-sm text-white/75">{{ $leader['mode'] }} · {{ $leader['time'] }}</p>
                                </div>
                                <span @class(['font-black tabular-nums', 'text-4xl' => $rank < 3, 'text-2xl' => $rank >= 3])>{{ number_format($leader['band'], 1) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div class="flex flex-col items-center justify-center rounded-[2rem] bg-white/[0.07] p-8 text-center ring-1 ring-white/10 backdrop-blur-xl">
                <p class="text-3xl font-extrabold leading-tight">Coba sekarang,<br>gratis</p>
                <p class="mt-3 text-lg text-white/75">Wawancara kerja · Sidang skripsi · Pitching bisnis</p>
                <div id="board-qr" data-url="{{ $startUrl }}" class="mt-6 h-60 w-60 rounded-3xl bg-white p-3 [&_svg]:h-full [&_svg]:w-full"></div>
                <p class="mt-5 text-base font-semibold">Pindai, lalu ditelepon penguji AI</p>
            </div>
        </div>
    </div>
    @include('booth.partials.qr', ['target' => 'board-qr'])
</x-exam-layout>
