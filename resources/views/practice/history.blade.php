<x-app-layout title="Riwayat">
    <div class="mx-auto max-w-5xl px-4 pb-20 pt-8 sm:px-6"
         x-data="{
            async remove(id, url) {
                if (! confirm('Hapus sesi ini beserta transkripnya?')) return;
                const response = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                if (response.ok) document.getElementById('session-' + id)?.remove();
            }
         }">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Riwayat latihan</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $sessions->total() }} sesi tersimpan</p>
            </div>
            <a href="{{ route('practice.export') }}" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">Unduh CSV</a>
        </div>

        <form method="GET" action="{{ route('practice.history') }}" class="mt-6 grid gap-3 rounded-3xl border border-black/5 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03] sm:grid-cols-[1fr_auto_auto_auto]">
            <label class="sr-only" for="search">Cari</label>
            <x-text-input id="search" name="search" type="search" :value="$currentSearch" placeholder="Cari topik atau kesimpulan…" class="w-full" />
            <label class="sr-only" for="role">Mode</label>
            <select id="role" name="role" class="rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-2.5 text-sm focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                <option value="">Semua mode</option>
                @foreach (['dosen_penguji' => 'Sidang Skripsi', 'hrd' => 'Wawancara Kerja', 'investor' => 'Pitching Startup'] as $value => $label)
                    <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="min_score">Skor minimum</label>
            <select id="min_score" name="min_score" class="min-w-[9.5rem] rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-2.5 text-sm focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                <option value="">Semua skor</option>
                @foreach ([55 => 'Band ≥ 5', 72 => 'Band ≥ 6.5', 88 => 'Band ≥ 8'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $currentMinScore === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-primary-button>Terapkan</x-primary-button>
        </form>

        <div class="mt-4 space-y-3">
            @forelse ($sessions as $session)
                @php
                    $report = $session->feedback_notes['exam_report'] ?? null;
                    $band = $report['overall_band'] ?? null;
                @endphp
                <div id="session-{{ $session->id }}" class="flex items-center gap-4 rounded-3xl border border-black/5 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="inline-flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-2xl bg-[#f6f6f3] dark:bg-white/5">
                        <span class="text-sm font-extrabold">{{ $band !== null ? number_format($band, 1) : ($session->isCoaching() ? '💬' : ($session->isExam() ? '…' : number_format($session->overall_score, 0))) }}</span>
                        <span class="text-[9px] font-semibold uppercase text-slate-400">{{ $band !== null ? 'band' : ($session->isCoaching() ? 'coach' : ($session->isExam() ? 'proses' : 'skor')) }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $session->scenario_type }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $session->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                            @if ($session->duration_seconds) · {{ intdiv($session->duration_seconds, 60) }}:{{ str_pad((string) ($session->duration_seconds % 60), 2, '0', STR_PAD_LEFT) }} menit @endif
                        </p>
                        @if ($session->ai_conclusion)
                            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $session->ai_conclusion }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <a href="{{ $session->reportUrl() }}" class="rounded-full bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">Lihat rapor</a>
                        <button type="button" @click="remove({{ $session->id }}, @js(route('practice.destroy', $session)))" class="rounded-full px-3 py-2 text-xs font-semibold text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10" aria-label="Hapus sesi">Hapus</button>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-black/10 p-10 text-center dark:border-white/15">
                    <p class="font-bold">Belum ada sesi yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter, atau mulai latihan baru.</p>
                    <a href="{{ route('exam.index') }}" class="mt-4 inline-block rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">Mulai latihan</a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $sessions->links() }}</div>
    </div>
</x-app-layout>
