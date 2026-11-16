<x-app-layout title="Riwayat">
    <div class="mx-auto max-w-5xl px-4 pb-10 pt-6 sm:px-6 sm:pt-8"
         x-data="{
            async remove(id, url) {
                if (! confirm('Hapus sesi ini beserta transkripnya?')) return;
                const response = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                if (response.ok) document.getElementById('session-' + id)?.remove();
            }
         }">

        {{-- Header --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Riwayat latihan</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $sessions->total() }} sesi tersimpan</p>
            </div>
            <a href="{{ route('practice.export') }}"
               class="inline-flex items-center gap-1.5 rounded-full border border-black/10 px-4 py-2 text-sm font-semibold hover:bg-black/5 active:bg-black/10 dark:border-white/15 dark:hover:bg-white/10">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                CSV
            </a>
        </div>

        {{-- Filter form --}}
        <form method="GET" action="{{ route('practice.history') }}"
              class="mt-5 rounded-2xl border border-black/5 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03] sm:rounded-3xl">
            <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]">
                <div>
                    <label class="sr-only" for="search">Cari</label>
                    <x-text-input id="search" name="search" type="search" :value="$currentSearch"
                                  placeholder="Cari topik atau kesimpulan…" class="w-full" />
                </div>
                <div class="grid grid-cols-2 gap-2 sm:contents">
                    <div>
                        <label class="sr-only" for="role">Mode</label>
                        <select id="role" name="role"
                                class="w-full rounded-xl border-black/10 bg-[#f6f6f3] px-3 py-2.5 text-sm focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                            <option value="">Semua mode</option>
                            @foreach (['dosen_penguji' => 'Sidang Skripsi', 'hrd' => 'Wawancara Kerja', 'investor' => 'Pitching Startup'] as $value => $label)
                                <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sr-only" for="min_score">Skor minimum</label>
                        <select id="min_score" name="min_score"
                                class="w-full rounded-xl border-black/10 bg-[#f6f6f3] px-3 py-2.5 text-sm focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                            <option value="">Semua skor</option>
                            @foreach ([55 => 'Band ≥ 5', 72 => 'Band ≥ 6.5', 88 => 'Band ≥ 8'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) $currentMinScore === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <x-primary-button class="sm:self-start">Terapkan</x-primary-button>
            </div>
        </form>

        {{-- Session list --}}
        <div class="mt-4 space-y-2.5">
            @forelse ($sessions as $session)
                @php
                    $report = $session->feedback_notes['exam_report'] ?? null;
                    $band = $report['overall_band'] ?? null;
                @endphp
                <div id="session-{{ $session->id }}"
                     class="flex items-stretch gap-0 rounded-2xl border border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.03] sm:rounded-3xl overflow-hidden">
                    {{-- Score badge (left side) --}}
                    <div class="flex w-14 shrink-0 flex-col items-center justify-center border-r border-black/5 dark:border-white/5 bg-[#f6f6f3]/60 dark:bg-white/[0.015] px-1">
                        <span class="text-base font-extrabold leading-none">
                            {{ $band !== null ? number_format($band, 1) : ($session->isCoaching() ? '💬' : ($session->isExam() ? '…' : number_format($session->overall_score, 0))) }}
                        </span>
                        <span class="mt-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                            {{ $band !== null ? 'band' : ($session->isCoaching() ? 'coach' : ($session->isExam() ? 'proses' : 'skor')) }}
                        </span>
                    </div>
                    {{-- Info --}}
                    <div class="flex min-w-0 flex-1 flex-col justify-center px-4 py-3">
                        <p class="truncate font-semibold text-[15px]">{{ $session->scenario_type }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $session->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                            @if ($session->duration_seconds) · {{ intdiv($session->duration_seconds, 60) }}:{{ str_pad((string) ($session->duration_seconds % 60), 2, '0', STR_PAD_LEFT) }} @endif
                        </p>
                        @if ($session->ai_conclusion)
                            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $session->ai_conclusion }}</p>
                        @endif
                    </div>
                    {{-- Actions --}}
                    <div class="flex shrink-0 flex-col items-stretch divide-y divide-black/5 dark:divide-white/5">
                        <a href="{{ $session->reportUrl() }}"
                           class="flex flex-1 items-center justify-center px-4 text-xs font-bold text-slate-900 hover:bg-black/[0.03] active:bg-black/[0.07] dark:text-white dark:hover:bg-white/5">
                            Rapor
                        </a>
                        <button type="button"
                                @click="remove({{ $session->id }}, @js(route('practice.destroy', $session)))"
                                class="flex flex-1 items-center justify-center px-4 text-xs font-semibold text-slate-400 hover:text-rose-600 hover:bg-rose-50 active:bg-rose-100 dark:hover:bg-rose-500/10"
                                aria-label="Hapus sesi">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-black/10 p-10 text-center dark:border-white/15 sm:rounded-3xl">
                    <p class="text-2xl mb-3">🔍</p>
                    <p class="font-bold">Belum ada sesi yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter, atau mulai latihan baru.</p>
                    <a href="{{ route('exam.index') }}"
                       class="mt-5 inline-flex items-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 active:bg-slate-700 dark:bg-white dark:text-slate-900">
                        Mulai latihan
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $sessions->links() }}</div>
    </div>
</x-app-layout>
