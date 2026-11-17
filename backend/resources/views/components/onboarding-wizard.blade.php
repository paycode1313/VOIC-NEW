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
     class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 p-4 backdrop-blur-sm sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="onboarding-title">
    <div @click.outside="isOpen = false" class="w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white p-6 shadow-2xl sm:p-8">
        {{-- Top Step Header + Progress Bar --}}
        <div class="flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-700">
                Langkah <span x-text="step"></span> dari 3
            </span>
            <button type="button" @click="isOpen = false" class="rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="mt-3 flex gap-1.5" aria-hidden="true">
            <template x-for="i in 3" :key="i">
                <span class="h-1.5 flex-1 rounded-full transition-colors duration-200" :class="i <= step ? 'bg-brand-600' : 'bg-slate-100'"></span>
            </template>
        </div>

        <h2 id="onboarding-title" class="mt-4 text-xl font-extrabold tracking-tight text-slate-900">Panduan Orientasi &amp; Personalisasi</h2>

        <div x-show="step === 1" class="mt-5">
            <p class="text-sm font-semibold text-slate-600">Apa fokus simulasi yang sedang kamu persiapkan?</p>
            <div class="mt-3.5 grid gap-2.5">
                @foreach ([
                    'sidang_skripsi' => ['Sidang skripsi', 'Latihan mempertahankan metodologi & hasil riset'],
                    'wawancara_kerja' => ['Wawancara kerja', 'Latihan menjawab HR recruiter dengan metode STAR'],
                    'pitching_startup' => ['Pitching startup', 'Latihan mempresentasikan model bisnis ke investor'],
                    'public_speaking' => ['Sekadar latihan bicara', 'Melatih kelancaran, tempo, dan rasa percaya diri'],
                ] as $value => [$label, $sub])
                    <button type="button" @click="goal = '{{ $value }}'"
                            :class="goal === '{{ $value }}' ? 'border-brand-600 bg-brand-50/60 ring-2 ring-brand-500/20' : 'border-slate-200/90 hover:border-slate-300 hover:bg-slate-50/60'"
                            class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3.5 text-left transition">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $sub }}</p>
                        </div>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                              :class="goal === '{{ $value }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'">
                            <svg x-show="goal === '{{ $value }}'" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="step === 2" x-cloak class="mt-5">
            <p class="text-sm font-semibold text-slate-600">Seberapa sering kamu bicara di depan penguji atau audiens?</p>
            <div class="mt-3.5 grid gap-2.5">
                @foreach ([
                    'pemula' => ['Jarang, masih sering gugup', 'Penguji akan memberi ruang adaptasi di awal sesi'],
                    'menengah' => ['Kadang-kadang, cukup percaya diri', 'Tantangan seimbang dengan pertanyaan lanjutan'],
                    'mahir' => ['Sering, ingin diuji lebih keras', 'Pertanyaan kritis untuk menguji kedalaman argumen'],
                ] as $value => [$label, $sub])
                    <button type="button" @click="experience = '{{ $value }}'"
                            :class="experience === '{{ $value }}' ? 'border-brand-600 bg-brand-50/60 ring-2 ring-brand-500/20' : 'border-slate-200/90 hover:border-slate-300 hover:bg-slate-50/60'"
                            class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3.5 text-left transition">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $sub }}</p>
                        </div>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                              :class="experience === '{{ $value }}' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'">
                            <svg x-show="experience === '{{ $value }}'" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="step === 3" x-cloak class="mt-5 space-y-3 text-sm text-slate-600">
            <p class="font-bold text-slate-900">Cara kerja simulasi VOIC</p>
            <div class="space-y-2.5">
                @foreach ([
                    ['1', 'Penguji AI bicara duluan. Tahan tombol mikrofon (atau Spasi) saat menjawab.'],
                    ['2', 'Tiga bagian terstruktur: Pemanasan, Kartu Topik 2 menit, dan Diskusi mendalam.'],
                    ['3', 'Rapor instan dengan Band Score 0–9.0 di empat kriteria serta saran perbaikan per jawaban.'],
                ] as [$num, $text])
                    <div class="flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-extrabold text-white">{{ $num }}</span>
                        <p class="text-xs leading-relaxed text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <p class="rounded-2xl bg-brand-50/70 p-3 text-xs font-medium text-brand-900 ring-1 ring-brand-500/15">
                Tips: Gunakan headset di ruang yang tenang. Kamera bersifat opsional dan diproses langsung di perangkatmu untuk analisis kontak mata.
            </p>
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
            <button type="button" x-show="step > 1" @click="step--" class="text-sm font-bold text-slate-500 hover:text-slate-900">Kembali</button>
            <span x-show="step === 1"></span>
            <button type="button" x-show="step < 3" @click="step++" class="rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-glow-blue transition hover:brightness-105">Lanjut</button>
            <button type="button" x-show="step === 3" @click="finish()" :disabled="saving" class="rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-glow-blue transition hover:brightness-105 disabled:opacity-60">Mulai latihan pertama</button>
        </div>
    </div>
</div>
