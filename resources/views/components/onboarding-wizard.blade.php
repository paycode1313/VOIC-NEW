@props([
    'showOnInit' => request()->routeIs('dashboard') && Auth::check() && ! (Auth::user()?->has_completed_onboarding ?? false),
])

<div x-data="onboardingWizard({
        initialOpen: @js($showOnInit),
        initialGoal: @js(Auth::user()?->onboarding_goal ?: 'sidang_skripsi'),
        initialExperience: @js(Auth::user()?->onboarding_experience ?: 'pemula')
     })"
     @open-onboarding.window="openModal()"
     x-cloak>

    <!-- Modal Backdrop Overlay -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/65 backdrop-blur-md flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
         @keydown.escape.window="closeModal()">

        <!-- Modal Dialog Container -->
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             @click.outside="closeModal()"
             class="w-full max-w-3xl bg-white dark:bg-[#0c1626] border border-slate-200/90 dark:border-slate-800 rounded-3xl shadow-2xl shadow-blue-950/30 overflow-hidden flex flex-col my-auto max-h-[92vh]">

            <!-- Modal Header: Progress & Close -->
            <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-[#09111e]/50 shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#0b192c] via-blue-800 to-blue-600 flex items-center justify-center text-white shadow-xs">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Zm5 9a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21H9a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2h-2v-3.08A7 7 0 0 0 19 11h-2Z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">
                                    Panduan Orientasi & Personalisasi
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                    VOIC Step <span x-text="step"></span>/4
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Konfigurasi preferensi latihan dan pelajari cara kerja simulasi cerdas VOIC.
                            </p>
                        </div>
                    </div>

                    <!-- Close Button -->
                    <button type="button"
                            @click="closeModal()"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/80 transition cursor-pointer"
                            title="Tutup Panduan">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Step Progress Bar & Labels -->
                <div class="mt-4 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                    <div class="grid grid-cols-4 gap-2 text-center text-[10px] font-semibold mb-2">
                        <span :class="step >= 1 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400 dark:text-slate-500'">
                            1. Fokus Latihan
                        </span>
                        <span :class="step >= 2 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400 dark:text-slate-500'">
                            2. Kesiapan Diri
                        </span>
                        <span :class="step >= 3 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400 dark:text-slate-500'">
                            3. Tutorial Pemakaian
                        </span>
                        <span :class="step >= 4 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400 dark:text-slate-500'">
                            4. Arahan Simulasi
                        </span>
                    </div>

                    <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full transition-all duration-300 rounded-full"
                             :style="'width: ' + (step * 25) + '%'"></div>
                    </div>
                </div>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 overflow-y-auto flex-1 space-y-6">

                <!-- STEP 1: PILIH FOKUS / TUJUAN LATIHAN ("DI TANYA") -->
                <div x-show="step === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="space-y-4">

                    <div class="text-left space-y-1">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                            Pertanyaan 1 dari 2
                        </span>
                        <h4 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Apa tujuan atau skenario utama latihan Anda saat ini?
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Pilihan ini membantu VOIC merekomendasikan persona penguji, gaya pertanyaan, dan parameter penilaian yang relevan untuk Anda.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                        <!-- Option 1: Sidang Skripsi -->
                        <div @click="goal = 'sidang_skripsi'"
                             :class="goal === 'sidang_skripsi'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                                        Akademik & Riset
                                    </span>
                                    <div :class="goal === 'sidang_skripsi' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                         class="w-5 h-5 rounded-full flex items-center justify-center transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white">Sidang Skripsi & Tugas Akhir</h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Pertahankan metodologi penelitian, analisis data, dan hadapi sanggahan dewan penguji kritis.
                                </p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-slate-400">Penguji:</span>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">VOIC - Dosen Penguji</span>
                            </div>
                        </div>

                        <!-- Option 2: Wawancara Kerja HRD -->
                        <div @click="goal = 'wawancara_kerja'"
                             :class="goal === 'wawancara_kerja'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300">
                                        Karier & Rekrutmen
                                    </span>
                                    <div :class="goal === 'wawancara_kerja' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                         class="w-5 h-5 rounded-full flex items-center justify-center transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white">Wawancara Kerja Profesional</h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Kuasai perkenalan diri terstruktur, metode STAR (Situation-Task-Action-Result), dan negosiasi tawaran.
                                </p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-slate-400">Interviewer:</span>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">VOIC - HRD</span>
                            </div>
                        </div>

                        <!-- Option 3: Pitching Startup -->
                        <div @click="goal = 'pitching_startup'"
                             :class="goal === 'pitching_startup'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300">
                                        Bisnis & Modal Ventura
                                    </span>
                                    <div :class="goal === 'pitching_startup' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                         class="w-5 h-5 rounded-full flex items-center justify-center transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white">Pitching Startup & Investor</h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Pertahankan validasi pasar, ukuran target pasar (TAM), unit economics, dan proyeksi skalabilitas bisnis.
                                </p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-slate-400">Investor:</span>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">VOIC - Investor</span>
                            </div>
                        </div>

                        <!-- Option 4: Public Speaking -->
                        <div @click="goal = 'public_speaking'"
                             :class="goal === 'public_speaking'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300">
                                        Komunikasi Publik
                                    </span>
                                    <div :class="goal === 'public_speaking' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                         class="w-5 h-5 rounded-full flex items-center justify-center transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 dark:text-white">Public Speaking & Presentasi Umum</h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    Latih intonasi, hilangkan jeda filler words (umm/ehm), kendalikan tempo, dan bangun kontak mata percaya diri.
                                </p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-slate-400">Kurator:</span>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">VOIC - Dosen Penguji</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: TINGKAT PENGALAMAN & KESIAPAN ("DI TANYA") -->
                <div x-show="step === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="space-y-4">

                    <div class="text-left space-y-1">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                            Pertanyaan 2 dari 2
                        </span>
                        <h4 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Bagaimana Anda menilai tingkat kesiapan dan ketenangan Anda saat ini?
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Kami mengkalibrasi tingkat ketelitian evaluasi tempo bicara, frekuensi filler words, dan toleransi jeda respons.
                        </p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <!-- Level 1: Pemula -->
                        <div @click="experience = 'pemula'"
                             :class="experience === 'pemula'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer flex items-start gap-4">
                            <div :class="experience === 'pemula' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                 class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Pemula & Perlu Adaptasi</h5>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Level 1</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Sering merasa cemas, kontak mata mudah teralihkan ke meja/catatan, dan tempo bicara cenderung terburu-buru. AI akan memberikan alur santai dan umpan balik yang membangun.
                                </p>
                            </div>
                        </div>

                        <!-- Level 2: Menengah -->
                        <div @click="experience = 'menengah'"
                             :class="experience === 'menengah'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer flex items-start gap-4">
                            <div :class="experience === 'menengah' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                 class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Menengah & Cukup Berpengalaman</h5>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">Level 2</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Mampu menyampaikan ide secara runtut, namun ingin mengeliminasi jeda filler words, menyeimbangkan intonasi suara, dan menjaga kontak mata di atas target 80%.
                                </p>
                            </div>
                        </div>

                        <!-- Level 3: Mahir -->
                        <div @click="experience = 'mahir'"
                             :class="experience === 'mahir'
                                ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-600/30 dark:border-blue-500'
                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0e192c] hover:border-slate-300 dark:hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all cursor-pointer flex items-start gap-4">
                            <div :class="experience === 'mahir' ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                 class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Mahir & Simulasi Bertekanan Tinggi</h5>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">Level 3</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Mempersiapkan presentasi berisiko tinggi. AI akan menerapkan standar penguji senior yang kritis dengan pertanyaan sanggahan langsung dan batasan durasi ketat.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: TUTORIAL PEMAKAIAN PLATFORM ("DI KASIH TUTORIAL PEMAKAIAN") -->
                <div x-show="step === 3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="space-y-4">

                    <div class="text-left space-y-1">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Panduan Teknis 4 Pilar
                        </span>
                        <h4 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Cara Kerja & Alur Latihan di VOIC
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Pahami cara memaksimalkan latihan interaktif Anda agar hasil analisis metrik akurat dan terpercaya.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                        <!-- Pillar 1 -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-[#0e192c]/70 border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center">1</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Pilih Karakter & Konteks</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                Pilih penguji (Dosen/HRD/Investor). Anda dapat memasukkan ringkasan skripsi atau resume agar pertanyaan yang dilontarkan AI 100% kontekstual dengan materi Anda.
                            </p>
                            <div class="inline-flex items-center gap-1 text-[10px] font-semibold text-blue-600 dark:text-blue-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Pertanyaan terarah & relevan
                            </div>
                        </div>

                        <!-- Pillar 2 -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-[#0e192c]/70 border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">2</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Sensor Privasi Lokal (No Cloud Video)</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                Kamera mendeteksi kontak mata dan posisi kepala secara lokal di browser Anda. <strong class="text-slate-800 dark:text-slate-100 font-semibold">Tidak ada video yang direkam atau disimpan di server luar</strong>. Privasi Anda terlindungi mutlak.
                            </p>
                            <div class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                100% aman & terenkripsi lokal
                            </div>
                        </div>

                        <!-- Pillar 3 -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-[#0e192c]/70 border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">3</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Interaksi Vokal & Wajah Reaktif</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                Berbicaralah lugas menggunakan mikrofon Anda. Karakter AI menyimak secara real-time dan memberikan jawaban audio realistis lengkap dengan mikro-ekspresi reaktif saat mendengarkan dan berbicara.
                            </p>
                            <div class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                                </svg>
                                Audio dua arah berkecepatan tinggi
                            </div>
                        </div>

                        <!-- Pillar 4 -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-[#0e192c]/70 border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-amber-600 text-white font-bold text-xs flex items-center justify-center">4</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Metrik Objektif & QR Sertifikat</span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                Usai latihan, dapatkan laporan performa lengkap (persentase kontak mata, kata pengisi per menit, kestabilan tempo) serta sertifikat digital publik yang dapat diverifikasi oleh dewan dosen atau HRD.
                            </p>
                            <div class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                                Sertifikasi kompetensi terverifikasi
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: REKOMENDASI TERARAH & AKSI ("DIARAHKAN") -->
                <div x-show="step === 4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="space-y-4">

                    <div class="text-left space-y-1">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Personalisasi Selesai
                        </span>
                        <h4 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Rekomendasi Simulasi Siap untuk Anda
                        </h4>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Berdasarkan profil Anda, kami telah mengalokasikan penguji dan skenario yang paling sesuai untuk sesi pertama Anda:
                        </p>
                    </div>

                    <!-- Recommended Persona Spotlight Card -->
                    <div class="p-5 rounded-3xl bg-gradient-to-br from-slate-50 to-blue-50/50 dark:from-[#0d182b] dark:to-[#0f243e] border border-blue-200/80 dark:border-blue-900/60 shadow-lg shadow-blue-950/10">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <!-- Real Avatar Image -->
                            <div class="relative shrink-0">
                                <div class="w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-md">
                                    <img :src="currentRole.avatar"
                                         :alt="currentRole.name"
                                         class="w-full h-full object-cover object-top">
                                </div>
                                <span class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shadow-xs">
                                    ✓
                                </span>
                            </div>

                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white shadow-2xs"
                                          x-text="currentRole.scenario">
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300"
                                          x-text="currentExperience.title">
                                    </span>
                                </div>
                                <h5 class="text-base font-extrabold text-slate-900 dark:text-white tracking-tight"
                                    x-text="currentRole.name">
                                </h5>
                                <p class="text-xs text-slate-500 dark:text-slate-400"
                                   x-text="currentRole.title">
                                </p>
                            </div>
                        </div>

                        <!-- Directive Advice & Setup Box -->
                        <div class="mt-4 pt-3.5 border-t border-slate-200/80 dark:border-slate-800/80 space-y-2">
                            <div class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-[#070e1b]/70 p-3 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="leading-relaxed" x-text="currentRole.guidance"></span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 text-[11px] text-slate-600 dark:text-slate-300">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Karakter Penguji Terpilih</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    <span>Sensor Kontak Mata Aktif</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span>Evaluasi Vokal Terkalibrasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer: Navigation Actions -->
            <div class="px-6 py-4 bg-slate-50/70 dark:bg-[#09111e]/70 border-t border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between gap-3 shrink-0">
                <!-- Back / Skip Button -->
                <div>
                    <template x-if="step > 1">
                        <button type="button"
                                @click="step--"
                                :disabled="submitting"
                                class="inline-flex items-center gap-1 px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-200/60 dark:hover:bg-slate-800/60 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali</span>
                        </button>
                    </template>
                    <template x-if="step === 1">
                        <button type="button"
                                @click="closeModal()"
                                class="px-3 py-2 text-xs font-medium text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition cursor-pointer">
                            Nanti Saja
                        </button>
                    </template>
                </div>

                <!-- Next / Submit Buttons -->
                <div class="flex items-center gap-2.5">
                    <template x-if="step < 4">
                        <button type="button"
                                @click="step++"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-md shadow-blue-600/30 transition transform hover:-translate-y-0.5 cursor-pointer">
                            <span>Lanjutkan</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </template>

                    <template x-if="step === 4">
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="completeAndClose()"
                                    :disabled="submitting"
                                    class="inline-flex items-center px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-[#0c1626] border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer">
                                <span>Jelajahi Dashboard</span>
                            </button>

                            <button type="button"
                                    @click="startRecommendedSimulation()"
                                    :disabled="submitting"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-xl shadow-md shadow-blue-600/30 transition transform hover:-translate-y-0.5 cursor-pointer">
                                <span x-show="!submitting">Mulai Simulasi Pertama</span>
                                <span x-show="submitting">Menyiapkan Ruang...</span>
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function onboardingWizard(config) {
        return {
            isOpen: Boolean(config.initialOpen),
            step: 1,
            goal: config.initialGoal || 'sidang_skripsi',
            experience: config.initialExperience || 'pemula',
            submitting: false,

            roles: {
                sidang_skripsi: {
                    roleKey: 'dosen_penguji',
                    name: 'VOIC - Dosen Penguji',
                    title: 'Dosen Penguji Senior (Bidang Rekayasa & Sistem Informasi)',
                    avatar: '/images/avatars/dosen.jpg',
                    scenario: 'Simulasi Sidang Skripsi',
                    guidance: 'Fokus pada keabsahan metodologi, batasan masalah, dan pembuktian data. Penguji akan menguji logika perancangan Anda.'
                },
                wawancara_kerja: {
                    roleKey: 'hrd',
                    name: 'VOIC - HRD',
                    title: 'Senior HR Recruiter & Talent Lead',
                    avatar: '/images/avatars/hrd.jpg?v=3',
                    scenario: 'Simulasi Wawancara Kerja',
                    guidance: 'Siapkan perkenalan diri terstruktur dan gunakan metode STAR (Situation, Task, Action, Result) untuk setiap contoh kasus.'
                },
                pitching_startup: {
                    roleKey: 'investor',
                    name: 'VOIC - Investor',
                    title: 'Managing Partner, Nusantara Ventures',
                    avatar: '/images/avatars/investor.jpg',
                    scenario: 'Simulasi Pitching Investor',
                    guidance: 'Pertahankan kejelasan target pasar (TAM), keunggulan kompetitif (moat), serta traksi dan proyeksi finansial Anda.'
                },
                public_speaking: {
                    roleKey: 'dosen_penguji',
                    name: 'VOIC - Dosen Penguji',
                    title: 'Kurator Retorika & Komunikasi Publik Formal',
                    avatar: '/images/avatars/dosen.jpg',
                    scenario: 'Simulasi Public Speaking',
                    guidance: 'Latihlah ritme bicara di rentang 120-150 kata per menit, pertahankan kontak mata stabil, dan tekan filler words di bawah 3%.'
                }
            },

            experiences: {
                pemula: {
                    title: 'Level 1: Pemula (Bimbingan Bertahap)',
                    badge: 'Dasar'
                },
                menengah: {
                    title: 'Level 2: Menengah (Standar Formal)',
                    badge: 'Standar'
                },
                mahir: {
                    title: 'Level 3: Mahir (Tekanan Tinggi)',
                    badge: 'Kritis'
                }
            },

            get currentRole() {
                return this.roles[this.goal] || this.roles.sidang_skripsi;
            },

            get currentExperience() {
                return this.experiences[this.experience] || this.experiences.pemula;
            },

            openModal() {
                this.step = 1;
                this.isOpen = true;
            },

            closeModal() {
                this.isOpen = false;
            },

            async savePreferences() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) return;

                try {
                    await fetch('{{ route("onboarding.complete") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            onboarding_goal: this.goal,
                            onboarding_experience: this.experience
                        })
                    });
                } catch (e) {
                    console.warn('Gagal menyimpan preferensi onboarding:', e);
                }
            },

            async completeAndClose() {
                this.submitting = true;
                await this.savePreferences();
                this.submitting = false;
                this.isOpen = false;
            },

            async startRecommendedSimulation() {
                this.submitting = true;
                await this.savePreferences();
                const targetRole = this.currentRole.roleKey;
                window.location.href = '{{ route("practice.create") }}?role=' + encodeURIComponent(targetRole);
            }
        };
    }
</script>
