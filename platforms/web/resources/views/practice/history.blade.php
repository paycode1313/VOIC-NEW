<x-app-layout>
    <div class="pb-16 pt-4 sm:pt-8">

        {{-- Page Header --}}
        <div class="pt-6 sm:pt-10 pb-6 text-center max-w-5xl mx-auto px-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-200/70 dark:bg-white/5 border border-slate-300 dark:border-white/10 text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-4 transition-colors">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400 animate-pulse"></span>
                Riwayat Simulasi
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-[1.15] transition-colors">
                Riwayat
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-blue-300 dark:via-indigo-200 dark:to-slate-200">
                    Sesi Latihan
                </span>
            </h1>

            <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-slate-400 max-w-lg mx-auto leading-relaxed transition-colors">
                Seluruh catatan performa kamu tersimpan aman. Cari, filter, dan pelajari progres dari waktu ke waktu.
            </p>
        </div>

        <div class="max-w-5xl mx-auto px-4 space-y-6">

            {{-- Search & Filter Bar --}}
            <form method="GET" action="{{ route('practice.history') }}" class="rounded-2xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 p-4 sm:p-5 backdrop-blur-xl shadow-lg dark:shadow-2xl transition-colors">
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Search Input --}}
                    <div class="flex-1 relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               name="search"
                               value="{{ $currentSearch }}"
                               placeholder="Cari skenario atau topik..."
                               class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition" />
                    </div>

                    {{-- Role Filter --}}
                    <select name="role"
                            class="w-full sm:w-44 py-2.5 px-3 text-sm rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition appearance-none cursor-pointer">
                        <option value="">Semua Karakter</option>
                        @foreach($roleOptions as $role)
                            <option value="{{ $role->role_type }}" {{ $currentRole === $role->role_type ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Min Score Filter --}}
                    <select name="min_score"
                            class="w-full sm:w-36 py-2.5 px-3 text-sm rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition appearance-none cursor-pointer">
                        <option value="">Semua Skor</option>
                        <option value="80" {{ $currentMinScore == '80' ? 'selected' : '' }}>Skor ≥ 80</option>
                        <option value="65" {{ $currentMinScore == '65' ? 'selected' : '' }}>Skor ≥ 65</option>
                        <option value="50" {{ $currentMinScore == '50' ? 'selected' : '' }}>Skor ≥ 50</option>
                    </select>

                    {{-- Submit & Reset --}}
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold text-white dark:text-slate-900 bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-200 rounded-xl transition shadow-sm cursor-pointer">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filter
                        </button>

                        @if($currentSearch || $currentRole || $currentMinScore)
                            <a href="{{ route('practice.history') }}"
                               class="inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Results Summary --}}
            <div class="flex items-center justify-between px-1">
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    @if($sessions->total() > 0)
                        Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari <span class="font-bold text-slate-700 dark:text-slate-300">{{ $sessions->total() }}</span> sesi
                    @else
                        Tidak ditemukan sesi latihan
                    @endif
                </p>

                @if($sessions->total() > 0)
                    <a href="{{ route('practice.export') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Ekspor CSV
                    </a>
                @endif
            </div>

            {{-- Session Cards --}}
            @if($sessions->isEmpty())
                <div class="rounded-3xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 p-12 text-center backdrop-blur-xl shadow-xl dark:shadow-2xl transition-colors">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tidak Ada Hasil</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 max-w-sm mx-auto">
                        @if($currentSearch || $currentRole || $currentMinScore)
                            Coba ubah filter pencarian atau <a href="{{ route('practice.history') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">reset semua filter</a>.
                        @else
                            Kamu belum memiliki sesi latihan.
                            <a href="{{ route('practice.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">Mulai simulasi pertama</a> sekarang!
                        @endif
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($sessions as $session)
                        <div id="session-card-{{ $session->id }}" class="group rounded-2xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 hover:border-slate-300 dark:hover:border-white/20 p-4 sm:p-5 backdrop-blur-xl shadow-md dark:shadow-xl transition-all duration-200 hover:shadow-lg dark:hover:shadow-2xl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                {{-- Left: Session Info --}}
                                <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                    {{-- Score Badge --}}
                                    <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-black text-sm
                                        {{ $session->overall_score >= 80
                                            ? 'bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400'
                                            : ($session->overall_score >= 65
                                                ? 'bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-600 dark:text-amber-400'
                                                : 'bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400') }}">
                                        {{ number_format($session->overall_score, 0) }}
                                    </div>

                                    {{-- Details --}}
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                                {{ $session->scenario_type }}
                                            </h3>
                                            @if($session->aiRole)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider
                                                    {{ $session->aiRole->role_type === 'dosen_penguji'
                                                        ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20'
                                                        : ($session->aiRole->role_type === 'hrd'
                                                            ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20'
                                                            : 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20') }}">
                                                    {{ $session->aiRole->name }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                {{ $session->created_at->format('d M Y, H:i') }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 font-mono">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                            </span>
                                            @php
                                                $feedback = is_array($session->feedback_notes) ? $session->feedback_notes : [];
                                                $lang = strtoupper($feedback['language'] ?? 'ID');
                                            @endphp
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10">
                                                {{ $lang }}
                                            </span>
                                        </div>

                                        {{-- Score Bars (Face & Voice) --}}
                                        <div class="flex items-center gap-4 mt-2.5">
                                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Wajah: <span class="text-slate-700 dark:text-slate-300">{{ number_format($session->face_score, 0) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                                </svg>
                                                Suara: <span class="text-slate-700 dark:text-slate-300">{{ number_format($session->voice_score, 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right: Actions --}}
                                <div class="flex items-center gap-2 shrink-0 sm:flex-col sm:items-end">
                                    <a href="{{ route('practice.show', $session) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-white dark:hover:text-white bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-600 dark:hover:bg-blue-600 border border-blue-200 dark:border-blue-500/20 hover:border-blue-600 dark:hover:border-blue-600 rounded-xl transition-all shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Lihat Rapor
                                    </a>

                                    <button type="button"
                                            onclick="confirmDeleteSession({{ $session->id }}, '{{ addslashes($session->scenario_type) }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 bg-transparent hover:bg-rose-50 dark:hover:bg-rose-500/10 border border-transparent hover:border-rose-200 dark:hover:border-rose-500/20 rounded-xl transition-all opacity-0 group-hover:opacity-100 focus:opacity-100 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            {{-- AI Conclusion Snippet --}}
                            @if($session->ai_conclusion)
                                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-white/5">
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed italic">
                                        "{{ Str::limit($session->ai_conclusion, 180) }}"
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($sessions->hasPages())
                    <div class="pt-4">
                        <div class="rounded-2xl bg-white dark:bg-[#0b121e]/90 border border-slate-200 dark:border-white/10 px-4 py-3 backdrop-blur-xl shadow-md">
                            {{ $sessions->links() }}
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </div>

    {{-- Delete Confirmation Modal & Script --}}
    <div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div onclick="closeDeleteModal()" class="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-[#0b121e] border border-slate-200 dark:border-white/10 p-6 shadow-2xl transform transition-all">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.734 0L4.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Sesi Latihan?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Sesi "<span id="delete-session-name" class="font-semibold text-slate-700 dark:text-slate-300"></span>" akan dihapus permanen beserta seluruh riwayat percakapan.
                </p>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button"
                        onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        id="confirm-delete-btn"
                        onclick="executeDelete()"
                        class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm cursor-pointer">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
        let pendingDeleteSessionId = null;

        function confirmDeleteSession(sessionId, sessionName) {
            pendingDeleteSessionId = sessionId;
            document.getElementById('delete-session-name').textContent = sessionName;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            pendingDeleteSessionId = null;
        }

        function executeDelete() {
            if (!pendingDeleteSessionId) return;

            const btn = document.getElementById('confirm-delete-btn');
            btn.textContent = 'Menghapus...';
            btn.disabled = true;

            fetch(`/practice/${pendingDeleteSessionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById(`session-card-${pendingDeleteSessionId}`);
                    if (card) {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(-20px)';
                        setTimeout(() => card.remove(), 300);
                    }
                    closeDeleteModal();
                } else {
                    alert('Gagal menghapus sesi. Silakan coba lagi.');
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan. Pastikan koneksi stabil dan coba lagi.');
            })
            .finally(() => {
                btn.textContent = 'Ya, Hapus';
                btn.disabled = false;
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
    </script>
</x-app-layout>
