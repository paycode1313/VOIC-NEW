@php
    $blueprint = app(\App\Services\Exam\ExamBlueprint::class);
    $glows = ['amber' => '245 158 11', 'indigo' => '99 102 241', 'violet' => '139 92 246'];
    // What each examiner "says" when calling in: a short greeting and a real first question.
    $lines = [
        'wawancara' => 'Halo! Aku HRD yang bakal ngobrol sama kamu hari ini. Coba ceritain dong, kenapa kami harus milih kamu?',
        'sidang' => 'Selamat datang. Saya dosen penguji Anda hari ini. Kenapa Anda memilih metode itu, dan apa batasannya?',
        'pitching' => 'Halo, saya investor yang bakal dengerin pitch Anda. Masalah apa yang Anda selesaikan, dan siapa yang mau bayar?',
    ];
    $callers = collect($modes)->map(fn ($mode, $key) => [
        'mode' => $key,
        'name' => $mode['examiner']['name'],
        'title' => $blueprint->text($mode['examiner']['title'], 'id'),
        'avatar' => asset($mode['examiner']['avatar']),
        'glow' => $glows[$mode['accent']] ?? '99 102 241',
        'line' => $lines[$key] ?? $blueprint->text($mode['title'], 'id'),
    ])->values();
@endphp

<x-exam-layout title="Selamat datang" :call="true" :full-height="true">
    <div x-data="voicAttract(@js($callers))" x-init="start()"
         class="relative isolate flex h-full flex-col overflow-hidden" :style="'--glow: ' + caller.glow">

        {{-- Moving light in the colour of whoever is calling --}}
        <div class="pointer-events-none absolute -z-10 h-[70vmax] w-[70vmax] rounded-full opacity-60 blur-3xl transition-colors duration-1000 voic-drift [background:radial-gradient(circle,rgb(var(--glow)/0.55),transparent_65%)]" style="left: 35%; top: -20%"></div>
        <div class="pointer-events-none absolute -z-10 h-[50vmax] w-[50vmax] rounded-full opacity-40 blur-3xl voic-drift-slow [background:radial-gradient(circle,rgb(56_189_248/0.35),transparent_65%)]" style="left: -15%; bottom: -25%"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.07] [background-image:linear-gradient(white_1px,transparent_1px),linear-gradient(90deg,white_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]"></div>

        {{-- Top bar --}}
        <div class="flex shrink-0 items-center justify-between px-6 py-5 lg:px-12">
            <img src="/images/logo/voic-logo-horizontal-dark.svg" alt="VOIC" class="h-9 w-auto lg:h-11" width="156" height="44">
            <div class="flex items-center gap-3 text-sm font-semibold text-white/80">
                @if ($today > 0)
                    <span class="hidden rounded-full bg-white/10 px-3 py-1.5 sm:inline">{{ $today }} orang sudah ditelepon hari ini</span>
                @endif
                <span class="rounded-full bg-white/10 px-3 py-1.5">Innofest 2026</span>
                <span class="font-mono text-base text-white" x-text="clock"></span>
            </div>
        </div>

        <div class="mx-auto grid min-h-0 w-full max-w-7xl flex-1 items-center gap-10 px-6 pb-6 lg:grid-cols-[1.1fr_1fr] lg:px-12">
            {{-- Pitch --}}
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1 text-sm font-bold text-emerald-300">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>3 penguji AI lagi online
                </p>
                <h1 class="mt-5 text-5xl font-extrabold leading-[1.02] tracking-tight xl:text-7xl">
                    Berani ditelepon<br><span class="bg-clip-text text-transparent transition-all duration-1000" :style="'background-image: linear-gradient(90deg, rgb(' + caller.glow + '), #7dd3fc)'">penguji AI?</span>
                </h1>
                <p class="mt-5 max-w-xl text-lg text-white/75 xl:text-xl">
                    Latihan wawancara kerja, sidang skripsi, atau pitching bisnis lewat video call. Penguji nyimak jawabanmu, nanya balik, lalu kasih rapor.
                </p>

                <ol class="mt-8 grid max-w-xl grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['Tulis nama', 'Pilih penguji', 'Ngobrol ±12 menit', 'Dapat rapor'] as $n => $step)
                        <li class="rounded-2xl bg-white/[0.06] px-3 py-3 ring-1 ring-white/10">
                            <span class="text-xs font-bold text-white/65">0{{ $n + 1 }}</span>
                            <p class="mt-0.5 text-sm font-semibold leading-snug">{{ $step }}</p>
                        </li>
                    @endforeach
                </ol>

                {{-- Or call a specific examiner --}}
                <div class="mt-8 flex flex-wrap gap-3">
                    @foreach ($callers as $c)
                        <form method="POST" action="{{ route('attract.pickup') }}">
                            @csrf
                            <input type="hidden" name="mode" value="{{ $c['mode'] }}">
                            <button type="submit" style="--glow: {{ $c['glow'] }}"
                                    class="group inline-flex items-center gap-3 rounded-full bg-white/[0.07] py-1.5 pl-1.5 pr-5 text-left ring-1 ring-white/10 transition hover:bg-white/15 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white">
                                <img src="{{ $c['avatar'] }}" alt="" class="h-11 w-11 rounded-full object-cover ring-2" style="--tw-ring-color: rgb(var(--glow))">
                                <span>
                                    <span class="block text-sm font-extrabold">{{ $c['name'] }}</span>
                                    <span class="block text-xs font-semibold" style="color: rgb(var(--glow))">{{ $c['title'] }}</span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            {{-- Incoming call --}}
            <div class="flex flex-col items-center">
                <div class="relative w-full max-w-md rounded-[2rem] bg-white/[0.06] p-7 text-center shadow-2xl shadow-black/40 ring-1 ring-white/10 backdrop-blur-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-white/75">Panggilan masuk…</p>

                    <div class="relative mx-auto mt-6 h-40 w-40 xl:h-48 xl:w-48">
                        <span class="voic-ring absolute inset-0 rounded-full border-2" style="border-color: rgb(var(--glow) / 0.7)"></span>
                        <span class="voic-ring absolute inset-0 rounded-full border-2 [animation-delay:600ms]" style="border-color: rgb(var(--glow) / 0.5)"></span>
                        <span class="voic-ring absolute inset-0 rounded-full border-2 [animation-delay:1200ms]" style="border-color: rgb(var(--glow) / 0.3)"></span>
                        <template x-for="(c, n) in callers" :key="c.mode">
                            <img :src="c.avatar" alt="" x-show="n === i" x-transition.opacity.duration.500ms
                                 class="absolute inset-0 h-full w-full rounded-full object-cover ring-4 ring-white/90 voic-shake">
                        </template>
                    </div>

                    <h2 class="mt-6 text-3xl font-extrabold tracking-tight" x-text="caller.name"></h2>
                    <p class="mt-1 text-base font-semibold transition-colors duration-700" :style="'color: rgb(' + caller.glow + ')'" x-text="caller.title"></p>

                    {{-- Live caption, typed out like the real call --}}
                    <div class="mt-5 flex min-h-[5.5rem] items-start rounded-2xl bg-black/50 px-4 py-3 text-left">
                        <span class="mr-2 mt-1.5 flex h-3 shrink-0 items-end gap-[2px]" aria-hidden="true">
                            <span class="voic-eq w-[3px] rounded-full bg-white"></span><span class="voic-eq w-[3px] rounded-full bg-white [animation-delay:150ms]"></span><span class="voic-eq w-[3px] rounded-full bg-white [animation-delay:300ms]"></span>
                        </span>
                        <p class="text-base leading-relaxed text-white" aria-live="off"><span x-text="typed"></span><span class="ml-0.5 inline-block h-4 w-[2px] animate-pulse bg-white/80 align-middle"></span></p>
                    </div>

                    <div class="mt-7 flex items-start justify-center gap-14">
                        <div class="flex flex-col items-center gap-2">
                            <button type="button" @click="next(true)" aria-label="Penguji berikutnya"
                                    class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg shadow-rose-900/40 transition hover:scale-105 hover:bg-rose-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                <svg class="h-7 w-7 rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1z"/></svg>
                            </button>
                            <span class="text-sm font-semibold text-white/75">Lainnya</span>
                        </div>
                        <form method="POST" action="{{ route('attract.pickup') }}" class="flex flex-col items-center gap-2">
                            @csrf
                            <input type="hidden" name="mode" :value="caller.mode">
                            <button type="submit" aria-label="Angkat telepon"
                                    class="voic-bounce inline-flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500 text-white shadow-xl shadow-emerald-900/50 transition hover:scale-105 hover:bg-emerald-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                <svg class="h-9 w-9" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1z"/></svg>
                            </button>
                            <span class="text-base font-bold">Angkat</span>
                        </form>
                    </div>
                </div>

                {{-- Phones --}}
                <div class="mt-6 flex items-center gap-4 rounded-2xl bg-white/[0.06] p-3 pr-5 ring-1 ring-white/10">
                    <div id="attract-qr" data-url="{{ $startUrl }}" class="h-24 w-24 shrink-0 rounded-xl bg-white p-1.5 [&_svg]:h-full [&_svg]:w-full"></div>
                    <div>
                        <p class="font-bold">Atau coba dari HP kamu</p>
                        <p class="mt-0.5 text-sm text-white/75">Pindai, pilih penguji, ngobrol. Gratis, tanpa daftar.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // This tab is now the booth kiosk: other pages send it back here when a visitor walks away.
        try {
            if (new URLSearchParams(location.search).get('kiosk') === 'off') sessionStorage.removeItem('voic_kiosk');
            else sessionStorage.setItem('voic_kiosk', '1');
        } catch (e) {}

        function voicAttract(callers) {
            return {
                callers, i: 0, typed: '', clock: '', timer: null,
                get caller() { return this.callers[this.i] },
                start() {
                    const tick = () => this.clock = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    tick(); setInterval(tick, 10000);
                    this.play();
                },
                play() {
                    clearTimeout(this.timer);
                    const line = this.caller.line;
                    let n = 0;
                    this.typed = '';
                    const type = () => {
                        this.typed = line.slice(0, ++n);
                        this.timer = n < line.length ? setTimeout(type, 38) : setTimeout(() => this.next(), 4500);
                    };
                    this.timer = setTimeout(type, 900);
                },
                next() {
                    this.i = (this.i + 1) % this.callers.length;
                    this.play();
                },
            };
        }
    </script>
    @include('booth.partials.qr', ['target' => 'attract-qr'])
</x-exam-layout>
