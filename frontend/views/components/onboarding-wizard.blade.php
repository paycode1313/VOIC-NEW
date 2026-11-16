@props([
    'showOnInit' => request()->routeIs('dashboard') && Auth::check() && ! (Auth::user()?->has_completed_onboarding ?? false),
])

{{-- Panduan Orientasi & Personalisasi: three short steps, then straight into the matching practice mode. --}}
<div x-data="{
        isOpen: false,
        step: 1,
        saving: false,
        initialOpen: @js($showOnInit),
        goal: @js(Auth::user()?->onboarding_goal ?: 'sidang_skripsi'),
        experience: @js(Auth::user()?->onboarding_experience ?: 'pemula'),
        init() { this.isOpen = this.initialOpen },
        async finish() {
            this.saving = true;
            try {
                const response = await fetch(@js(route('onboarding.complete')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ onboarding_goal: this.goal, onboarding_experience: this.experience }),
                });
                const data = await response.json();
                window.location.href = data.redirect_url;
            } catch (e) {
                this.saving = false;
                this.isOpen = false;
            }
        }
     }"
     @open-onboarding.window="isOpen = true; step = 1"
     @keydown.escape.window="isOpen = false"
     x-show="isOpen" x-cloak
     class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 backdrop-blur-sm sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="onboarding-title">
    <div @click.outside="isOpen = false" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-xl dark:bg-[#16171b] sm:p-8">
        <div class="flex items-center justify-between">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langkah <span x-text="step"></span> dari 3</p>
            <button type="button" @click="isOpen = false" class="rounded-full p-1 text-slate-400 hover:bg-black/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <h2 id="onboarding-title" class="mt-2 text-xl font-extrabold tracking-tight">Panduan Orientasi & Personalisasi</h2>

        <div x-show="step === 1" class="mt-5">
            <p class="text-sm font-semibold">Apa yang sedang kamu persiapkan?</p>
            <div class="mt-3 grid gap-2">
                @foreach (['sidang_skripsi' => ['🎓', 'Sidang skripsi'], 'wawancara_kerja' => ['💼', 'Wawancara kerja'], 'pitching_startup' => ['🚀', 'Pitching startup'], 'public_speaking' => ['🎙️', 'Sekadar latihan bicara']] as $value => [$icon, $label])
                    <button type="button" @click="goal = '{{ $value }}'"
                            :class="goal === '{{ $value }}' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-black/10 hover:border-black/30 dark:border-white/10 dark:hover:border-white/30'"
                            class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition">
                        <span aria-hidden="true">{{ $icon }}</span>{{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="step === 2" class="mt-5">
            <p class="text-sm font-semibold">Seberapa sering kamu bicara di depan penguji atau audiens?</p>
            <div class="mt-3 grid gap-2">
                @foreach (['pemula' => 'Jarang, masih sering gugup', 'menengah' => 'Kadang-kadang, cukup percaya diri', 'mahir' => 'Sering, ingin diuji lebih keras'] as $value => $label)
                    <button type="button" @click="experience = '{{ $value }}'"
                            :class="experience === '{{ $value }}' ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-black/10 hover:border-black/30 dark:border-white/10 dark:hover:border-white/30'"
                            class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div x-show="step === 3" class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-400">
            <p class="font-semibold text-slate-900 dark:text-white">Cara kerjanya</p>
            <p><span class="font-bold text-slate-900 dark:text-white">1.</span> Penguji AI bicara duluan. Jawab dengan suara, kirim otomatis saat kamu jeda sebentar.</p>
            <p><span class="font-bold text-slate-900 dark:text-white">2.</span> Tiga bagian: pemanasan, kartu topik 2 menit, lalu diskusi mendalam. Penguji menanggapi isi jawabanmu.</p>
            <p><span class="font-bold text-slate-900 dark:text-white">3.</span> Dapat rapor band score di empat kriteria, plus contoh jawaban yang lebih kuat.</p>
            <p class="rounded-2xl bg-[#f6f6f3] p-3 text-xs dark:bg-white/5">Tips: pakai headset dan tempat yang tenang. Kamera opsional, hanya untuk mengukur kontak mata di perangkatmu.</p>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button type="button" x-show="step > 1" @click="step--" class="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">Kembali</button>
            <span x-show="step === 1"></span>
            <button type="button" x-show="step < 3" @click="step++" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">Lanjut</button>
            <button type="button" x-show="step === 3" @click="finish()" :disabled="saving" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60 dark:bg-white dark:text-slate-900">Mulai latihan pertama</button>
        </div>
    </div>
</div>
