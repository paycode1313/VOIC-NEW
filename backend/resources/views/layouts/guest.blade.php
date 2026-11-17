{{-- Auth pages: mobile-first international app card + desktop split showcase. --}}
<x-exam-layout :title="$title ?? null">
    <div class="mx-auto flex min-h-[calc(100dvh-3.5rem)] max-w-6xl flex-col justify-center px-4 py-6 sm:px-6 sm:py-10 lg:py-12">
        <div class="mx-auto grid w-full max-w-[440px] items-center gap-10 lg:max-w-none lg:grid-cols-[1fr_1.08fr] xl:gap-16">
            {{-- Left / Mobile Primary Column: Auth Form --}}
            <div class="w-full">
                {{ $slot }}
            </div>

            {{-- Right Column (Desktop lg+): International Product Showcase Card --}}
            <div class=" relative hidden overflow-hidden rounded-[2rem] bg-navy-950 p-8 text-white shadow-2xl ring-1 ring-white/10 lg:flex lg:flex-col lg:justify-between xl:p-10" aria-hidden="true">
                {{-- Ambient radial glow --}}
                <div class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-brand-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-sky-400/20 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-0 opacity-25 [background-image:radial-gradient(rgb(255_255_255/0.12)_1px,transparent_1px)] [background-size:20px_20px]"></div>

                {{-- Top Status Pill --}}
                <div class="relative z-10 flex items-center justify-between">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] px-3.5 py-1.5 text-xs font-semibold tracking-wide text-sky-200 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        VOIC INTELLIGENT ENGINE · REAL-TIME
                    </span>
                    <span class="rounded-full bg-white/10 px-3 py-1 font-mono text-xs font-semibold text-slate-300">
                        v2.4 · ID / EN
                    </span>
                </div>

                {{-- Center Hero Copy & Live Telemetry Preview --}}
                <div class="relative z-10 my-8">
                    <h2 class="text-3xl font-extrabold leading-tight tracking-tight text-white xl:text-[34px]">
                        Latihan bicara tatap muka dengan penguji AI standar global.
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300/90">
                        Simulasi wawancara kerja, sidang skripsi, dan pitching startup dengan analisis suara, struktur argumen, serta kontak mata secara langsung.
                    </p>

                    {{-- Simulated Live AI Examiner Card --}}
                    <div class="mt-7 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3.5">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <img src="{{ asset('images/avatars/hrd-portrait.webp') }}" alt="" class="h-11 w-11 rounded-full object-cover ring-2 ring-brand-400/70">
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-emerald-400 ring-2 ring-navy-950"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white">Rania Putri · Lead HR Recruiter</p>
                                    <p class="text-xs text-sky-300">Wawancara Kerja · Bagian 3 (Follow-up)</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 rounded-full bg-brand-500/20 px-2.5 py-1 text-[11px] font-bold text-sky-300 ring-1 ring-brand-400/30">
                                <span class="flex h-2.5 items-end gap-[2px]">
                                    <span class="voic-eq w-[2px] rounded-full bg-sky-300"></span>
                                    <span class="voic-eq w-[2px] rounded-full bg-sky-300 [animation-delay:150ms]"></span>
                                    <span class="voic-eq w-[2px] rounded-full bg-sky-300 [animation-delay:300ms]"></span>
                                </span>
                                AI Aktif
                            </div>
                        </div>

                        {{-- Metric Pills --}}
                        <div class="mt-3.5 grid grid-cols-3 gap-2.5">
                            <div class="rounded-xl bg-white/[0.05] px-3 py-2.5 ring-1 ring-white/[0.07]">
                                <p class="text-[11px] font-medium text-slate-400">Band Score</p>
                                <p class="mt-0.5 text-lg font-extrabold text-white">8.5 <span class="text-xs font-semibold text-emerald-400">+1.2</span></p>
                            </div>
                            <div class="rounded-xl bg-white/[0.05] px-3 py-2.5 ring-1 ring-white/[0.07]">
                                <p class="text-[11px] font-medium text-slate-400">Kontak Mata</p>
                                <p class="mt-0.5 text-lg font-extrabold text-white">94% <span class="text-xs font-semibold text-sky-300">Stabil</span></p>
                            </div>
                            <div class="rounded-xl bg-white/[0.05] px-3 py-2.5 ring-1 ring-white/[0.07]">
                                <p class="text-[11px] font-medium text-slate-400">Struktur STAR</p>
                                <p class="mt-0.5 text-lg font-extrabold text-white">91% <span class="text-xs font-semibold text-amber-300">Kuat</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom Trust Footer --}}
                <div class="relative z-10 flex items-center justify-between border-t border-white/10 pt-5 text-xs text-slate-400">
                    <div class="flex items-center gap-2.5">
                        <div class="flex -space-x-2">
                            <img src="{{ asset('images/avatars/hrd-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-navy-950">
                            <img src="{{ asset('images/avatars/dosen-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-navy-950">
                            <img src="{{ asset('images/avatars/investor-portrait.webp') }}" alt="" class="h-7 w-7 rounded-full object-cover ring-2 ring-navy-950">
                        </div>
                        <span class="font-medium text-slate-300">3 Penguji AI Spesialis Siap</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-400">
                        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        Privasi Terenkripsi
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-exam-layout>
