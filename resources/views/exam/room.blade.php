@php
    $isEn = $language === 'en';
    $accent = config('exam.accents')[$mode['accent']] ?? config('exam.accents.indigo');
    $roomConfig = [
        'language' => $language,
        'initialState' => $initialState,
        'transcript' => $transcript->map(fn ($m) => ['id' => $m->id, 'sender' => $m->sender, 'message' => $m->message, 'exam_part' => $m->exam_part])->values(),
        'answerUrl' => route('exam.answer', $session),
        'finishUrl' => route('exam.finish', $session),
        'statusUrl' => route('exam.status'),
    ];
@endphp

<x-exam-layout :title="$blueprint->text($mode['title'], $language)" :full-height="true">
    <div x-data="examRoom(@js($roomConfig))" class="mx-auto flex h-full max-w-6xl flex-col px-4 pb-4 sm:px-6">

        {{-- Part stepper --}}
        <div class="flex shrink-0 items-center justify-between gap-3 py-3">
            <ol class="flex min-w-0 items-center gap-1.5 overflow-x-auto text-xs font-semibold sm:text-sm">
                <template x-for="(p, i) in state.parts" :key="p.number">
                    <li class="flex shrink-0 items-center gap-1.5">
                        <span x-show="i > 0" class="h-px w-4 bg-black/15 dark:bg-white/15 sm:w-8"></span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1"
                              :class="p.status === 'current' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : (p.status === 'done' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400')">
                            <template x-if="p.status === 'done'"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                            <span x-text="'{{ $isEn ? 'Part' : 'Bagian' }} ' + p.number"></span>
                            <span class="hidden md:inline" x-text="'· ' + p.title"></span>
                        </span>
                    </li>
                </template>
            </ol>
            <button type="button" @click="if (confirm(t('Akhiri sesi dan lihat laporan dari jawaban sejauh ini?', 'End the session and see the report for answers so far?'))) finish()"
                    x-show="started" x-cloak
                    class="shrink-0 rounded-full border border-black/10 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-black/5 dark:border-white/15 dark:text-slate-300 dark:hover:bg-white/10">
                {{ $isEn ? 'End session' : 'Akhiri sesi' }}
            </button>
        </div>
        <div class="h-1 w-full shrink-0 overflow-hidden rounded-full bg-black/5 dark:bg-white/10">
            <div class="h-full rounded-full bg-slate-900 transition-all duration-700 dark:bg-white" :style="`width: ${state.progress.percent}%`"></div>
        </div>

        <div class="mt-4 grid min-h-0 flex-1 gap-4 lg:grid-cols-[1fr_320px]">

            {{-- Stage --}}
            <section class="relative flex min-h-0 flex-col overflow-hidden rounded-3xl border border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.03]">

                {{-- Pre-start: mic check --}}
                <div x-show="!started" class="flex flex-1 flex-col items-center justify-center overflow-y-auto p-6 text-center">
                    <img src="{{ asset($mode['examiner']['avatar']) }}" alt="" class="h-24 w-24 rounded-full object-cover">
                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight">{{ $isEn ? 'Ready when you are' : 'Siap kalau kamu siap' }}</h1>
                    <p class="mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">
                        {{ $isEn
                            ? "{$mode['examiner']['name']} will speak first. Answer out loud; when you stop talking for a moment your answer is sent automatically."
                            : "{$mode['examiner']['name']} akan bicara duluan. Jawab dengan suara; saat kamu berhenti bicara sejenak, jawaban terkirim otomatis." }}
                    </p>

                    <div class="mt-6 w-full max-w-xs">
                        <button type="button" @click="checkMic()" x-show="!micReady && !typingMode"
                                class="w-full rounded-full border border-black/10 px-5 py-3 text-sm font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">
                            🎙️ {{ $isEn ? 'Test microphone' : 'Tes mikrofon' }}
                        </button>
                        <div x-show="micReady" x-cloak class="rounded-2xl bg-[#f6f6f3] p-4 text-left dark:bg-white/5">
                            <p class="text-xs font-semibold text-slate-500">{{ $isEn ? 'Say something, the bar should move' : 'Coba bicara, bar harus bergerak' }}</p>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-black/10 dark:bg-white/10">
                                <div class="h-full rounded-full bg-emerald-500 transition-[width] duration-75" :style="`width: ${micLevel}%`"></div>
                            </div>
                        </div>
                        <p x-show="!speechSupported" x-cloak class="mt-3 text-xs text-amber-600">{{ $isEn ? 'This browser has no speech recognition. You will type your answers.' : 'Browser ini tidak mendukung pengenalan suara. Kamu akan menjawab dengan mengetik.' }}</p>
                        <button type="button" @click="begin()"
                                class="mt-4 w-full rounded-full bg-slate-900 px-6 py-3.5 text-base font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            {{ $isEn ? 'Start the exam' : 'Mulai ujian' }}
                        </button>
                    </div>
                </div>

                {{-- Live stage --}}
                <div x-show="started" x-cloak class="flex min-h-0 flex-1 flex-col">
                    <div class="flex flex-1 flex-col items-center justify-center overflow-y-auto px-5 py-6 text-center sm:px-10">
                        <div class="relative">
                            <span class="absolute inset-0 rounded-full {{ $accent['solid'] }} opacity-30" :class="status === 'speaking' && 'animate-ping'"></span>
                            <img src="{{ asset($mode['examiner']['avatar']) }}" alt="{{ $mode['examiner']['name'] }}"
                                 class="relative h-20 w-20 rounded-full object-cover ring-4 transition sm:h-24 sm:w-24"
                                 :class="status === 'speaking' ? 'ring-emerald-400' : 'ring-transparent'">
                        </div>
                        <p class="mt-3 text-sm font-semibold">{{ $mode['examiner']['name'] }}</p>
                        <span x-show="engine" x-cloak class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                              :class="engine === 'offline_composer' ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300'"
                              :title="engine === 'offline_composer' ? t('Ollama / ai_service.py belum aktif dan tidak ada API key cloud. Penguji memakai jawaban cadangan berbasis aturan.', 'Ollama / ai_service.py is not running and no cloud key is set. The examiner uses rule-based fallback replies.') : ''">
                            <span class="h-1.5 w-1.5 rounded-full" :class="engine === 'offline_composer' ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                            <span x-text="{ local_llm: t('AI lokal aktif', 'Local AI on'), cloud_llm: t('AI cloud aktif', 'Cloud AI on'), offline_composer: t('AI offline · mode cadangan', 'AI offline · fallback mode') }[engine]"></span>
                        </span>
                        <p class="text-xs text-slate-500" x-text="{
                            speaking: t('Sedang berbicara…', 'Speaking…'),
                            listening: t('Mendengarkan kamu', 'Listening to you'),
                            long_turn: t('Mendengarkan, tidak akan menyela', 'Listening, will not interrupt'),
                            thinking: t('Sedang berpikir…', 'Thinking…'),
                            prep: t('Waktu persiapan', 'Preparation time'),
                            finishing: t('Menyusun laporan…', 'Building your report…'),
                            error: t('Ada kendala', 'Something went wrong'),
                            ready: ''
                        }[status]"></p>

                        {{-- Cue card --}}
                        <template x-if="cueCard && (status === 'prep' || status === 'long_turn')">
                            <div class="mt-6 w-full max-w-xl rounded-2xl border-2 border-dashed border-black/15 bg-[#fbfbf9] p-5 text-left dark:border-white/15 dark:bg-white/[0.02]">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $isEn ? 'Topic card' : 'Kartu topik' }}</span>
                                    <span class="rounded-full px-3 py-1 font-mono text-sm font-bold"
                                          :class="status === 'prep' ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300'"
                                          x-text="(status === 'prep' ? t('Persiapan ', 'Prep ') : t('Bicara ', 'Talk ')) + countdownLabel"></span>
                                </div>
                                <p class="mt-3 text-lg font-bold leading-snug" x-text="cueCard.prompt"></p>
                                <p class="mt-3 text-sm text-slate-500">{{ $isEn ? 'You should say:' : ($mode['role_type'] === 'hrd' ? 'Yang perlu kamu sampaikan:' : 'Yang perlu Anda sampaikan:') }}</p>
                                <ul class="mt-1.5 space-y-1 text-sm">
                                    <template x-for="point in cueCard.points" :key="point">
                                        <li class="flex gap-2"><span class="text-slate-400">•</span><span x-text="point"></span></li>
                                    </template>
                                </ul>
                                <div x-show="status === 'prep'" class="mt-4">
                                    <textarea x-model="notes" rows="3" placeholder="{{ $isEn ? 'Jot down keywords (only you can see this)…' : 'Tulis kata kunci (hanya kamu yang lihat)…' }}"
                                              class="w-full rounded-xl border-black/10 bg-white text-sm focus:border-slate-900 focus:ring-slate-900 dark:border-white/10 dark:bg-white/5"></textarea>
                                    <button type="button" @click="startLongTurn()" class="mt-2 w-full rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                                        {{ $isEn ? "I'm ready, start talking" : 'Saya siap, mulai bicara' }}
                                    </button>
                                </div>
                                <p x-show="status === 'long_turn' && notes" class="mt-3 whitespace-pre-wrap rounded-xl bg-black/5 p-3 text-xs text-slate-600 dark:bg-white/5 dark:text-slate-300" x-text="notes"></p>
                            </div>
                        </template>

                        {{-- Examiner subtitle --}}
                        <p x-show="!(cueCard && (status === 'prep' || status === 'long_turn'))"
                           class="mt-6 max-w-2xl text-lg font-semibold leading-relaxed tracking-tight sm:text-2xl" x-text="subtitle"></p>

                        <div x-show="status === 'thinking' || status === 'finishing'" class="mt-5 flex gap-1.5" aria-hidden="true">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:120ms]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:240ms]"></span>
                        </div>
                    </div>

                    {{-- Answer dock --}}
                    <div class="shrink-0 border-t border-black/5 p-4 dark:border-white/10">
                        <p x-show="errorMessage" x-cloak class="mb-3 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="errorMessage"></p>

                        <template x-if="!typingMode">
                            <div class="flex items-center gap-3">
                                <div class="min-h-[3rem] flex-1 rounded-2xl bg-[#f6f6f3] px-4 py-3 text-[15px] dark:bg-white/5">
                                    <span x-show="liveAnswer" x-text="finalText"></span>
                                    <span x-show="interim" class="text-slate-400" x-text="' ' + interim"></span>
                                    <span x-show="!liveAnswer" class="text-slate-400" x-text="(status === 'listening' || status === 'long_turn') ? t('Silakan mulai bicara…', 'Start speaking…') : t('Mikrofon aktif setelah penguji selesai bicara', 'The mic opens after the examiner finishes')"></span>
                                </div>
                                <button type="button" @click="submitAnswer()" :disabled="!(status === 'listening' || status === 'long_turn')"
                                        class="relative inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-white transition disabled:opacity-40"
                                        :class="(status === 'listening' || status === 'long_turn') ? 'bg-rose-500 hover:bg-rose-600' : 'bg-slate-900 dark:bg-white dark:text-slate-900'"
                                        :aria-label="t('Selesai menjawab', 'Done answering')" :title="t('Selesai menjawab', 'Done answering')">
                                    <span x-show="status === 'listening' || status === 'long_turn'" class="absolute inset-0 animate-ping rounded-full bg-rose-500/40"></span>
                                    <svg class="relative h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg>
                                </button>
                            </div>
                        </template>

                        <template x-if="typingMode">
                            <form @submit.prevent="status === 'error' ? retry() : submitAnswer()" class="flex items-end gap-3">
                                <textarea x-model="typed" rows="2" :disabled="!(status === 'listening' || status === 'long_turn' || status === 'error')"
                                          @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); status === 'error' ? retry() : submitAnswer() }"
                                          placeholder="{{ $isEn ? 'Type your answer, Enter to send' : 'Ketik jawabanmu, Enter untuk kirim' }}"
                                          class="flex-1 resize-none rounded-2xl border-black/10 bg-[#f6f6f3] px-4 py-3 text-[15px] focus:border-slate-900 focus:ring-slate-900 disabled:opacity-50 dark:border-white/10 dark:bg-white/5"></textarea>
                                <button type="submit" :disabled="!typed.trim()" class="h-12 shrink-0 rounded-full bg-slate-900 px-5 text-sm font-bold text-white disabled:opacity-40 dark:bg-white dark:text-slate-900">
                                    {{ $isEn ? 'Send' : 'Kirim' }}
                                </button>
                            </form>
                        </template>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                            <div class="flex items-center gap-3">
                                <button type="button" @click="repeatQuestion()" :disabled="status !== 'listening'" class="font-semibold hover:text-slate-900 disabled:opacity-40 dark:hover:text-white">↻ {{ $isEn ? 'Repeat question' : 'Ulangi pertanyaan' }}</button>
                                <button type="button" x-show="speechSupported" @click="typingMode = !typingMode; if (!typingMode && status === 'listening') startListening()" class="font-semibold hover:text-slate-900 dark:hover:text-white"
                                        x-text="typingMode ? t('🎙️ Jawab dengan suara', '🎙️ Answer by voice') : t('⌨️ Ketik saja', '⌨️ Type instead')"></button>
                            </div>
                            <span x-show="status === 'long_turn' && !typingMode">{{ $isEn ? 'Tap the mic when you are done' : 'Tekan mikrofon jika sudah selesai' }}</span>
                            <span x-show="status === 'listening' && !typingMode">{{ $isEn ? 'Pause ~3s or tap the mic to send' : 'Diam ~3 detik atau tekan mikrofon untuk kirim' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Side panel --}}
            <aside class="hidden min-h-0 flex-col gap-4 lg:flex">
                <div class="overflow-hidden rounded-3xl border border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="relative aspect-video bg-slate-100 dark:bg-black/40">
                        <video x-ref="video" x-show="cameraOn" muted playsinline class="h-full w-full -scale-x-100 object-cover"></video>
                        <div x-show="!cameraOn" class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center text-xs text-slate-500">
                            <p>{{ $isEn ? 'Camera off. Turn it on to measure eye contact.' : 'Kamera mati. Nyalakan untuk mengukur kontak mata.' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 text-xs">
                        <span class="text-slate-500">{{ $isEn ? 'Processed on your device' : 'Diproses di perangkatmu' }}</span>
                        <button type="button" @click="toggleCamera()" class="rounded-full border border-black/10 px-3 py-1 font-semibold hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10" x-text="cameraOn ? t('Matikan', 'Turn off') : t('Nyalakan', 'Turn on')"></button>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col rounded-3xl border border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="shrink-0 px-4 pt-4 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $isEn ? 'Transcript' : 'Transkrip' }}</p>
                    <div x-ref="transcript" class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4 text-sm">
                        <template x-for="item in transcript" :key="item.id">
                            <div :class="item.sender === 'user' ? 'text-right' : ''">
                                <p class="mb-0.5 text-[11px] font-semibold text-slate-400" x-text="item.sender === 'user' ? t('Kamu', 'You') : '{{ $mode['examiner']['name'] }}'"></p>
                                <p class="inline-block max-w-[92%] rounded-2xl px-3 py-2 text-left"
                                   :class="item.sender === 'user' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-[#f6f6f3] dark:bg-white/5'"
                                   x-text="item.message"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-exam-layout>
