@php
    $isEn = $language === 'en';
    $prep = (int) config('exam.prep_seconds');
    $talk = (int) config('exam.long_turn_seconds');
    $glow = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'][$mode['accent']] ?? '99 102 241';
    $examinerName = $mode['examiner']['name'];
    $topics = array_map(fn ($topic) => $blueprint->text($topic, $language), $mode['topics'] ?? []);
    $user = auth()->user();
    $durations = [
        ['parts' => [1, 2, 3], 'label' => $isEn ? 'Full mock' : 'Simulasi penuh', 'meta' => $isEn ? '3 parts · ~12 min' : '3 bagian · ~12 menit'],
        ['parts' => [1], 'label' => $isEn ? 'Quick warm-up' : 'Pemanasan cepat', 'meta' => $isEn ? 'Part 1 only · ~4 min' : 'Bagian 1 saja · ~4 menit'],
        ['parts' => [3], 'label' => $isEn ? 'Deep questions' : 'Pertanyaan mendalam', 'meta' => $isEn ? 'Part 3 only · ~4 min' : 'Bagian 3 saja · ~4 menit'],
    ];
    $wizard = [
        'step' => $errors->any() ? 5 : 1,
        'name' => old('guest_name', $user ? \Illuminate\Support\Str::of($user->name)->explode(' ')->first() : ''),
        'campus' => old('campus', ''),
        'type' => old('session_type', $sessionType),
        'topic' => old('topic', ''),
        'parts' => array_map('intval', (array) old('parts', $selectedParts)),
        'submitting' => false,
    ];
@endphp

{{-- Setup as a short wizard: type your name and campus once, everything else is one tap per step. --}}
<x-exam-layout :title="$blueprint->text($mode['title'], $language)" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto max-w-2xl px-4 pb-20 pt-8 sm:px-6 sm:pt-12" style="--glow: {{ $glow }}"
         x-data="{
            ...@js($wizard),
            get last() { return this.type === 'exam' ? 5 : 4 },
            next() { if (this.step === 1 && !this.$refs.name.reportValidity()) return; if (this.step === 1 && !this.$refs.campus.reportValidity()) return; this.step = Math.min(this.step + 1, this.last); window.scrollTo({ top: 0, behavior: 'smooth' }) },
            back() { this.step = Math.max(1, this.step - 1) },
            pick(field, value) { this[field] = value; this.next() },
            same(a, b) { return JSON.stringify(a) === JSON.stringify(b) },
         }">

        {{-- Who you are about to call --}}
        <div class="flex items-center gap-4">
            <div class="relative isolate flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#121521]">
                <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(circle,rgb(var(--glow)/0.6),transparent_70%)]"></div>
                <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-12 w-12 rounded-full object-cover">
                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#121521]"></span>
            </div>
            <div class="min-w-0">
                <p class="text-lg font-extrabold tracking-tight">{{ $examinerName }}</p>
                <p class="text-sm font-semibold" style="color: rgb(var(--glow))">{{ $blueprint->text($mode['examiner']['title'], $language) }}</p>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mt-6 flex items-center gap-1.5" aria-hidden="true">
            <template x-for="i in last" :key="i">
                <span class="h-1.5 flex-1 rounded-full transition-colors duration-300" :style="i <= step ? 'background: rgb(var(--glow))' : ''" :class="i <= step ? '' : 'bg-black/10 dark:bg-white/10'"></span>
            </template>
        </div>
        <p class="mt-2 text-xs font-semibold text-slate-500" x-text="'{{ $isEn ? 'Step' : 'Langkah' }} ' + step + ' / ' + last"></p>

        <form method="POST" action="{{ route('exam.start', $modeKey) }}" class="mt-4" @submit="submitting = true">
            @csrf
            <input type="hidden" name="language" value="{{ $language }}">
            <input type="hidden" name="session_type" :value="type">
            <input type="hidden" name="topic" :value="topic">
            <template x-for="p in (type === 'exam' ? parts : [1, 2, 3])" :key="p"><input type="hidden" name="parts[]" :value="p"></template>
            @if ($focus)
                <input type="hidden" name="focus" value="{{ $focus }}">
            @endif

            {{-- 1. Name and campus: the only typing --}}
            <section x-show="step === 1" x-transition.opacity>
                <h1 class="text-3xl font-extrabold tracking-tight">{{ $isEn ? "Let's get to know you" : 'Kenalan dulu, yuk' }}</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">{{ $isEn ? "{$examinerName} will greet you by name." : "{$examinerName} bakal manggil kamu pakai nama ini." }}</p>
                <div class="mt-6 space-y-4 rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                    <div>
                        <label for="guest_name" class="block text-sm font-semibold">{{ $isEn ? 'Your name' : 'Nama panggilan' }}</label>
                        <input id="guest_name" @guest name="guest_name" @endguest x-ref="name" x-model="name" type="text" maxlength="100" required autocomplete="given-name"
                               @keydown.enter.prevent="$refs.campus.focus()" @if ($user) readonly @endif
                               placeholder="{{ $isEn ? 'e.g. Rifai' : 'mis. Rifai' }}"
                               class="mt-2 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 read-only:opacity-70 dark:border-white/10 dark:bg-white/5">
                        <x-input-error :messages="$errors->get('guest_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="campus" class="block text-sm font-semibold">{{ $isEn ? 'Campus / school' : 'Kampus / sekolah' }}</label>
                        <input id="campus" name="campus" x-ref="campus" x-model="campus" type="text" maxlength="120" required autocomplete="organization"
                               @keydown.enter.prevent="next()"
                               placeholder="{{ $isEn ? 'e.g. Universitas Brawijaya' : 'mis. Universitas Brawijaya' }}"
                               class="mt-2 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                        <x-input-error :messages="$errors->get('campus')" class="mt-2" />
                    </div>
                </div>
                <button type="button" @click="next()" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full px-6 py-4 text-base font-bold text-white transition hover:brightness-110" style="background: rgb(var(--glow))">
                    {{ $isEn ? 'Next' : 'Lanjut' }}
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                </button>
            </section>

            {{-- 2. Coach or mock exam --}}
            <section x-show="step === 2" x-cloak x-transition.opacity>
                <h2 class="text-3xl font-extrabold tracking-tight" x-text="'{{ $isEn ? 'Hi' : 'Hai' }} ' + name + ', {{ $isEn ? 'what kind of call?' : 'mau latihan yang mana?' }}'"></h2>
                <div class="mt-6 grid gap-3">
                    @foreach ([
                        'coach' => [$isEn ? 'Chat with a coach' : 'Ngobrol dengan coach', $isEn ? config('exam.coach_minutes').' min · relaxed, no score' : config('exam.coach_minutes').' menit · santai, tanpa nilai', $isEn ? 'You get tips on how to answer and which words to use.' : 'Kamu diajari cara menjawab dan milih kata, sambil ngobrol.', '💬'],
                        'exam' => [$isEn ? 'Mock exam' : 'Simulasi ujian', $isEn ? 'Feels like the real thing · report with scores' : 'Rasa ujian sungguhan · ada rapor nilai', $isEn ? 'Afterwards you get a band score and feedback per answer.' : 'Setelahnya kamu dapat band score dan masukan per jawaban.', '🎯'],
                    ] as $value => [$label, $meta, $body, $emoji])
                        <button type="button" @click="pick('type', '{{ $value }}')"
                                class="flex items-start gap-4 rounded-3xl border bg-white p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/[0.03]"
                                :class="type === '{{ $value }}' ? 'border-[rgb(var(--glow))] ring-2 ring-[rgb(var(--glow)/0.3)]' : 'border-black/10 dark:border-white/10'">
                            <span class="text-3xl" aria-hidden="true">{{ $emoji }}</span>
                            <span>
                                <span class="block text-lg font-bold">{{ $label }}</span>
                                <span class="block text-xs font-semibold text-slate-500">{{ $meta }}</span>
                                <span class="mt-1 block text-sm text-slate-600 dark:text-slate-400">{{ $body }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- 3. Topic --}}
            <section x-show="step === 3" x-cloak x-transition.opacity>
                <h2 class="text-3xl font-extrabold tracking-tight">{{ $blueprint->text($mode['topic_label'], $language) }}?</h2>
                <p class="mt-2 text-slate-600 dark:text-slate-400">{{ $isEn ? 'Pick the closest one, the questions will fit it.' : 'Pilih yang paling dekat, pertanyaannya bakal menyesuaikan.' }}</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($topics as $topic)
                        <button type="button" @click="pick('topic', @js($topic))"
                                class="rounded-full border px-4 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5"
                                :class="topic === @js($topic) ? 'border-transparent text-white' : 'border-black/10 bg-white hover:border-black/30 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-white/30'"
                                :style="topic === @js($topic) ? 'background: rgb(var(--glow))' : ''">{{ $topic }}</button>
                    @endforeach
                    <button type="button" @click="pick('topic', '')"
                            class="rounded-full border border-dashed px-4 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5"
                            :class="topic === '' ? 'border-transparent text-white' : 'border-black/20 text-slate-600 hover:border-black/40 dark:border-white/20 dark:text-slate-300'"
                            :style="topic === '' ? 'background: rgb(var(--glow))' : ''">{{ $isEn ? 'Anything / general' : 'Bebas / umum' }}</button>
                </div>
            </section>

            {{-- 4. Length (mock exam only) --}}
            <section x-show="type === 'exam' && step === 4" x-cloak x-transition.opacity>
                <h2 class="text-3xl font-extrabold tracking-tight">{{ $isEn ? 'How long?' : 'Mau berapa lama?' }}</h2>
                <div class="mt-6 grid gap-3">
                    @foreach ($durations as $option)
                        <button type="button" @click="pick('parts', @js($option['parts']))"
                                class="flex items-center justify-between rounded-3xl border bg-white p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/[0.03]"
                                :class="same(parts, @js($option['parts'])) ? 'border-[rgb(var(--glow))] ring-2 ring-[rgb(var(--glow)/0.3)]' : 'border-black/10 dark:border-white/10'">
                            <span>
                                <span class="block text-lg font-bold">{{ $option['label'] }}</span>
                                <span class="block text-sm text-slate-500">{{ $option['meta'] }}</span>
                            </span>
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @endforeach
                </div>
                @if ($focus)
                    <p class="mt-4 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-white dark:bg-white dark:text-slate-900">{{ $isEn ? 'Targeted practice: the parts that train your focus are pre-selected.' : 'Latihan terarah: bagian yang melatih fokusmu sudah dipilihkan.' }}</p>
                @endif
            </section>

            {{-- Last step: summary, consent, call --}}
            <section x-show="step === last" x-cloak x-transition.opacity>
                <h2 class="text-3xl font-extrabold tracking-tight">{{ $isEn ? 'Ready to call?' : 'Siap ditelepon?' }}</h2>
                <div class="mt-6 divide-y divide-black/5 rounded-3xl border border-black/5 bg-white text-sm dark:divide-white/10 dark:border-white/10 dark:bg-white/[0.03]">
                    @foreach ([
                        [1, $isEn ? 'Name' : 'Nama', "name + ' · ' + campus"],
                        [2, $isEn ? 'Type' : 'Jenis', "type === 'coach' ? '".($isEn ? 'Chat with a coach' : 'Ngobrol dengan coach')."' : '".($isEn ? 'Mock exam' : 'Simulasi ujian')."'"],
                        [3, $isEn ? 'Topic' : 'Topik', "topic || '".($isEn ? 'Anything / general' : 'Bebas / umum')."'"],
                    ] as [$goto, $label, $expression])
                        <button type="button" @click="step = {{ $goto }}" class="flex w-full items-center justify-between gap-4 px-5 py-3.5 text-left hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">
                            <span class="text-slate-500">{{ $label }}</span>
                            <span class="flex items-center gap-2 font-semibold"><span x-text="{{ $expression }}"></span><span class="text-xs font-normal text-slate-400">{{ $isEn ? 'change' : 'ubah' }}</span></span>
                        </button>
                    @endforeach
                    <button type="button" x-show="type === 'exam'" @click="step = 4" class="flex w-full items-center justify-between gap-4 px-5 py-3.5 text-left hover:bg-black/[0.02] dark:hover:bg-white/[0.03]">
                        <span class="text-slate-500">{{ $isEn ? 'Length' : 'Durasi' }}</span>
                        <span class="flex items-center gap-2 font-semibold"><span x-text="parts.length === 3 ? '{{ $isEn ? 'Full mock' : 'Simulasi penuh' }}' : '{{ $isEn ? 'Part' : 'Bagian' }} ' + parts.join(', ')"></span><span class="text-xs font-normal text-slate-400">{{ $isEn ? 'change' : 'ubah' }}</span></span>
                    </button>
                </div>

                <label class="mt-4 flex items-start gap-3 rounded-2xl border border-black/5 bg-white p-4 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                           class="mt-0.5 h-4 w-4 rounded border-black/20 text-slate-900 focus:ring-slate-900">
                    <span class="text-slate-600 dark:text-slate-400">
                        @if (config('exam.speed_mode') === 'online' || \App\Services\Exam\ExaminerService::isCloudModel(config('exam.ai_model')))
                            {{ $isEn
                                ? 'I agree that the text of my answers is sent to an online AI to reply, that the examiner voice is made by an online voice service, and that my answers are stored to build the report.'
                                : 'Saya setuju teks jawaban saya dikirim ke AI online untuk dibalas, suara penguji dibuat layanan suara online, dan jawaban saya disimpan untuk membuat rapor.' }}
                        @else
                            {{ $isEn
                                ? 'I agree that my voice is processed by the AI on this laptop (not sent to the internet), and that my answers are stored to build the report.'
                                : 'Saya setuju suara saya diproses oleh AI di laptop ini (tidak dikirim ke internet) dan jawaban saya disimpan untuk membuat rapor.' }}
                        @endif
                        @guest
                            {{ $isEn ? 'Guest data is deleted automatically after '.config('exam.retention_days').' days.' : 'Data tamu dihapus otomatis setelah '.config('exam.retention_days').' hari.' }}
                        @endguest
                    </span>
                </label>
                <x-input-error :messages="$errors->get('consent')" class="mt-2" />

                <button type="submit" :disabled="submitting"
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full px-6 py-4 text-base font-bold text-white shadow-lg transition hover:brightness-110 disabled:opacity-60" style="background: rgb(var(--glow))">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    <span x-show="!submitting">{{ $isEn ? "Call {$examinerName}" : "Telepon {$examinerName}" }}</span>
                    <span x-show="submitting" x-cloak>{{ $isEn ? 'Connecting…' : 'Menyambungkan…' }}</span>
                </button>
                <p class="mt-3 text-center text-xs text-slate-500">{{ $isEn ? "Use Chrome or Edge and allow the microphone. Part 2: {$prep}s to prepare, up to ".($talk / 60).' min to talk.' : "Pakai Chrome atau Edge dan izinkan mikrofon. Bagian 2: {$prep} detik persiapan, bicara sampai ".($talk / 60).' menit.' }}</p>

                @php $studyCode = old('study_code', session('study_code')); @endphp
                <details class="mt-6 text-sm" @if ($studyCode) open @endif>
                    <summary class="cursor-pointer text-center font-semibold text-slate-400">{{ $isEn ? 'Have a trial participant code?' : 'Punya kode peserta uji coba?' }}</summary>
                    <input name="study_code" type="text" maxlength="20" value="{{ $studyCode }}" autocomplete="off"
                           placeholder="{{ $isEn ? 'e.g. P01' : 'mis. P01' }}"
                           class="mt-3 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] uppercase placeholder:normal-case focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                </details>
            </section>

            {{-- Back --}}
            <button type="button" x-show="step > 1" x-cloak @click="back()" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                {{ $isEn ? 'Back' : 'Kembali' }}
            </button>
        </form>
    </div>
</x-exam-layout>
