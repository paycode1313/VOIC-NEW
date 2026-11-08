@php
    $isEn = $language === 'en';
    $accent = config('exam.accents')[$mode['accent']] ?? config('exam.accents.indigo');
    $prep = (int) config('exam.prep_seconds');
    $talk = (int) config('exam.long_turn_seconds');
@endphp

<x-exam-layout :title="$blueprint->text($mode['title'], $language)" :back="route('exam.index', $isEn ? ['lang' => 'en'] : [])">
    <div class="mx-auto grid max-w-5xl gap-8 px-4 pb-20 pt-8 sm:px-6 lg:grid-cols-[1fr_340px] lg:pt-12">
        <form method="POST" action="{{ route('exam.start', $modeKey) }}" class="space-y-6"
              x-data="{ parts: [1, 2, 3], submitting: false, toggle(n) { this.parts.includes(n) ? (this.parts.length > 1 && (this.parts = this.parts.filter(p => p !== n))) : this.parts.push(n) } }"
              @submit="submitting = true">
            @csrf
            <input type="hidden" name="language" value="{{ $language }}">

            <div>
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $accent['soft'] }}">
                    @include('exam.partials.icon', ['name' => $mode['icon'], 'class' => 'h-4 w-4'])
                    {{ $blueprint->text($mode['title'], $language) }}
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $isEn ? 'Set up your session' : 'Siapkan sesi latihanmu' }}</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">{{ $blueprint->text($mode['tagline'], $language) }}</p>
            </div>

            <div class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <label for="topic" class="block text-sm font-semibold">{{ $blueprint->text($mode['topic_label'], $language) }}</label>
                <input id="topic" name="topic" type="text" maxlength="150" value="{{ old('topic') }}"
                       placeholder="{{ $blueprint->text($mode['topic_placeholder'], $language) }}"
                       class="mt-2 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5 dark:focus:border-white dark:focus:ring-white">
                <x-input-error :messages="$errors->get('topic')" class="mt-2" />

                <label for="context_material" class="mt-5 block text-sm font-semibold">{{ $blueprint->text($mode['context_label'], $language) }}</label>
                <textarea id="context_material" name="context_material" rows="4" maxlength="3000"
                          class="mt-2 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5 dark:focus:border-white dark:focus:ring-white">{{ old('context_material') }}</textarea>
                <x-input-error :messages="$errors->get('context_material')" class="mt-2" />

                @guest
                    <label for="guest_name" class="mt-5 block text-sm font-semibold">{{ $isEn ? 'Your name' : 'Nama kamu' }}</label>
                    <input id="guest_name" name="guest_name" type="text" maxlength="100" value="{{ old('guest_name') }}"
                           placeholder="{{ $isEn ? 'So the examiner can greet you' : 'Biar penguji bisa menyapamu' }}"
                           class="mt-2 w-full rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5">
                @endguest
            </div>

            <fieldset class="rounded-3xl border border-black/5 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <legend class="sr-only">{{ $isEn ? 'Parts' : 'Bagian' }}</legend>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold">{{ $isEn ? 'Which parts do you want?' : 'Bagian yang ingin dilatih' }}</p>
                    <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white" @click="parts = [1, 2, 3]">{{ $isEn ? 'Full mock' : 'Simulasi penuh' }}</button>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($mode['parts'] as $number => $part)
                        <button type="button" @click="toggle({{ $number }})"
                                :class="parts.includes({{ $number }}) ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-900' : 'border-black/10 bg-transparent hover:border-black/30 dark:border-white/10 dark:hover:border-white/30'"
                                class="rounded-2xl border p-4 text-left transition" :aria-pressed="parts.includes({{ $number }})">
                            <span class="text-xs font-bold opacity-60">{{ $isEn ? 'Part' : 'Bagian' }} {{ $number }}</span>
                            <span class="mt-1 block font-bold">{{ $blueprint->text($part['title'], $language) }}</span>
                            <span class="mt-1 block text-xs opacity-70">{{ $blueprint->text($part['subtitle'], $language) }}</span>
                        </button>
                    @endforeach
                </div>
                <template x-for="p in parts" :key="p"><input type="hidden" name="parts[]" :value="p"></template>
            </fieldset>

            <label class="flex items-start gap-3 rounded-2xl border border-black/5 bg-white p-4 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                       class="mt-0.5 h-4 w-4 rounded border-black/20 text-slate-900 focus:ring-slate-900">
                <span class="text-slate-600 dark:text-slate-400">
                    {{ $isEn
                        ? 'I agree that my voice is processed by the AI on this laptop (not sent to the internet), and that my answers are stored to build the report.'
                        : 'Saya setuju suara saya diproses oleh AI di laptop ini (tidak dikirim ke internet) dan jawaban saya disimpan untuk membuat rapor.' }}
                    @guest
                        {{ $isEn
                            ? 'Guest data is deleted automatically after '.config('exam.retention_days').' days.'
                            : 'Data tamu dihapus otomatis setelah '.config('exam.retention_days').' hari.' }}
                    @endguest
                </span>
            </label>
            <x-input-error :messages="$errors->get('consent')" class="-mt-3" />

            <button type="submit" :disabled="submitting"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-slate-900 px-6 py-4 text-base font-bold text-white transition hover:bg-slate-800 disabled:opacity-60 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                <span x-show="!submitting">{{ $isEn ? 'Enter the exam room' : 'Masuk ruang ujian' }}</span>
                <span x-show="submitting" x-cloak>{{ $isEn ? 'Preparing…' : 'Menyiapkan…' }}</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
            </button>
        </form>

        <aside class="space-y-4">
            <div class="rounded-3xl border border-black/5 bg-white p-6 text-center dark:border-white/10 dark:bg-white/[0.03]">
                <img src="{{ asset($mode['examiner']['avatar']) }}" alt="{{ $mode['examiner']['name'] }}" class="mx-auto h-24 w-24 rounded-full object-cover">
                <p class="mt-3 font-bold">{{ $mode['examiner']['name'] }}</p>
                <p class="text-sm text-slate-500">{{ $blueprint->text($mode['examiner']['title'], $language) }}</p>
            </div>
            <div class="rounded-3xl border border-black/5 bg-white p-6 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                <p class="font-bold">{{ $isEn ? 'Before you start' : 'Sebelum mulai' }}</p>
                <ul class="mt-3 space-y-2.5 text-slate-600 dark:text-slate-400">
                    <li class="flex gap-2"><span aria-hidden="true">🎙️</span>{{ $isEn ? 'Use Chrome or Edge and allow the microphone.' : 'Pakai Chrome atau Edge dan izinkan mikrofon.' }}</li>
                    <li class="flex gap-2"><span aria-hidden="true">🤫</span>{{ $isEn ? 'Find a quiet spot, headphones help.' : 'Cari tempat tenang, headset sangat membantu.' }}</li>
                    <li class="flex gap-2"><span aria-hidden="true">⏱️</span>{{ $isEn ? "Part 2: {$prep}s to prepare, up to ".($talk / 60).' min to talk.' : "Bagian 2: {$prep} detik persiapan, bicara sampai ".($talk / 60).' menit.' }}</li>
                    <li class="flex gap-2"><span aria-hidden="true">📷</span>{{ $isEn ? 'Camera is optional, it measures eye contact on your device only.' : 'Kamera opsional, hanya mengukur kontak mata di perangkatmu.' }}</li>
                </ul>
            </div>
        </aside>
    </div>
</x-exam-layout>
