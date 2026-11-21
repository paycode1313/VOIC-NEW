<x-app-layout title="Riwayat">
    <div class="mx-auto max-w-6xl px-4 pb-12 pt-5 sm:px-6 sm:pt-8"
         x-data="{
            async remove(id, url) {
                if (! confirm('Hapus sesi ini beserta transkripnya?')) return;
                const response = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                if (response.ok) document.getElementById('session-' + id)?.remove();
            }
         }">

        {{-- Executive Header --}}
        <section class="relative overflow-hidden rounded-[1.75rem] bg-navy-950 p-6 text-white shadow-card ring-1 ring-white/10 sm:rounded-[2rem] sm:p-7">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand-600/25 blur-3xl" aria-hidden="true"></div>
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/[0.07] px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-sky-300">
                        Arsip Evaluasi &amp; Transkrip
                    </span>
                    <h1 class="mt-2.5 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Riwayat latihan</h1>
                    <p class="mt-1 text-xs text-slate-300/85 sm:text-sm">Total <span class="font-bold text-white">{{ $sessions->total() }}</span> sesi simulasi tersimpan di akunmu</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('practice.export') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.07] px-4 py-2.5 text-xs font-bold text-white backdrop-blur transition hover:bg-white/15">
                        <svg class="h-4 w-4 text-sky-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        <span>Unduh CSV</span>
                    </a>
                    <a href="{{ route('exam.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-xs font-bold text-white shadow-glow-blue transition hover:brightness-110">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span>Sesi Baru</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- Structured Filter Bar --}}
        <form method="GET" action="{{ route('practice.history') }}"
              class="mt-5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs sm:rounded-3xl sm:p-5">
            <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]">
                <div class="relative">
                    <label class="sr-only" for="search">Cari</label>
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </span>
                    <input id="search" name="search" type="search" value="{{ $currentSearch }}"
                           placeholder="Cari topik, mode, atau kesimpulan AI…"
                           class="w-full rounded-xl border border-slate-200/90 bg-slate-50/70 py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10" />
                </div>
                <div class="grid grid-cols-2 gap-2.5 sm:contents">
                    <div>
                        <label class="sr-only" for="role">Mode</label>
                        <select id="role" name="role"
                                class="w-full rounded-xl border border-slate-200/90 bg-slate-50/70 px-3.5 py-2.5 text-sm font-medium text-slate-700 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10">
                            <option value="">Semua mode</option>
                            @foreach (['dosen_penguji' => 'Sidang Skripsi', 'hrd' => 'Wawancara Kerja', 'investor' => 'Pitching Startup'] as $value => $label)
                                <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sr-only" for="min_score">Skor minimum</label>
                        <select id="min_score" name="min_score"
                                class="w-full rounded-xl border border-slate-200/90 bg-slate-50/70 px-3.5 py-2.5 text-sm font-medium text-slate-700 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10">
                            <option value="">Semua skor</option>
                            @foreach ([55 => 'Band ≥ 5.0', 72 => 'Band ≥ 6.5', 88 => 'Band ≥ 8.0'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) $currentMinScore === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 sm:flex-initial">
                        Terapkan
                    </button>
                    @if ($currentSearch || $currentRole || $currentMinScore)
                        <a href="{{ route('practice.history') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Session List --}}
        <div class="mt-5 space-y-3">
            @forelse ($sessions as $session)
                @php
                    $report = $session->feedback_notes['exam_report'] ?? null;
                    $band = $report['overall_band'] ?? null;
                    $scoreColor = $band !== null
                        ? ($band >= 7 ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80' : ($band >= 5.5 ? 'bg-brand-50 text-brand-700 border-brand-200/80' : 'bg-amber-50 text-amber-700 border-amber-200/80'))
                        : 'bg-slate-50 text-slate-700 border-slate-200/80';
                @endphp
                <div id="session-{{ $session->id }}"
                     class="group flex flex-col justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs transition hover:border-slate-300 hover:shadow-card sm:flex-row sm:items-center sm:rounded-3xl sm:p-5">
                    <div class="flex min-w-0 items-start gap-4 sm:items-center">
                        {{-- Score Badge --}}
                        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl border {{ $scoreColor }}">
                            <span class="text-base font-extrabold tabular-nums leading-none">
                                {{ $band !== null ? number_format($band, 1) : ($session->isCoaching() ? 'COACH' : ($session->isExam() ? '…' : number_format($session->overall_score, 0))) }}
                            </span>
                            <span class="mt-1 text-[9px] font-bold uppercase tracking-wider opacity-75">
                                {{ $band !== null ? 'Band' : ($session->isCoaching() ? 'Mode' : ($session->isExam() ? 'Proses' : 'Skor')) }}
                            </span>
                        </div>

                        {{-- Session Details --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-base font-extrabold tracking-tight text-slate-900">{{ $session->scenario_type }}</p>
                                @php
                                    // The examiner's current name (config/exam.php); the ai_roles table still has the old ones ("VOIC-HRD").
                                    $examinerName = $session->exam_mode ? config('exam.modes.'.$session->exam_mode.'.examiner.name') : null;
                                @endphp
                                @if ($examinerName || $session->aiRole)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">{{ $examinerName ?? $session->aiRole->name }}</span>
                                @endif
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-500">
                                <span>{{ $session->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                                @if ($session->duration_seconds)
                                    <span>·</span>
                                    <span class="tabular-nums">{{ intdiv($session->duration_seconds, 60) }}:{{ str_pad((string) ($session->duration_seconds % 60), 2, '0', STR_PAD_LEFT) }} mnt</span>
                                @endif
                            </p>
                            @if ($session->ai_conclusion)
                                <p class="mt-1.5 line-clamp-1 text-xs text-slate-500">{{ $session->ai_conclusion }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 pt-3 sm:border-t-0 sm:pt-0">
                        <a href="{{ $session->reportUrl() }}"
                           class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-brand-600 sm:flex-initial">
                            <span>Buka Rapor</span>
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                        <button type="button"
                                @click="remove({{ $session->id }}, @js(route('practice.destroy', $session)))"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                                aria-label="Hapus sesi">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-2xs">
                    <div class="mx-auto mb-3.5 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <p class="text-base font-extrabold text-slate-900">Belum ada sesi yang cocok</p>
                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">Coba ubah filter pencarian di atas, atau mulai sesi latihan baru.</p>
                    <a href="{{ route('exam.index') }}"
                       class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-glow-blue transition hover:bg-brand-700">
                        <span>Mulai latihan</span>
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $sessions->links() }}</div>
    </div>
</x-app-layout>
