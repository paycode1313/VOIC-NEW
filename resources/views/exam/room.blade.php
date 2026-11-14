@php
    $isEn = $language === 'en';
    $examinerName = $mode['examiner']['name'];
    $examinerTitle = $blueprint->text($mode['examiner']['title'], $language);
    // RGB triplet for the glow behind the examiner (matches the mode accent).
    $glow = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'][$mode['accent']] ?? '99 102 241';
    $roomConfig = [
        'language' => $language,
        'initialState' => $initialState,
        'transcript' => $transcript->map(fn ($m) => ['id' => $m->id, 'sender' => $m->sender, 'message' => $m->message, 'exam_part' => $m->exam_part])->values(),
        'answerUrl' => route('exam.answer', $session),
        'finishUrl' => route('exam.finish', $session),
        'statusUrl' => route('exam.status'),
        'streamUrl' => route('exam.answer.stream', $session),
        'avatar' => [
            'image' => asset($mode['examiner']['avatar']),
            'rig' => $mode['examiner']['rig'] ?? null,
        ],
    ];
@endphp

{{-- The exam room looks and behaves like a video call: the examiner fills the screen, your camera is a
     small tile, what is said appears as live captions, and the controls sit in a bar at the bottom. --}}
<x-exam-layout :title="$blueprint->text($mode['title'], $language)" :full-height="true" :call="true">
    <div x-data="examRoom(@js($roomConfig))"
         x-init="panel = window.innerWidth >= 1280; setInterval(() => { if (started) elapsed++ }, 1000)"
         class="flex h-full flex-col" style="--glow: {{ $glow }}">

        {{-- ===================== Lobby (before joining) ===================== --}}
        <div x-show="!started" class="flex min-h-0 flex-1 flex-col">
            <div class="flex shrink-0 items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('exam.index', $isEn ? ['lang' => 'en'] : []) }}" class="flex shrink-0 items-center" aria-label="VOIC">
                    <img src="/images/logo/voic-logo-horizontal-dark.svg" alt="VOIC" class="h-7 w-auto" width="99" height="28">
                </a>
                <span class="text-xs text-white/65">{{ $blueprint->text($mode['title'], $language) }}</span>
            </div>

            <div class="mx-auto grid min-h-0 w-full max-w-6xl flex-1 items-center gap-6 overflow-y-auto px-4 pb-8 sm:px-6 lg:grid-cols-[1.4fr_1fr] lg:gap-10">
                {{-- Preview tile: the examiner is already in the room --}}
                <div class="relative isolate aspect-video w-full overflow-hidden rounded-3xl bg-[#121521] ring-1 ring-white/10">
                    <div class="pointer-events-none absolute inset-0 -z-10 [background:radial-gradient(60%_80%_at_50%_45%,rgb(var(--glow)/0.45),transparent_70%)]"></div>
                    <div class="flex h-full items-center justify-center">
                        <div class="relative">
                            <span class="voic-ring absolute inset-0 rounded-full border-2" style="border-color: rgb(var(--glow) / 0.5)"></span>
                            <img src="{{ asset($mode['examiner']['avatar']) }}" alt="{{ $examinerName }}" class="relative h-32 w-32 rounded-full object-cover shadow-2xl ring-4 ring-white/15 sm:h-44 sm:w-44">
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-4 rounded-lg bg-black/50 px-2.5 py-1 text-sm font-semibold backdrop-blur">{{ $examinerName }}</div>
                    <div class="absolute right-4 top-3 inline-flex items-center gap-1.5 rounded-full bg-black/50 px-2.5 py-1 text-xs font-semibold backdrop-blur">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        {{ $isEn ? 'Waiting for you' : 'Menunggumu bergabung' }}
                    </div>
                </div>

                {{-- Join panel --}}
                <div class="text-center lg:text-left">
                    <p class="text-sm font-semibold" style="color: rgb(var(--glow))">{{ $examinerTitle }}</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl"
                        x-text="isCoach ? t('Yuk ngobrol {{ config('exam.coach_minutes') }} menit', 'Let\'s chat for {{ config('exam.coach_minutes') }} minutes') : t('Siap bergabung?', 'Ready to join?')"></h1>
                    <p x-show="isCoach" x-cloak class="mt-3 text-sm leading-relaxed text-white/60">{{ $isEn ? 'This is not a test. Talk naturally, ask anything, and your coach will show you how to say it better.' : 'Ini bukan tes. Ngobrol aja senatural mungkin, boleh nanya apa pun, nanti coach kasih tahu cara ngomong yang lebih pas.' }}</p>
                    <p x-show="!isCoach" class="mt-3 text-sm leading-relaxed text-white/60">
                        {{ $isEn
                            ? "{$examinerName} speaks first. Answer out loud; when you pause for a moment, your answer is sent automatically."
                            : "{$examinerName} bakal ngomong duluan. Jawab pakai suara; kalau kamu diam sebentar, jawabanmu otomatis terkirim." }}
                    </p>

                    <div class="mx-auto mt-6 max-w-sm space-y-3 lg:mx-0">
                        <button type="button" @click="checkMic()" x-show="!micReady && !typingMode"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-white/10 px-5 py-3 text-sm font-semibold hover:bg-white/15">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg>
                            {{ $isEn ? 'Test microphone' : 'Tes mikrofon' }}
                        </button>
                        <div x-show="micReady" x-cloak class="rounded-2xl bg-white/5 p-4 text-left ring-1 ring-white/10">
                            <p class="text-xs font-semibold text-white/60">{{ $isEn ? 'Say something, the bar should move' : 'Coba ngomong, bar-nya harus gerak' }}</p>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-emerald-400 transition-[width] duration-75" :style="`width: ${micLevel}%`"></div>
                            </div>
                        </div>
                        <p x-show="!speechSupported" x-cloak class="text-xs text-amber-300">{{ $isEn ? 'This browser has no speech recognition. You will type your answers.' : 'Browser ini nggak bisa mengenali suara. Kamu jawab dengan mengetik ya.' }}</p>
                        <button type="button" @click="begin()"
                                class="flex w-full items-center justify-center gap-2 rounded-full px-6 py-3.5 text-base font-bold text-white shadow-lg transition hover:brightness-110"
                                style="background: rgb(var(--glow))">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                            <span x-text="isCoach ? t('Mulai ngobrol', 'Start chatting') : t('Gabung sekarang', 'Join now')"></span>
                        </button>
                        <p class="text-xs text-white/65">{{ $isEn ? 'Use Chrome or Edge. Headphones help.' : 'Pakai Chrome atau Edge. Headset bikin lebih jernih.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== In the call ===================== --}}
        <div x-show="started" x-cloak class="flex min-h-0 flex-1 flex-col">

            {{-- Top bar: who, which part, how long --}}
            <div class="flex shrink-0 items-center justify-between gap-3 px-3 py-2.5 sm:px-5">
                <div class="flex min-w-0 items-center gap-3">
                    <img src="/images/logo/voic-logo-mark.svg" alt="VOIC" class="h-6 w-6 shrink-0" width="24" height="24">
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-500/15 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-rose-300">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>Live
                    </span>
                    <span class="truncate text-sm font-semibold">{{ $examinerName }}</span>
                    <span class="hidden font-mono text-sm text-white/65 sm:inline" x-text="isCoach ? coachClock : (Math.floor(elapsed / 60) + ':' + String(elapsed % 60).padStart(2, '0'))"></span>
                </div>
                <ol x-show="!isCoach" class="flex min-w-0 items-center gap-1 overflow-x-auto text-xs font-semibold">
                    <template x-for="p in state.parts" :key="p.number">
                        <li class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1"
                            :class="p.status === 'current' ? 'bg-white text-slate-900' : (p.status === 'done' ? 'text-emerald-300' : 'text-white/65')">
                            <template x-if="p.status === 'done'"><svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                            <span x-text="'{{ $isEn ? 'Part' : 'Bagian' }} ' + p.number"></span>
                            <span class="hidden lg:inline" x-text="'· ' + p.title"></span>
                        </li>
                    </template>
                </ol>
                <span x-show="isCoach" x-cloak class="rounded-full px-2.5 py-1 text-xs font-semibold"
                      :class="coachRemaining <= 60 ? 'bg-amber-400/15 text-amber-300' : 'bg-white/10 text-white/70'"
                      x-text="state.part_title"></span>
            </div>

            <div class="flex min-h-0 flex-1 gap-3 px-3 sm:px-5">

                {{-- Main tile --}}
                <section class="relative isolate flex min-h-0 flex-1 flex-col overflow-hidden rounded-3xl bg-[#121521] ring-1 transition"
                         :class="status === 'speaking' ? 'ring-[rgb(var(--glow)/0.7)]' : (isListening ? 'ring-sky-400/50' : 'ring-white/10')">
                    <div class="pointer-events-none absolute inset-0 -z-10 transition-opacity duration-700 [background:radial-gradient(55%_70%_at_50%_42%,rgb(var(--glow)/0.5),transparent_72%)]"
                         :class="status === 'speaking' ? 'opacity-100' : 'opacity-40'"></div>

                    {{-- Examiner --}}
                    <div class="flex min-h-0 flex-1 items-center justify-center p-6 pb-24 sm:pb-28">
                        {{-- Live avatar: the canvas animates the portrait (resources/js/avatar.js); the photo is the fallback. --}}
                        <div class="relative h-36 w-36 sm:h-52 sm:w-52 xl:h-64 xl:w-64">
                            <span x-show="status === 'speaking'" x-cloak class="voic-ring absolute inset-0 rounded-full border-2" style="border-color: rgb(var(--glow) / 0.6)"></span>
                            <span x-show="status === 'speaking'" x-cloak class="voic-ring absolute inset-0 rounded-full border-2 [animation-delay:900ms]" style="border-color: rgb(var(--glow) / 0.6)"></span>
                            <div class="relative h-full w-full overflow-hidden rounded-full bg-slate-900 shadow-2xl shadow-black/60 ring-[3px] transition duration-300"
                                 :class="status === 'speaking' ? 'ring-white/90' : (isListening ? 'ring-sky-400/80' : 'ring-white/15')">
                                <img x-show="!avatarReady" src="{{ asset($mode['examiner']['avatar']) }}" alt="{{ $examinerName }}" class="h-full w-full object-cover">
                                <canvas x-ref="avatarCanvas" x-show="avatarReady" x-cloak class="h-full w-full" role="img" aria-label="{{ $examinerName }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Name plate + status, top left (captions own the bottom, your camera the top right) --}}
                    <div class="pointer-events-none absolute left-3 top-3 flex flex-wrap items-center gap-2 sm:left-4 sm:top-4">
                        <span class="hidden rounded-lg bg-black/50 px-2.5 py-1 text-sm font-semibold backdrop-blur sm:inline">{{ $examinerName }} <span class="font-normal text-white/65">· {{ $examinerTitle }}</span></span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-black/50 px-2.5 py-1 text-xs font-semibold backdrop-blur" aria-live="polite">
                            <span x-show="status === 'speaking'" class="flex h-3 items-end gap-[2px]" aria-hidden="true">
                                <span class="voic-eq w-[3px] rounded-full bg-white"></span>
                                <span class="voic-eq w-[3px] rounded-full bg-white [animation-delay:150ms]"></span>
                                <span class="voic-eq w-[3px] rounded-full bg-white [animation-delay:300ms]"></span>
                            </span>
                            <span x-show="isListening" x-cloak class="relative flex h-2 w-2" aria-hidden="true">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-400"></span>
                            </span>
                            <span x-show="status === 'thinking' || status === 'finishing'" x-cloak class="flex gap-0.5" aria-hidden="true">
                                <span class="h-1 w-1 animate-bounce rounded-full bg-white"></span>
                                <span class="h-1 w-1 animate-bounce rounded-full bg-white [animation-delay:120ms]"></span>
                                <span class="h-1 w-1 animate-bounce rounded-full bg-white [animation-delay:240ms]"></span>
                            </span>
                            <span x-text="{
                                speaking: t('Sedang bicara', 'Speaking'),
                                listening: vad.speaking ? t('Mendengarkan…', 'Listening…') : t('Giliranmu', 'Your turn'),
                                long_turn: t('Mendengarkan, nggak menyela', 'Listening, no interruptions'),
                                thinking: t('Lagi mikir…', 'Thinking…'),
                                prep: t('Waktu persiapan', 'Preparation'),
                                finishing: t('Menyusun rapor', 'Building report'),
                                error: t('Ada kendala', 'Something went wrong'),
                                ready: t('Siap', 'Ready')
                            }[status]"></span>
                        </span>
                    </div>

                    {{-- AI engine chip, under the name plate: details on demand --}}
                    <div x-data="{ open: false }" x-show="engine" x-cloak class="absolute left-3 top-12 z-20 text-left sm:left-4 sm:top-14">
                        <button type="button" @click="open = !open" :aria-expanded="open"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-black/50 px-2.5 py-1 text-xs font-semibold backdrop-blur hover:bg-black/70">
                            <span class="h-1.5 w-1.5 rounded-full" :class="engine === 'offline_composer' ? 'bg-amber-400' : 'bg-emerald-400'"></span>
                            <span x-text="{ local_llm: t('AI lokal', 'Local AI'), ollama_cloud: (aiStatus?.model || 'AI') + ' · cloud', openrouter: (aiStatus?.model || 'AI') + ' · cloud', cloud_llm: t('AI cloud', 'Cloud AI'), offline_composer: t('Mode cadangan', 'Fallback mode') }[engine]"></span>
                        </button>
                        <div x-show="open" x-transition.opacity @click.outside="open = false"
                             class="absolute left-0 mt-2 w-72 max-w-[80vw] rounded-2xl border border-white/10 bg-[#161a28]/95 p-3 text-xs text-white/80 shadow-xl backdrop-blur">
                            <p class="font-semibold text-white" x-text="engine === 'offline_composer'
                                ? t('AI belum aktif: penguji memakai jawaban cadangan.', 'AI is off: the examiner uses fallback replies.')
                                : t('AI berjalan normal.', 'AI is running normally.')"></p>
                            <template x-if="aiStatus?.reason">
                                <div class="mt-2 rounded-xl bg-amber-400/10 p-2 text-amber-200">
                                    <p x-text="aiStatus.reason"></p>
                                    <p x-show="aiStatus.fix" class="mt-1 break-words font-mono text-xs" x-text="aiStatus.fix"></p>
                                </div>
                            </template>
                            <p class="mt-2 text-white/60" x-text="t('Telinga: ', 'Ears: ') + (voiceMode === 'local' ? t('Whisper lokal', 'local Whisper') : voiceMode === 'browser' ? t('browser', 'browser') : t('ketik', 'typing'))
                                + (firstSentenceMs ? ' · ' + t('respon ', 'reply ') + (firstSentenceMs / 1000).toFixed(1) + t(' dtk', 's') : '')"></p>
                            <button type="button" class="mt-2 font-semibold text-white underline" @click="checkEngine()">{{ $isEn ? 'Check again' : 'Cek ulang' }}</button>
                        </div>
                    </div>

                    {{-- Cue card (Part 2), floating over the call --}}
                    <template x-if="cueCard && (status === 'prep' || status === 'long_turn')">
                        <div class="absolute inset-x-3 top-14 z-20 mx-auto max-w-lg rounded-2xl bg-white p-5 text-left text-slate-900 shadow-2xl sm:top-16">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $isEn ? 'Topic card' : 'Kartu topik' }}</span>
                                <span class="rounded-full px-3 py-1 font-mono text-sm font-bold"
                                      :class="status === 'prep' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                      x-text="(status === 'prep' ? t('Persiapan ', 'Prep ') : t('Bicara ', 'Talk ')) + countdownLabel"></span>
                            </div>
                            <p class="mt-3 text-lg font-bold leading-snug" x-text="cueCard.prompt"></p>
                            <p class="mt-3 text-sm text-slate-500">{{ $isEn ? 'You should say:' : ($mode['role_type'] === 'hrd' ? 'Yang perlu kamu sampaikan:' : 'Yang perlu Anda sampaikan:') }}</p>
                            <ul class="mt-1.5 space-y-1 text-sm">
                                <template x-for="point in cueCard.points" :key="point">
                                    <li class="flex gap-2"><span class="text-slate-500 dark:text-slate-400">•</span><span x-text="point"></span></li>
                                </template>
                            </ul>
                            <div x-show="status === 'prep'" class="mt-4">
                                <textarea x-model="notes" rows="3" placeholder="{{ $isEn ? 'Jot down keywords (only you can see this)…' : 'Tulis kata kunci (cuma kamu yang lihat)…' }}"
                                          class="w-full rounded-xl border-black/10 bg-slate-50 text-sm focus:border-slate-900 focus:ring-slate-900"></textarea>
                                <button type="button" @click="startLongTurn()" class="mt-2 w-full rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">
                                    {{ $isEn ? "I'm ready, start talking" : 'Aku siap, mulai bicara' }}
                                </button>
                            </div>
                            <p x-show="status === 'long_turn' && notes" class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-100 p-3 text-xs text-slate-600" x-text="notes"></p>
                        </div>
                    </template>

                    {{-- Live captions: what the examiner says, and what you are saying --}}
                    <div x-show="captions" class="pointer-events-none absolute inset-x-0 bottom-4 z-0 flex flex-col items-center gap-2 px-4 sm:bottom-5">
                        <p x-show="subtitle && !(cueCard && (status === 'prep' || status === 'long_turn'))"
                           class="max-w-3xl rounded-xl bg-black/65 px-4 py-2 text-center text-base font-medium leading-relaxed backdrop-blur sm:text-lg"
                           :class="status === 'speaking' ? 'opacity-100' : 'opacity-75'" x-text="subtitle"></p>
                        <p x-show="!typingMode && (liveAnswer || (voiceMode === 'local' && vad.speaking))" x-cloak
                           class="max-w-3xl rounded-xl bg-sky-500/20 px-4 py-1.5 text-center text-sm text-sky-100 ring-1 ring-sky-400/30 backdrop-blur">
                            <span class="font-semibold">{{ $isEn ? 'You' : 'Kamu' }}:</span>
                            <span x-text="voiceMode === 'local' ? t('merekam… ', 'recording… ') + recordSeconds + t(' dtk', 's') : finalText"></span>
                            <span class="text-sky-200/60" x-text="interim"></span>
                        </p>
                    </div>

                    {{-- Your camera, picture-in-picture --}}
                    <div class="absolute right-3 top-3 z-10 w-32 overflow-hidden rounded-2xl bg-[#1b1f2e] shadow-xl ring-1 transition sm:right-4 sm:top-4 sm:w-48 xl:w-56"
                         :class="vad.speaking ? 'ring-2 ring-sky-400' : 'ring-white/10'">
                        <div class="relative aspect-video">
                            <video x-ref="video" x-show="cameraOn" muted playsinline class="h-full w-full -scale-x-100 object-cover"></video>
                            <div x-show="!cameraOn" class="absolute inset-0 flex items-center justify-center">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold sm:h-12 sm:w-12">{{ $isEn ? 'You' : 'Kamu' }}</span>
                            </div>
                            <span x-show="cameraOn && expression" x-cloak x-text="expressionLabel()"
                                  :class="expression === 'tense' || expression === 'away' ? 'bg-amber-500/90' : 'bg-emerald-600/90'"
                                  class="absolute right-1.5 top-1.5 rounded-full px-2 py-0.5 text-xs font-semibold"></span>
                            <span class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 rounded-md bg-black/50 px-1.5 py-0.5 text-xs font-semibold">
                                <svg x-show="!isListening" class="h-3 w-3 text-rose-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 3l18 18M9 9v3a3 3 0 005.1 2.1M15 9.3V6a3 3 0 00-5.9-.8M19 11a7 7 0 01-1.1 3.8M5 11a7 7 0 0011 5.7M12 18v3"/></svg>
                                <span x-show="isListening" x-cloak class="flex h-2.5 items-end gap-[2px]" aria-hidden="true">
                                    <template x-for="i in 3" :key="i">
                                        <span class="w-[2px] rounded-full bg-sky-300 transition-all duration-75" :style="`height: ${Math.max(3, Math.min(10, micLevel * (0.25 + i * 0.12)))}px`"></span>
                                    </template>
                                </span>
                                {{ $isEn ? 'You' : 'Kamu' }}
                            </span>
                        </div>
                    </div>
                </section>

                {{-- Side panel: transcript (like the chat panel of a call) --}}
                <aside x-show="panel" x-cloak x-transition.opacity
                       class="fixed inset-y-0 right-0 z-30 flex w-full max-w-sm flex-col bg-[#121521] ring-1 ring-white/10 sm:inset-y-3 sm:right-3 sm:rounded-3xl xl:static xl:w-80 xl:shrink-0">
                    <div class="flex shrink-0 items-center justify-between px-4 pt-4">
                        <p class="text-sm font-bold">{{ $isEn ? 'Transcript' : 'Transkrip' }}</p>
                        <button type="button" @click="panel = false" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-white/60 hover:bg-white/10 hover:text-white" aria-label="{{ $isEn ? 'Close' : 'Tutup' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                    <div x-ref="transcript" class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4 text-sm">
                        <template x-for="item in transcript" :key="item.id">
                            <div :class="item.sender === 'user' ? 'text-right' : ''">
                                <p class="mb-0.5 text-xs font-semibold text-white/65" x-text="item.sender === 'user' ? t('Kamu', 'You') : '{{ $examinerName }}'"></p>
                                <p class="inline-block max-w-[92%] rounded-2xl px-3 py-2 text-left"
                                   :class="item.sender === 'user' ? 'bg-sky-500/20 text-sky-50' : 'bg-white/5 text-white/90'"
                                   x-text="item.message"></p>
                            </div>
                        </template>
                    </div>
                </aside>
            </div>

            {{-- Messages and typing box --}}
            <div class="mx-auto w-full max-w-3xl shrink-0 px-3 pt-3 sm:px-5">
                <p x-show="errorMessage" x-cloak class="mb-2 rounded-xl bg-rose-500/15 px-3 py-2 text-center text-sm text-rose-200" x-text="errorMessage"></p>
                <p x-show="!errorMessage && !typingMode && !liveAnswer" class="text-center text-xs text-white/65"
                   x-text="hint || (status === 'long_turn' ? t('Tekan tombol mic kalau sudah selesai', 'Tap the mic when you are done')
                        : status === 'listening' ? t('Jeda sebentar atau tekan mic untuk kirim', 'Pause for a second or tap the mic to send')
                        : status === 'thinking' ? t('Jawabanmu lagi diproses…', 'Processing your answer…')
                        : t('Mic aktif setelah penguji selesai bicara', 'The mic opens after the examiner finishes'))"></p>
                <template x-if="typingMode">
                    <form @submit.prevent="status === 'error' ? retry() : submitAnswer()" class="flex items-end gap-2">
                        <textarea x-model="typed" rows="1" :disabled="!(status === 'listening' || status === 'long_turn' || status === 'error')"
                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); status === 'error' ? retry() : submitAnswer() }"
                                  placeholder="{{ $isEn ? 'Type your answer, Enter to send' : 'Ketik jawabanmu, Enter untuk kirim' }}"
                                  class="flex-1 resize-none rounded-2xl border-0 bg-white/10 px-4 py-3 text-[15px] text-white placeholder:text-white/65 focus:ring-2 focus:ring-white/30 disabled:opacity-50"></textarea>
                        <button type="submit" :disabled="!typed.trim()" class="h-12 shrink-0 rounded-full bg-white px-5 text-sm font-bold text-slate-900 disabled:opacity-40">
                            {{ $isEn ? 'Send' : 'Kirim' }}
                        </button>
                    </form>
                </template>
            </div>

            {{-- Control bar --}}
            <div class="flex shrink-0 items-center justify-center gap-2 px-3 py-3 sm:gap-3 sm:py-4">
                {{-- Mic: answer / send --}}
                <button type="button" x-show="!typingMode" @click="submitAnswer()" :disabled="!(status === 'listening' || status === 'long_turn')"
                        class="relative inline-flex h-14 w-14 items-center justify-center rounded-full transition disabled:cursor-not-allowed"
                        :class="isListening ? 'bg-sky-500 text-white hover:bg-sky-400' : 'bg-white/10 text-white/65'"
                        :aria-label="t('Selesai menjawab', 'Done answering')" :title="t('Selesai menjawab', 'Done answering')">
                    <span x-show="isListening && vad.speaking" class="absolute inset-0 animate-ping rounded-full bg-sky-400/40"></span>
                    <svg class="relative h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg>
                </button>

                {{-- Camera --}}
                <button type="button" @click="toggleCamera()"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-full transition"
                        :class="cameraOn ? 'bg-white/10 hover:bg-white/15' : 'bg-white/10 text-white/60 hover:bg-white/15'"
                        :aria-label="cameraOn ? t('Matikan kamera', 'Turn camera off') : t('Nyalakan kamera', 'Turn camera on')"
                        :title="cameraOn ? t('Matikan kamera', 'Turn camera off') : t('Nyalakan kamera (kontak mata & ekspresi, diproses di perangkatmu)', 'Turn camera on (eye contact & expression, processed on your device)')">
                    <svg x-show="cameraOn" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    <svg x-show="!cameraOn" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.407.659-.97.659-1.591v-9a2.25 2.25 0 00-2.25-2.25h-9c-.621 0-1.184.252-1.591.659m12.182 12.182L2.909 5.909M1.5 4.5l1.409 1.409"/></svg>
                </button>

                {{-- Repeat the question --}}
                <button type="button" @click="repeatQuestion()" :disabled="status !== 'listening'"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/15 disabled:opacity-35"
                        :aria-label="t('Ulangi pertanyaan', 'Repeat question')" :title="t('Ulangi pertanyaan', 'Repeat question')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m0 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                </button>

                {{-- Captions --}}
                <button type="button" @click="captions = !captions"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-full text-xs font-black transition"
                        :class="captions ? 'bg-white text-slate-900' : 'bg-white/10 text-white/60 hover:bg-white/15'"
                        :aria-pressed="captions" :aria-label="t('Takarir', 'Captions')" :title="t('Takarir', 'Captions')">CC</button>

                {{-- Voice or typing --}}
                <button type="button" x-show="speechSupported || aiStatus?.stt_local"
                        @click="stopListening(true); voiceMode = typingMode ? ((aiStatus?.stt_local && micReady) ? 'local' : (speechSupported ? 'browser' : 'typing')) : 'typing'; if (status === 'listening') startListening()"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-full transition"
                        :class="typingMode ? 'bg-white text-slate-900' : 'bg-white/10 hover:bg-white/15'"
                        :aria-label="typingMode ? t('Jawab dengan suara', 'Answer by voice') : t('Ketik saja', 'Type instead')" :title="typingMode ? t('Jawab dengan suara', 'Answer by voice') : t('Ketik saja', 'Type instead')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="12" rx="2"/><path stroke-linecap="round" d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M7 14h10"/></svg>
                </button>

                {{-- Transcript --}}
                <button type="button" @click="panel = !panel"
                        class="inline-flex h-12 w-12 items-center justify-center rounded-full transition"
                        :class="panel ? 'bg-white text-slate-900' : 'bg-white/10 hover:bg-white/15'"
                        :aria-pressed="panel" :aria-label="t('Transkrip', 'Transcript')" :title="t('Transkrip', 'Transcript')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                </button>

                {{-- Settings: interrupting --}}
                <div x-data="{ more: false }" class="relative">
                    <button type="button" @click="more = !more" @click.outside="more = false"
                            class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 transition hover:bg-white/15" :aria-expanded="more" :aria-label="t('Pengaturan lainnya', 'More settings')" :title="t('Lainnya', 'More')">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                    </button>
                    <div x-show="more" x-cloak x-transition.opacity class="absolute bottom-14 left-1/2 w-64 -translate-x-1/2 rounded-2xl bg-[#161a28] p-3 text-left text-xs shadow-xl ring-1 ring-white/10">
                        <label class="flex cursor-pointer items-start gap-2">
                            <input type="checkbox" x-model="bargeIn" class="mt-0.5 h-4 w-4 rounded border-white/20 bg-white/10 text-sky-500 focus:ring-0">
                            <span><span class="font-semibold">{{ $isEn ? 'Allow interrupting' : 'Boleh menyela' }}</span><br>
                                <span class="text-white/65">{{ $isEn ? 'Speaking while the examiner talks stops them. Use a headset.' : 'Ngomong saat penguji bicara akan memotongnya. Pakai headset.' }}</span></span>
                        </label>
                    </div>
                </div>

                {{-- Leave --}}
                <button type="button" @click="if (confirm(isCoach ? t('Akhiri obrolan dan lihat catatan coaching?', 'End the chat and see your coaching notes?') : t('Akhiri sesi dan lihat rapor dari jawaban sejauh ini?', 'End the session and see the report for answers so far?'))) finish()"
                        class="ml-1 inline-flex h-12 items-center gap-2 rounded-full bg-rose-600 px-5 text-sm font-bold transition hover:bg-rose-500 sm:ml-3">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08a.956.956 0 010-1.36C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.72c.18.18.29.43.29.71 0 .28-.11.53-.29.71l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28a11.27 11.27 0 00-2.67-1.85.996.996 0 01-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/></svg>
                    <span class="hidden sm:inline">{{ $isEn ? 'End' : 'Akhiri' }}</span>
                </button>
            </div>
        </div>
    </div>
</x-exam-layout>
