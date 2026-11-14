@php
    $engineLabel = ['local_llm' => 'AI lokal aktif', 'ollama_cloud' => ($status['model'] ?? 'Model').' (cloud) aktif', 'openrouter' => ($status['model'] ?? 'Model').' (OpenRouter) aktif', 'cloud_llm' => 'AI cloud aktif', 'offline_composer' => 'AI offline · mode cadangan'][$status['engine']] ?? $status['engine'];
    $healthy = in_array($status['engine'], ['local_llm', 'ollama_cloud', 'openrouter'], true);
@endphp

<x-exam-layout title="Operator Booth">
    <div class="mx-auto max-w-5xl px-4 pb-20 pt-8 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight">Operator booth</h1>
            <div class="flex gap-2">
                <a href="{{ route('attract') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">Buka layar sambut</a>
                <a href="{{ route('booth.board') }}" target="_blank" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">Buka papan skor ↗</a>
                <form method="POST" action="{{ route('booth.lock') }}">@csrf<button class="rounded-full px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-black/5 dark:hover:bg-white/10">Kunci</button></form>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-[1.4fr_1fr]">
            {{-- Next visitor: the one button operators press most --}}
            <form method="POST" action="{{ route('booth.next') }}" class="rounded-3xl bg-slate-900 p-6 text-white dark:bg-white dark:text-slate-900">
                @csrf
                <p class="text-sm font-semibold opacity-70">Setelah pengunjung selesai</p>
                <button type="submit" class="mt-3 w-full rounded-2xl bg-white px-6 py-5 text-xl font-extrabold text-slate-900 hover:bg-slate-100 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800">
                    Pengunjung berikutnya →
                </button>
                <p class="mt-3 text-xs opacity-70">Menghapus sesi tamu di browser ini lalu membuka halaman pilih mode.</p>
            </form>

            <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-bold">Status AI</p>
                <p class="mt-2 inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $healthy ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-300' }}">
                    <span class="h-2 w-2 rounded-full {{ $healthy ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $engineLabel }}{{ $status['model'] ? ' · '.$status['model'] : '' }}
                </p>
                <ul class="mt-3 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>{{ $status['stt_local'] ? '✓' : '–' }} Telinga lokal (Whisper)</li>
                    <li>{{ $status['tts_local'] ? '✓' : '–' }} Suara lokal (Piper)</li>
                </ul>
                @if ($status['reason'])
                    <p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-400/10 dark:text-amber-200">{{ $status['reason'] }}<br><span class="font-mono">{{ $status['fix'] }}</span></p>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ([['Sesi dimulai hari ini', $stats['started']], ['Sesi selesai', $stats['finished']], ['Rata-rata band', $stats['average'] !== null ? number_format($stats['average'], 1) : '–']] as [$label, $value])
                <div class="rounded-3xl border border-black/5 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-extrabold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-[1.4fr_1fr]">
            <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-bold">Peringkat hari ini</p>
                @include('booth.partials.leaders', ['leaders' => $leaders])
            </div>
            <div class="rounded-3xl border border-black/5 bg-white p-6 text-center dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm font-bold">Mulai dari HP pengunjung</p>
                <div id="start-qr" data-url="{{ $startUrl }}" class="mx-auto mt-3 h-40 w-40"></div>
                <p class="mt-2 break-all text-xs text-slate-500">{{ $startUrl }}</p>
            </div>
        </div>
    </div>
    @include('booth.partials.qr', ['target' => 'start-qr'])
</x-exam-layout>
