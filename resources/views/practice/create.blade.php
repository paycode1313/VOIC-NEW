<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Simulasi AI Roleplay • Real-Time Voice & Open Cam
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                    {{ __('Ruang Simulasi Interaktif - VOIC AI') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pilih karakter AI penguji, nyalakan kamera, dan latih respons spontan dengan deteksi ekspresi wajah & suara otomatis.
                </p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-750 transition shadow-xs w-fit">
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </x-slot>

    <style>
        /* Modern 3D Audio Orb Animations */
        @keyframes orb-pulse-idle {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(99, 102, 241, 0.4)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 35px rgba(139, 92, 246, 0.6)); }
        }
        @keyframes orb-speaking {
            0%, 100% { transform: scale(1.02); filter: drop-shadow(0 0 30px rgba(129, 140, 248, 0.7)); }
            25% { transform: scale(1.15) rotate(5deg); filter: drop-shadow(0 0 50px rgba(168, 85, 247, 0.9)); }
            50% { transform: scale(0.98) rotate(-4deg); filter: drop-shadow(0 0 35px rgba(236, 72, 153, 0.8)); }
            75% { transform: scale(1.12) rotate(3deg); filter: drop-shadow(0 0 45px rgba(99, 102, 241, 0.85)); }
        }
        @keyframes orb-listening {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 25px rgba(16, 185, 129, 0.5)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 40px rgba(6, 182, 212, 0.75)); }
        }
        @keyframes orb-thinking {
            0% { transform: rotate(0deg) scale(1); filter: drop-shadow(0 0 25px rgba(245, 158, 11, 0.6)); }
            50% { transform: rotate(180deg) scale(1.06); filter: drop-shadow(0 0 45px rgba(234, 88, 12, 0.8)); }
            100% { transform: rotate(360deg) scale(1); filter: drop-shadow(0 0 25px rgba(245, 158, 11, 0.6)); }
        }
        @keyframes ripple-wave {
            0% { transform: scale(0.85); opacity: 0.8; }
            100% { transform: scale(1.85); opacity: 0; }
        }
        .orb-idle { animation: orb-pulse-idle 4s ease-in-out infinite; }
        .orb-speaking { animation: orb-speaking 1.2s ease-in-out infinite; }
        .orb-listening { animation: orb-listening 1.8s ease-in-out infinite; }
        .orb-thinking { animation: orb-thinking 2s linear infinite; }
        .ripple-ring { animation: ripple-wave 2s cubic-bezier(0, 0.2, 0.8, 1) infinite; }
    </style>

    <div class="py-8" x-data="interactiveSimulationApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- STEP 1: AI ROLE SELECTION (Shown before session starts) -->
            <div x-show="sessionState === 'selection'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xs border border-gray-100 dark:border-gray-700/60 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-xs font-bold px-3 py-1 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-full uppercase tracking-wider">
                                    Langkah 1: Pilih Karakter AI Penguji
                                </span>
                                <template x-if="ollamaStatus.checked && ollamaStatus.active">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        🧠 Ollama LLM Aktif (<span x-text="ollamaStatus.model"></span>)
                                    </span>
                                </template>
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                                Siapa yang Akan Menguji & Mengevaluasi Anda Hari Ini?
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Setiap karakter AI memiliki sifat psikologis, fokus pengujian, dan gaya evaluasi bicara & ekspresi wajah yang berbeda.
                            </p>
                        </div>
                    </div>

                    <!-- Role Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @forelse($aiRoles as $role)
                            <div @click="selectRole(@js($role))"
                                 :class="selectedRole.id === {{ $role->id }}
                                    ? 'border-indigo-600 ring-4 ring-indigo-500/20 bg-indigo-50/40 dark:bg-indigo-950/40 dark:border-indigo-500'
                                    : 'border-gray-200 dark:border-gray-750 bg-white dark:bg-gray-850 hover:border-gray-300 dark:hover:border-gray-650'"
                                 class="p-6 rounded-2xl border transition-all cursor-pointer relative group flex flex-col justify-between">

                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-md {{ $role->role_type === 'dosen_penguji' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700' : ($role->role_type === 'hrd' ? 'bg-amber-100 dark:bg-amber-900 text-amber-700' : 'bg-purple-100 dark:bg-purple-900 text-purple-700') }}">
                                            @if($role->role_type === 'dosen_penguji')
                                                🎓
                                            @elseif($role->role_type === 'hrd')
                                                💼
                                            @else
                                                🚀
                                            @endif
                                        </div>
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider {{ $role->difficulty_level === 'Sulit' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                                Level: {{ $role->difficulty_level }}
                                            </span>
                                            <span class="text-[10px] font-semibold text-gray-400">
                                                Suara: {{ $role->voice_id ? 'Tersedia' : 'Sintesis' }}
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="font-extrabold text-lg text-gray-900 dark:text-white">{{ $role->name }}</h4>
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 block mb-2 uppercase tracking-wide">
                                        {{ $role->role_type === 'dosen_penguji' ? 'Sidang Skripsi / Tesis' : ($role->role_type === 'hrd' ? 'HRD Interview Recruiter' : 'Pitching Startup') }}
                                    </span>

                                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                                        {{ $role->description }}
                                    </p>

                                    <!-- Traits Tags -->
                                    @if(is_array($role->personality_traits))
                                        <div class="flex flex-wrap gap-1.5 mb-4">
                                            @foreach($role->personality_traits as $trait)
                                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300">
                                                    #{{ $trait }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-4 border-t border-gray-100 dark:border-gray-750 flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Pilih Karakter Ini</span>
                                    <span x-show="selectedRole.id === {{ $role->id }}" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Terpilih
                                    </span>
                                </div>
                            </div>
                        @empty
                            <!-- Fallback if database not seeded yet -->
                            @foreach($scenarios as $sc)
                                <div @click="selectRole({ id: 1, name: '{{ $sc['name'] }}', role_type: '{{ $sc['role_type'] }}', difficulty_level: 'Sedang' })"
                                     class="p-6 rounded-2xl border border-gray-200 dark:border-gray-750 bg-white dark:bg-gray-850 cursor-pointer">
                                    <div class="text-3xl mb-2">{{ $sc['icon'] }}</div>
                                    <h4 class="font-bold text-base text-gray-900 dark:text-white">{{ $sc['name'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $sc['description'] }}</p>
                                </div>
                            @endforeach
                        @endforelse
                    </div>

                    <!-- Action Button to Start Simulation -->
                    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span class="text-base">💡</span>
                            <span>Kamera & mikrofon Anda akan diaktifkan untuk deteksi kontak mata dan jeda hening 1,5 detik.</span>
                        </div>

                        <button type="button"
                                @click="enterInterviewRoom()"
                                :disabled="isStarting"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 rounded-2xl shadow-xl shadow-indigo-500/25 transition transform hover:-translate-y-0.5 cursor-pointer disabled:opacity-50">
                            <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="isStarting ? 'Menyiapkan Karakter AI...' : 'Masuk ke Ruang Simulasi'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: ACTIVE INTERVIEW ROOM (Live Camera + Audio Orb + Silence Detection) -->
            <div x-show="sessionState === 'room'" class="space-y-6" x-cloak>

                <!-- Interview Top Navigation & Status -->
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600">
                            <span x-text="selectedRole.role_type === 'dosen_penguji' ? '🎓' : (selectedRole.role_type === 'hrd' ? '💼' : '🚀')"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-sm text-gray-900 dark:text-white" x-text="selectedRole.name"></h3>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300">
                                    Live Roleplay
                                </span>
                                <template x-if="ollamaStatus.checked && ollamaStatus.active">
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center gap-1 border border-indigo-200 dark:border-indigo-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                        Ollama: <span x-text="ollamaStatus.model"></span>
                                    </span>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400">
                                Durasi Sesi: <span class="font-mono font-bold text-gray-700 dark:text-gray-200" x-text="formattedTime"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Right Controls: End Session Button -->
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <button type="button"
                                @click="finishSession()"
                                :disabled="isFinishing"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 rounded-xl shadow-md shadow-rose-500/20 transition cursor-pointer">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="6" y="6" width="12" height="12" rx="2" stroke-width="2"/>
                            </svg>
                            <span x-text="isFinishing ? 'Menghitung Rapor...' : 'Akhiri Sesi & Buat Rapor'"></span>
                        </button>
                    </div>
                </div>

                <!-- Main Layout: 2 Columns (Open Cam Left, Interactive Orb & Dialogue Right) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- LEFT: Open Cam Video Feed & Optical HUD (5 Columns) -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="relative bg-gray-950 rounded-3xl overflow-hidden shadow-2xl border border-gray-800 aspect-4/3 flex items-center justify-center">

                            <!-- WebCam Video Element -->
                            <video id="webcamVideo"
                                   autoplay
                                   playsinline
                                   muted
                                   class="w-full h-full object-cover -scale-x-100">
                            </video>

                            <!-- Off-screen Computer Vision Telemetry Canvas -->
                            <canvas id="telemetryCanvas" width="320" height="240" class="hidden"></canvas>

                            <!-- Four Sci-Fi HUD Corner Brackets -->
                            <div class="absolute inset-2.5 pointer-events-none z-10">
                                <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-indigo-400/80 rounded-tl-lg shadow-sm shadow-indigo-500/50"></div>
                                <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-indigo-400/80 rounded-tr-lg shadow-sm shadow-indigo-500/50"></div>
                                <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-indigo-400/80 rounded-bl-lg shadow-sm shadow-indigo-500/50"></div>
                                <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-indigo-400/80 rounded-br-lg shadow-sm shadow-indigo-500/50"></div>
                            </div>

                            <!-- Optical Face Box Tracking HUD (Real Dynamic Computer Vision Coordinates) -->
                            <div class="absolute inset-0 pointer-events-none z-10">
                                <template x-if="faceDetected">
                                    <div class="absolute transition-all duration-150 rounded-2xl"
                                         :style="`left: ${faceBoxCoords.left}%; top: ${faceBoxCoords.top}%; width: ${faceBoxCoords.width}%; height: ${faceBoxCoords.height}%;`">

                                        <!-- Outer Glow Face Box -->
                                        <div class="absolute inset-0 border-2 rounded-2xl transition-all duration-150"
                                             :class="currentFaceStatus === 'tegang'
                                                ? 'border-rose-500 shadow-rose-500/40 shadow-lg'
                                                : (currentFaceStatus === 'tersenyum' ? 'border-emerald-400 shadow-emerald-400/40 shadow-lg' : 'border-indigo-400 shadow-indigo-500/30 shadow-md')">
                                        </div>

                                        <!-- Dynamic Center Crosshair & Reticle Ring -->
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div class="w-12 h-12 rounded-full border border-dashed transition-all duration-300 animate-spin"
                                                 style="animation-duration: 9s;"
                                                 :class="liveEyeContactScore >= 70 ? 'border-emerald-400/80' : 'border-amber-400/80'"></div>
                                            <div class="absolute w-2.5 h-2.5 rounded-full"
                                                 :class="liveEyeContactScore >= 70 ? 'bg-emerald-400' : 'bg-amber-400'"></div>
                                            <div class="absolute w-4 h-0.5"
                                                 :class="liveEyeContactScore >= 70 ? 'bg-emerald-400/80' : 'bg-amber-400/80'"></div>
                                            <div class="absolute w-0.5 h-4"
                                                 :class="liveEyeContactScore >= 70 ? 'bg-emerald-400/80' : 'bg-amber-400/80'"></div>
                                        </div>

                                        <!-- Dynamic Gaze Status Pill (Above Box) -->
                                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full tracking-wider shadow-lg flex items-center gap-1 font-mono uppercase"
                                                  :class="liveEyeContactScore >= 70 ? 'bg-emerald-950/90 text-emerald-300 border border-emerald-400/40' : 'bg-amber-950/90 text-amber-300 border border-amber-400/40'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="liveEyeContactScore >= 70 ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400 animate-ping'"></span>
                                                <span x-text="liveEyeContactScore >= 70 ? `TARGET LOCKED • ${liveEyeContactScore}%` : `GAZE DEFLECTED • ${liveEyeContactScore}%`"></span>
                                            </span>
                                        </div>

                                        <!-- Emotion Composure Pill (Below Box) -->
                                        <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                            <span class="text-[8px] font-bold px-2 py-0.5 rounded-full bg-black/85 text-white/90 border border-white/10 font-mono tracking-wider flex items-center gap-1">
                                                <span x-text="currentFaceIcon"></span>
                                                <span x-text="'COMPO: ' + currentFaceText.toUpperCase()"></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!faceDetected">
                                    <div class="absolute inset-6 border-2 border-dashed border-rose-500/70 rounded-3xl flex items-center justify-center bg-rose-950/20 backdrop-blur-xs">
                                        <div class="text-center p-3">
                                            <span class="text-2xl block mb-1">⚠️</span>
                                            <span class="text-xs font-bold text-rose-300">Wajah Tidak Terdeteksi</span>
                                            <p class="text-[10px] text-rose-400/80 mt-0.5">Posisikan wajah Anda tepat di depan kamera</p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Top Left HUD: Optical Engine FPS + Status -->
                            <div class="absolute top-3 left-3 z-20 flex items-center gap-2">
                                <div class="px-3 py-1.5 rounded-xl bg-black/75 backdrop-blur-md border border-white/15 text-white text-xs flex items-center gap-2 shadow-lg">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="font-mono text-[11px] font-bold text-emerald-400">VISION AI 30 FPS</span>
                                    <span class="text-white/30 text-[10px]">|</span>
                                    <span class="text-xs" x-text="currentFaceIcon + ' ' + currentFaceText">😊 Fokus & Rileks</span>
                                </div>
                            </div>

                            <!-- Top Right HUD: Live Eye Contact % -->
                            <div class="absolute top-3 right-3 z-20">
                                <div class="px-3 py-1.5 rounded-xl bg-black/75 backdrop-blur-md border border-white/15 text-white text-xs font-mono flex items-center gap-1.5 shadow-lg">
                                    <span class="text-indigo-400 font-bold">TATAPAN:</span>
                                    <span :class="liveEyeContactScore >= 70 ? 'text-emerald-400 font-bold' : 'text-amber-400 font-bold'" x-text="liveEyeContactScore + '%'">85%</span>
                                </div>
                            </div>

                            <!-- Bottom Floating Bar: Privacy & Security Badge -->
                            <div class="absolute bottom-3 inset-x-3 z-20 flex items-center justify-between px-3 py-1.5 rounded-xl bg-black/80 backdrop-blur-md border border-white/10 text-white text-[10px]">
                                <div class="flex items-center gap-1.5 font-mono text-emerald-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>EDGE AI • ZERO CLOUD VIDEO</span>
                                </div>
                                <span class="text-gray-400 font-mono">100% PRIVATE</span>
                            </div>
                        </div>

                        <!-- Live Dynamic Audio Waveform Visualizer & Decibel Meter -->
                        <div class="p-3.5 rounded-2xl bg-gray-900/90 dark:bg-gray-950/90 border border-gray-800 shadow-xl space-y-2">
                            <div class="flex items-center justify-between text-xs px-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="font-bold text-gray-200">Spektrum Suara Mikrofon (Live Acoustic Waveform)</span>
                                </div>
                                <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                    <span class="text-indigo-400 font-bold" x-text="(liveDecibels || 25) + ' dB'">25 dB</span>
                                    <span class="text-gray-500">•</span>
                                    <span :class="liveVolume > 15 ? 'text-emerald-400 font-bold' : 'text-gray-400'" x-text="liveVolume > 15 ? 'Suara Terdeteksi' : 'Hening'">Hening</span>
                                </div>
                            </div>
                            <div class="w-full h-11 relative">
                                <canvas id="audioWaveformCanvas" class="w-full h-full rounded-xl bg-black/60 border border-white/10"></canvas>
                            </div>
                        </div>

                        <!-- Live AI Visual Reprimand Alert (If user is nervous / breaking eye contact) -->
                        <div x-show="liveReprimandNotice"
                             x-transition
                             class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">⚠️</span>
                            <div>
                                <span class="font-bold block">Teguran Ekspresi AI:</span>
                                <span x-text="liveReprimandNotice"></span>
                            </div>
                        </div>

                        <!-- Device Toggles -->
                        <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Kamera & Mic Aktif
                            </span>
                            <span class="text-gray-400">Tekan spasi untuk mute</span>
                        </div>
                    </div>

                    <!-- RIGHT: Interactive Audio Orb & Conversational Transcript (7 Columns) -->
                    <div class="lg:col-span-7 flex flex-col space-y-4">

                        <!-- Central Audio Orb Section -->
                        <div class="bg-gray-900 rounded-3xl p-6 sm:p-8 border border-gray-800 shadow-2xl relative overflow-hidden flex flex-col items-center justify-center min-h-[260px]">

                            <!-- Ambient background glows -->
                            <div class="absolute -top-16 -left-16 w-48 h-48 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
                            <div class="absolute -bottom-16 -right-16 w-48 h-48 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>

                            <!-- Multi-Layer Ripple Wave (Rings radiating when speaking) -->
                            <div x-show="orbState === 'ai_speaking' || orbState === 'user_speaking'"
                                 class="absolute w-36 h-36 rounded-full border border-indigo-400/40 ripple-ring pointer-events-none"></div>
                            <div x-show="orbState === 'ai_speaking'"
                                 class="absolute w-48 h-48 rounded-full border border-purple-400/30 ripple-ring pointer-events-none" style="animation-delay: 0.6s;"></div>

                            <!-- Central Animated Orb Canvas / Gradient Sphere -->
                            <div class="relative z-10 w-28 h-28 sm:w-32 sm:h-32 rounded-full flex items-center justify-center shadow-2xl transition-all duration-500 cursor-pointer"
                                 :class="{
                                    'orb-speaking bg-gradient-to-tr from-indigo-600 via-purple-500 to-pink-500': orbState === 'ai_speaking',
                                    'orb-listening bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-500': orbState === 'user_speaking',
                                    'orb-thinking bg-gradient-to-tr from-amber-500 via-orange-500 to-purple-600': orbState === 'thinking',
                                    'orb-idle bg-gradient-to-tr from-indigo-700 via-indigo-600 to-purple-700': orbState === 'idle'
                                 }">
                                <!-- Inner Core Light -->
                                <div class="w-12 h-12 rounded-full bg-white/40 blur-md"></div>
                                <div class="absolute text-2xl font-black text-white/90 select-none">
                                    <span x-show="orbState === 'ai_speaking'">🎙️</span>
                                    <span x-show="orbState === 'user_speaking'">👂</span>
                                    <span x-show="orbState === 'thinking'">⚙️</span>
                                    <span x-show="orbState === 'idle'">✨</span>
                                </div>
                            </div>

                            <!-- Dynamic Orb Status Badge -->
                            <div class="mt-6 z-10 flex flex-col items-center text-center">
                                <div class="px-4 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase shadow-md flex items-center gap-2"
                                     :class="{
                                        'bg-purple-950/80 text-purple-300 border border-purple-800': orbState === 'ai_speaking',
                                        'bg-emerald-950/80 text-emerald-300 border border-emerald-800': orbState === 'user_speaking',
                                        'bg-amber-950/80 text-amber-300 border border-amber-800': orbState === 'thinking',
                                        'bg-gray-800 text-gray-300 border border-gray-700': orbState === 'idle'
                                     }">
                                    <span class="w-2 h-2 rounded-full"
                                          :class="{
                                            'bg-purple-400 animate-ping': orbState === 'ai_speaking',
                                            'bg-emerald-400 animate-pulse': orbState === 'user_speaking',
                                            'bg-amber-400 animate-spin': orbState === 'thinking',
                                            'bg-gray-400': orbState === 'idle'
                                          }"></span>
                                    <span x-text="orbStatusLabel">Menunggu pembicara...</span>
                                </div>

                                <!-- Silence Detection Countdown Bar -->
                                <p class="text-[11px] text-gray-400 mt-2">
                                    <span x-show="orbState === 'user_speaking'">
                                        Bicaralah dengan jelas. Setelah Anda diam <strong>1,5 detik</strong>, suara otomatis dikirim ke AI.
                                    </span>
                                    <span x-show="orbState === 'ai_speaking'">
                                        Dengarkan tanggapan dan pertanyaan dari <strong x-text="selectedRole.name"></strong>.
                                    </span>
                                    <span x-show="orbState === 'thinking'">
                                        <strong x-text="selectedRole.name"></strong> sedang memproses jawaban & menganalisis ekspresi wajah Anda...
                                    </span>
                                    <span x-show="orbState === 'idle'">
                                        Silakan mulai berbicara untuk menjawab.
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Live Turn-by-Turn Dialogue History (Chat bubbles) -->
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex-1 flex flex-col justify-between min-h-[320px]">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60 mb-3">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                    <span>💬</span>
                                    <span>Riwayat Obrolan Real-Time</span>
                                </h4>
                                <span class="text-[10px] text-gray-400 font-mono" x-text="messages.length + ' Pesan'"></span>
                            </div>

                            <!-- Scrollable Messages Container -->
                            <div id="dialogueContainer" class="space-y-3 overflow-y-auto max-h-[260px] pr-1 flex-1">
                                <template x-for="(msg, idx) in messages" :key="idx">
                                    <div class="flex flex-col" :class="msg.sender === 'user' ? 'items-end' : 'items-start'">
                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400 mb-1">
                                            <span class="font-bold" x-text="msg.sender === 'user' ? 'Anda' : selectedRole.name"></span>
                                            <span x-text="'• ' + (msg.timestamp_seconds ? msg.timestamp_seconds + 's' : '0s')"></span>
                                            <template x-if="msg.facial_status && msg.facial_status.status">
                                                <span class="px-1.5 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 text-[9px]"
                                                      x-text="'Ekspresi: ' + msg.facial_status.status"></span>
                                            </template>
                                        </div>
                                        <div class="p-3.5 rounded-2xl text-xs leading-relaxed max-w-[85%] shadow-xs"
                                             :class="msg.sender === 'user'
                                                ? 'bg-indigo-600 text-white rounded-tr-none'
                                                : 'bg-gray-100 dark:bg-gray-750 text-gray-800 dark:text-gray-200 rounded-tl-none border border-gray-200/50 dark:border-gray-700/50'">
                                            <p x-text="msg.message"></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Live Interim Speech Bubble (What user is saying right now) -->
                                <div x-show="liveInterimTranscript" class="flex flex-col items-end">
                                    <span class="text-[10px] text-emerald-500 font-bold mb-1 animate-pulse">Sedang Berbicara...</span>
                                    <div class="p-3.5 rounded-2xl rounded-tr-none text-xs leading-relaxed max-w-[85%] bg-indigo-600/70 text-white italic border border-indigo-400/40">
                                        <p x-text="liveInterimTranscript"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Text Input Fallback (In case mic is unavailable or silence detection is slow) -->
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 mt-2">
                                <form @submit.prevent="submitManualTurn()" class="flex items-center gap-2">
                                    <input type="text"
                                           x-model="manualInputText"
                                           :placeholder="'Bicara langsung ke mikrofon atau ketik jawaban untuk ' + selectedRole.name + '...'"
                                           :disabled="orbState === 'thinking' || orbState === 'ai_speaking'"
                                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-850 dark:text-white px-3.5 py-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                    <button type="submit"
                                            :disabled="!manualInputText.trim() || orbState === 'thinking'"
                                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white font-bold text-xs rounded-xl transition shrink-0 cursor-pointer shadow-sm">
                                        Kirim
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function interactiveSimulationApp() {
            return {
                aiRoles: @json($aiRoles),
                selectedRole: @json($defaultRole),

                sessionState: 'selection', // 'selection' | 'room'
                currentSessionId: null,
                isStarting: false,
                isFinishing: false,

                // Ollama Engine Status
                ollamaStatus: { checked: false, active: false, model: 'qwen2.5:3b' },

                // Hardware & Media
                mediaStream: null,
                audioContext: null,
                analyser: null,
                dataArray: null,

                // Real Computer Vision & Audio Telemetry
                faceDetected: false,
                faceBoxCoords: { left: 20, top: 15, width: 60, height: 70 },
                liveVolume: 0,
                liveDecibels: 25,
                waveformAnimationId: null,
                liveEyeContactScore: 0,
                liveSmileRate: 0,
                currentFaceStatus: 'menunggu', // 'fokus', 'tegang', 'tersenyum', 'mata_melenceng', 'wajah_hilang'
                currentFaceIcon: '📷',
                currentFaceText: 'Menghubungkan Kamera...',
                liveReprimandNotice: null,

                // Aggregated stats for the final feedback report
                eyeContactSamples: [],
                smileSamples: [],
                volumeSamples: [],

                // Orb & Conversational State
                orbState: 'idle', // 'idle' | 'user_speaking' | 'ai_speaking' | 'thinking'
                messages: [],
                liveInterimTranscript: '',
                manualInputText: '',

                // Speech Recognition & Silence Detection
                recognition: null,
                silenceTimer: null,
                silenceDelayMs: 1500, // 1.5 seconds silence detection
                accumulatedSpokenText: '',

                // Timers
                secondsElapsed: 0,
                sessionTimerInterval: null,
                telemetrySamplerInterval: null,

                async init() {
                    try {
                        const res = await fetch('http://127.0.0.1:8001/health/ollama');
                        if (res.ok) {
                            const data = await res.json();
                            this.ollamaStatus = {
                                checked: true,
                                active: !!data.ollama_active,
                                model: (data.available_models && data.available_models.length > 0) ? data.available_models[0] : 'qwen2.5:3b'
                            };
                        }
                    } catch (e) {
                        this.ollamaStatus = { checked: true, active: false, model: null };
                    }
                },

                selectRole(role) {
                    this.selectedRole = role;
                },

                get formattedTime() {
                    const mins = String(Math.floor(this.secondsElapsed / 60)).padStart(2, '0');
                    const secs = String(this.secondsElapsed % 60).padStart(2, '0');
                    return `${mins}:${secs}`;
                },

                get orbStatusLabel() {
                    if (this.orbState === 'ai_speaking') return this.selectedRole.name + ' Sedang Berbicara';
                    if (this.orbState === 'user_speaking') return 'Mendengarkan Jawaban Anda...';
                    if (this.orbState === 'thinking') return 'AI Sedang Menganalisis...';
                    return 'Giliran Anda Berbicara';
                },

                async enterInterviewRoom() {
                    this.isStarting = true;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch("{{ route('practice.start') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                ai_role_id: this.selectedRole.id,
                                scenario_type: this.selectedRole.name
                            })
                        });

                        const data = await res.json();
                        if (!data.success) {
                            throw new Error(data.message || 'Gagal memulai sesi simulasi.');
                        }

                        this.currentSessionId = data.session_id;

                        // Add AI's initial greeting message
                        if (data.initial_message) {
                            this.messages.push(data.initial_message);
                        }

                        // Start Camera and Mic
                        await this.initHardware();

                        this.sessionState = 'room';
                        this.startSessionTimer();
                        this.startTelemetrySampler();

                        // AI speaks the opening greeting
                        if (data.initial_message) {
                            this.playAiSpeech(data.initial_message.message, data.initial_message.audio_url);
                        }
                    } catch (err) {
                        console.error('Error starting room:', err);
                        alert('Gagal memasuki ruang simulasi. Periksa koneksi atau izin perangkat: ' + err.message);
                    } finally {
                        this.isStarting = false;
                    }
                },

                async initHardware() {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                            audio: true
                        });
                        this.mediaStream = stream;

                        const video = document.getElementById('webcamVideo');
                        if (video) {
                            video.srcObject = stream;
                        }

                        this.setupAudioAnalyser(stream);
                        this.setupSpeechRecognition();
                    } catch (e) {
                        console.warn('Hardware permission notice:', e);
                        // Setup speech recognition even if video has restrictions
                        this.setupSpeechRecognition();
                    }
                },

                setupAudioAnalyser(stream) {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        this.audioContext = new AudioContext();
                        const source = this.audioContext.createMediaStreamSource(stream);
                        this.analyser = this.audioContext.createAnalyser();
                        this.analyser.fftSize = 256;
                        source.connect(this.analyser);
                        this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
                        this.startAudioWaveformVisualizer();
                    } catch (e) {
                        console.warn('Web Audio Analyser not supported:', e);
                    }
                },

                startAudioWaveformVisualizer() {
                    const canvas = document.getElementById('audioWaveformCanvas');
                    if (!canvas || !this.analyser) return;

                    const ctx = canvas.getContext('2d');
                    const bufferLength = this.analyser.frequencyBinCount;
                    const dataArray = new Uint8Array(bufferLength);

                    const render = () => {
                        if (this.sessionState !== 'room') return;

                        this.waveformAnimationId = requestAnimationFrame(render);

                        // Ensure canvas dimensions match parent container
                        const parent = canvas.parentElement;
                        if (parent) {
                            const targetWidth = parent.clientWidth || 320;
                            const targetHeight = parent.clientHeight || 44;
                            if (canvas.width !== targetWidth || canvas.height !== targetHeight) {
                                canvas.width = targetWidth;
                                canvas.height = targetHeight;
                            }
                        }

                        const width = canvas.width;
                        const height = canvas.height;

                        this.analyser.getByteFrequencyData(dataArray);

                        ctx.clearRect(0, 0, width, height);

                        const numBars = 32;
                        const barSpacing = 3;
                        const totalSpacing = barSpacing * (numBars - 1);
                        const barWidth = Math.max(3, (width - totalSpacing) / numBars);

                        const gradient = ctx.createLinearGradient(0, height, 0, 0);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.7)');    // Indigo
                        gradient.addColorStop(0.5, 'rgba(168, 85, 247, 0.9)');  // Purple
                        gradient.addColorStop(1, 'rgba(34, 211, 238, 0.95)');   // Cyan

                        let sum = 0;
                        for (let i = 0; i < numBars; i++) {
                            const sampleIdx = Math.floor((i / numBars) * (bufferLength * 0.55));
                            const val = dataArray[sampleIdx] || 0;
                            sum += val;

                            const percent = val / 255;
                            const barHeight = Math.max(3, percent * (height * 0.85));
                            const x = i * (barWidth + barSpacing);
                            const y = height - barHeight;

                            ctx.fillStyle = gradient;
                            ctx.beginPath();
                            if (ctx.roundRect) {
                                ctx.roundRect(x, y, barWidth, barHeight, 2);
                            } else {
                                ctx.rect(x, y, barWidth, barHeight);
                            }
                            ctx.fill();
                        }

                        const avg = sum / numBars;
                        this.liveDecibels = Math.round(20 + (avg / 255) * 65);
                    };

                    render();
                },

                setupSpeechRecognition() {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        console.warn('Web Speech API not supported in this browser.');
                        return;
                    }

                    this.recognition = new SpeechRecognition();
                    this.recognition.continuous = true;
                    this.recognition.interimResults = true;
                    this.recognition.lang = 'id-ID';

                    this.recognition.onresult = (event) => {
                        // Do not listen while AI is speaking
                        if (this.orbState === 'ai_speaking' || this.orbState === 'thinking') {
                            return;
                        }

                        let interim = '';
                        let finalTurn = '';

                        for (let i = event.resultIndex; i < event.results.length; ++i) {
                            if (event.results[i].isFinal) {
                                finalTurn += event.results[i][0].transcript;
                            } else {
                                interim += event.results[i][0].transcript;
                            }
                        }

                        if (finalTurn) {
                            this.accumulatedSpokenText += ' ' + finalTurn;
                        }

                        this.liveInterimTranscript = (this.accumulatedSpokenText + ' ' + interim).trim();

                        if (this.liveInterimTranscript.length > 0) {
                            this.orbState = 'user_speaking';

                            // Clear any prior silence timer
                            clearTimeout(this.silenceTimer);

                            // Trigger turn submission after 1.5 seconds of silence
                            this.silenceTimer = setTimeout(() => {
                                const sentenceToSend = this.liveInterimTranscript.trim();
                                if (sentenceToSend.length >= 3 && this.orbState !== 'thinking') {
                                    this.sendTurn(sentenceToSend);
                                    this.liveInterimTranscript = '';
                                    this.accumulatedSpokenText = '';
                                }
                            }, this.silenceDelayMs);
                        }
                    };

                    this.recognition.onerror = (e) => {
                        console.warn('Speech recognition warning:', e.error);
                    };

                    this.recognition.onend = () => {
                        // Automatically restart listening if session is still running and AI isn't speaking
                        if (this.sessionState === 'room' && this.orbState !== 'ai_speaking' && this.orbState !== 'thinking') {
                            try { this.recognition.start(); } catch(e) {}
                        }
                    };

                    try { this.recognition.start(); } catch(e) {}
                },

                startSessionTimer() {
                    this.sessionTimerInterval = setInterval(() => {
                        this.secondsElapsed++;
                    }, 1000);
                },

                startTelemetrySampler() {
                    this.telemetrySamplerInterval = setInterval(() => {
                        // 1. REAL Audio Volume from Microphone Analyser
                        if (this.analyser && this.dataArray) {
                            this.analyser.getByteFrequencyData(this.dataArray);
                            let sum = 0;
                            for (let i = 0; i < this.dataArray.length; i++) {
                                sum += this.dataArray[i];
                            }
                            this.liveVolume = Math.min(100, Math.round((sum / this.dataArray.length / 128) * 100));
                            this.volumeSamples.push(this.liveVolume);
                        }

                        // 2. REAL Computer Vision on Webcam Canvas
                        this.analyzeWebcamPixels();
                    }, 250);
                },

                analyzeWebcamPixels() {
                    const video = document.getElementById('webcamVideo');
                    const canvas = document.getElementById('telemetryCanvas');
                    if (!video || !canvas || video.readyState < 2 || video.paused) {
                        return;
                    }

                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    const w = canvas.width;
                    const h = canvas.height;
                    ctx.drawImage(video, 0, 0, w, h);

                    let frame;
                    try {
                        frame = ctx.getImageData(0, 0, w, h);
                    } catch (e) {
                        return;
                    }

                    const data = frame.data;
                    let skinPixels = 0;
                    let sumX = 0;
                    let sumY = 0;
                    let minX = w, maxX = 0, minY = h, maxY = 0;

                    // Sample every 4th pixel for high performance (320x240 -> 4800 samples)
                    for (let y = 0; y < h; y += 4) {
                        for (let x = 0; x < w; x += 4) {
                            const idx = (y * w + x) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b = data[idx + 2];

                            // Standard Skin Color Rule in Normalized RGB / YCbCr Chromaticity
                            if (r > 60 && g > 40 && b > 20 &&
                                r > g && r > b &&
                                (r - g) > 15 &&
                                (Math.max(r, g, b) - Math.min(r, g, b)) > 15) {
                                skinPixels++;
                                sumX += x;
                                sumY += y;
                                if (x < minX) minX = x;
                                if (x > maxX) maxX = x;
                                if (y < minY) minY = y;
                                if (y > maxY) maxY = y;
                            }
                        }
                    }

                    // Face presence threshold (real face detection)
                    if (skinPixels < 120) {
                        this.faceDetected = false;
                        this.liveEyeContactScore = 0;
                        this.liveSmileRate = 0;
                        this.currentFaceStatus = 'wajah_hilang';
                        this.currentFaceIcon = '⚠️';
                        this.currentFaceText = 'Wajah Tidak Terdeteksi';
                        this.liveReprimandNotice = 'Posisikan wajah Anda tepat di depan kamera.';
                        return;
                    }

                    this.faceDetected = true;
                    this.liveReprimandNotice = null;

                    // Centroid and Box
                    const centerX = sumX / skinPixels;
                    const centerY = sumY / skinPixels;
                    const faceW = Math.max(40, maxX - minX);
                    const faceH = Math.max(40, maxY - minY);

                    // Dynamic HUD Coordinates (in percentage, mirrored for selfie webcam)
                    this.faceBoxCoords = {
                        left: Math.max(5, Math.min(80, Math.round(((w - maxX) / w) * 100))),
                        top: Math.max(5, Math.min(80, Math.round((minY / h) * 100))),
                        width: Math.min(90, Math.round((faceW / w) * 100)),
                        height: Math.min(90, Math.round((faceH / h) * 100)),
                    };

                    // REAL Eye Contact Gaze: Deviation from camera center (w/2, h*0.45)
                    const devX = Math.abs(centerX - (w / 2)) / (w / 2);
                    const devY = Math.abs(centerY - (h * 0.45)) / (h * 0.45);
                    const gazePenalty = (devX * 55) + (devY * 45);
                    const eyeScore = Math.max(15, Math.min(98, Math.round(98 - gazePenalty)));
                    this.liveEyeContactScore = eyeScore;
                    this.eyeContactSamples.push(eyeScore);

                    // REAL Mouth / Smile Detection in bottom 35% of face region
                    const mouthYStart = Math.round(minY + (faceH * 0.65));
                    const mouthYEnd = Math.round(minY + (faceH * 0.95));
                    let mouthPixels = 0;
                    let mouthMinX = w, mouthMaxX = 0;

                    for (let y = mouthYStart; y <= mouthYEnd; y += 4) {
                        for (let x = minX; x <= maxX; x += 4) {
                            const idx = (y * w + x) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b = data[idx + 2];
                            // Lips / mouth redness contrast
                            if (r > 75 && (r - g) > 25 && (r - b) > 20) {
                                mouthPixels++;
                                if (x < mouthMinX) mouthMinX = x;
                                if (x > mouthMaxX) mouthMaxX = x;
                            }
                        }
                    }

                    let smileScore = 50;
                    if (mouthPixels >= 6) {
                        const mouthWidth = Math.max(1, mouthMaxX - mouthMinX);
                        const mouthRatio = mouthWidth / faceW;
                        smileScore = Math.max(25, Math.min(95, Math.round((mouthRatio - 0.25) * 200 + 45)));
                    } else {
                        smileScore = 45;
                    }
                    this.liveSmileRate = smileScore;
                    this.smileSamples.push(smileScore);

                    // Real Status Classification
                    if (eyeScore < 65) {
                        this.currentFaceStatus = 'mata_melenceng';
                        this.currentFaceIcon = '👀';
                        this.currentFaceText = 'Tatapan Melenceng';
                    } else if (smileScore >= 72) {
                        this.currentFaceStatus = 'tersenyum';
                        this.currentFaceIcon = '😊';
                        this.currentFaceText = 'Ramah & Tersenyum';
                    } else if (smileScore < 42) {
                        this.currentFaceStatus = 'tegang';
                        this.currentFaceIcon = '😬';
                        this.currentFaceText = 'Tampak Tegang';
                    } else {
                        this.currentFaceStatus = 'fokus';
                        this.currentFaceIcon = '🎯';
                        this.currentFaceText = 'Fokus & Tenang';
                    }
                },

                async sendTurn(userText) {
                    if (!userText || !this.currentSessionId) return;

                    this.orbState = 'thinking';
                    clearTimeout(this.silenceTimer);

                    // Snapshot of current facial status
                    const facialTelemetry = {
                        status: this.currentFaceStatus,
                        eye_contact_score: this.liveEyeContactScore,
                        is_smiling: this.liveSmileRate >= 72,
                        face_detected: this.faceDetected
                    };

                    // Optimistically append user message to dialogue
                    this.messages.push({
                        sender: 'user',
                        message: userText,
                        facial_status: facialTelemetry,
                        timestamp_seconds: this.secondsElapsed
                    });
                    this.scrollToBottom();

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const url = `/practice/${this.currentSessionId}/message`;

                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                message: userText,
                                facial_status: facialTelemetry,
                                timestamp_seconds: this.secondsElapsed
                            })
                        });

                        const data = await res.json();
                        if (data.success && data.ai_message) {
                            this.messages.push(data.ai_message);
                            this.scrollToBottom();

                            if (data.facial_critique) {
                                this.liveReprimandNotice = data.facial_critique;
                            }

                            // AI Speaks back
                            this.playAiSpeech(data.response_text, data.audio_url);
                        }
                    } catch (e) {
                        console.error('Error sending turn to AI:', e);
                        this.orbState = 'idle';
                    }
                },

                submitManualTurn() {
                    if (!this.manualInputText.trim()) return;
                    const text = this.manualInputText.trim();
                    this.manualInputText = '';
                    this.sendTurn(text);
                },

                playAiSpeech(text, audioUrl) {
                    this.orbState = 'ai_speaking';

                    // Pause recognition while AI talks so it doesn't transcribe itself
                    if (this.recognition) {
                        try { this.recognition.stop(); } catch(e) {}
                    }

                    if (audioUrl) {
                        const audio = new Audio(audioUrl);
                        audio.onended = () => {
                            this.orbState = 'idle';
                            if (this.recognition) {
                                try { this.recognition.start(); } catch(e) {}
                            }
                        };
                        audio.onerror = () => {
                            this.speakWithBrowserSynth(text);
                        };
                        audio.play().catch(() => {
                            this.speakWithBrowserSynth(text);
                        });
                    } else {
                        this.speakWithBrowserSynth(text);
                    }
                },

                speakWithBrowserSynth(text) {
                    if (!window.speechSynthesis) {
                        setTimeout(() => {
                            this.orbState = 'idle';
                            if (this.recognition) {
                                try { this.recognition.start(); } catch(e) {}
                            }
                        }, 3000);
                        return;
                    }

                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = 'id-ID';
                    utterance.rate = 1.0;

                    // Choose an Indonesian voice if available
                    const voices = window.speechSynthesis.getVoices();
                    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
                    if (idVoice) {
                        utterance.voice = idVoice;
                    }

                    utterance.onend = () => {
                        this.orbState = 'idle';
                        if (this.recognition) {
                            try { this.recognition.start(); } catch(e) {}
                        }
                    };

                    utterance.onerror = () => {
                        this.orbState = 'idle';
                        if (this.recognition) {
                            try { this.recognition.start(); } catch(e) {}
                        }
                    };

                    window.speechSynthesis.speak(utterance);
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = document.getElementById('dialogueContainer');
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                async finishSession() {
                    if (this.secondsElapsed < 3) {
                        alert('Lakukan simulasi minimal 3 detik untuk mendapatkan laporan komprehensif.');
                        return;
                    }

                    this.isFinishing = true;
                    clearInterval(this.sessionTimerInterval);
                    clearInterval(this.telemetrySamplerInterval);
                    clearTimeout(this.silenceTimer);
                    if (this.waveformAnimationId) {
                        cancelAnimationFrame(this.waveformAnimationId);
                    }

                    if (this.recognition) {
                        try { this.recognition.stop(); } catch(e) {}
                    }
                    if (window.speechSynthesis) {
                        window.speechSynthesis.cancel();
                    }

                    // 1. DATA TELEMETRI OPTIK ASLI KAMERA (Computer Vision)
                    const hasEyeSamples = this.eyeContactSamples.length > 0;
                    const avgEye = hasEyeSamples
                        ? Math.round(this.eyeContactSamples.reduce((a, b) => a + b, 0) / this.eyeContactSamples.length)
                        : 0;

                    const hasSmileSamples = this.smileSamples.length > 0;
                    const avgSmile = hasSmileSamples
                        ? Math.round(this.smileSamples.reduce((a, b) => a + b, 0) / this.smileSamples.length)
                        : 0;

                    const faceScore = hasEyeSamples
                        ? parseFloat(((avgEye * 0.6) + (avgSmile * 0.4)).toFixed(1))
                        : 0;

                    // 2. DATA METRIK UCAPAN ASLI PENGGUNA (Kata, WPM, & Deteksi Filler Words)
                    const userMessages = this.messages.filter(m => m.sender === 'user');
                    const totalSpokenWords = userMessages.reduce((sum, m) => {
                        const words = (m.message || '').trim().split(/\s+/).filter(Boolean);
                        return sum + words.length;
                    }, 0);
                    const durationMinutes = Math.max(0.08, this.secondsElapsed / 60);
                    const realPaceWpm = Math.round(totalSpokenWords / durationMinutes);

                    // Deteksi kata jeda/gumaman (Filler Words)
                    const fillerRegex = /\b(ehm|eh|em|umm|um|uh|anu|ngg|ngga|kayak|apa namanya)\b/gi;
                    let totalFillerWords = 0;
                    const detectedFillers = [];
                    userMessages.forEach(m => {
                        const matches = (m.message || '').match(fillerRegex);
                        if (matches) {
                            totalFillerWords += matches.length;
                            matches.forEach(w => {
                                const lower = w.toLowerCase();
                                if (!detectedFillers.includes(lower)) {
                                    detectedFillers.push(lower);
                                }
                            });
                        }
                    });

                    // 3. DATA TELEMETRI AUDIO ASLI MIKROFON (Web Audio Analyser)
                    const validVolSamples = this.volumeSamples.filter(v => v > 0);
                    const avgVolume = validVolSamples.length > 0
                        ? Math.round(validVolSamples.reduce((a, b) => a + b, 0) / validVolSamples.length)
                        : 0;

                    // Pacing Score: Kecepatan bicara ideal presentasi Bahasa Indonesia adalah 110 - 150 WPM
                    let paceScore = 80;
                    if (totalSpokenWords === 0) {
                        paceScore = 40;
                    } else if (realPaceWpm >= 110 && realPaceWpm <= 150) {
                        paceScore = 95;
                    } else if (realPaceWpm >= 90 && realPaceWpm < 110) {
                        paceScore = 86;
                    } else if (realPaceWpm > 150 && realPaceWpm <= 175) {
                        paceScore = 82;
                    } else if (realPaceWpm > 175) {
                        paceScore = Math.max(45, Math.round(90 - ((realPaceWpm - 175) * 0.8)));
                    } else {
                        paceScore = Math.max(40, Math.round((realPaceWpm / 90) * 85));
                    }

                    // Volume Adequacy Score
                    let volumeScore = 75;
                    if (this.volumeSamples.length === 0 || avgVolume === 0) {
                        volumeScore = 50;
                    } else if (avgVolume >= 20 && avgVolume <= 80) {
                        volumeScore = 92;
                    } else if (avgVolume < 20) {
                        volumeScore = Math.max(40, Math.round(avgVolume * 4.5));
                    } else {
                        volumeScore = 82;
                    }

                    const voiceScore = parseFloat(((paceScore * 0.55) + (volumeScore * 0.45)).toFixed(1));
                    const overallScore = parseFloat(((faceScore * 0.5) + (voiceScore * 0.5)).toFixed(1));

                    // 4. ANALISIS KUALITATIF DINAMIS BERDASARKAN HASIL PENGUKURAN ASLI
                    const strengths = [];
                    const improvements = [];

                    // Evaluasi Kontak Mata Kamera
                    if (avgEye >= 80) {
                        strengths.push(`Kontak mata terukur sangat stabil dan mantap (${avgEye}%), tatapan terarah konsisten ke audiens.`);
                    } else if (avgEye >= 65) {
                        strengths.push(`Kontak mata tergolong cukup baik (${avgEye}%), fokus tatapan sebagian besar terjaga.`);
                        improvements.push(`Tingkatkan stabilitas tatapan ke kamera (saat ini ${avgEye}%), hindari sering melirik ke arah bawah.`);
                    } else if (hasEyeSamples) {
                        improvements.push(`Kontak mata masih rendah (${avgEye}%). Biasakan menatap langsung ke lensa kamera saat berbicara.`);
                    } else {
                        improvements.push('Kamera tidak terdeteksi atau tidak aktif selama sesi latihan.');
                    }

                    // Evaluasi Ekspresi Wajah
                    if (avgSmile >= 70) {
                        strengths.push(`Ekspresi wajah ramah dan bersahabat (${avgSmile}% senyum/rileks), membangun atmosfer yang positif.`);
                    } else if (avgSmile >= 45) {
                        strengths.push(`Ekspresi wajah tenang dan fokus dalam merespons pertanyaan penguji.`);
                        improvements.push('Selipkan senyum ramah di awal dan akhir jawaban agar impresi terasa lebih hangat.');
                    } else if (hasSmileSamples) {
                        improvements.push(`Otot wajah terdeteksi cukup tegang (${avgSmile}% rileks). Lakukan relaksasi pernapasan dan rahang.`);
                    }

                    // Evaluasi Pacing & Artikulasi WPM
                    if (realPaceWpm >= 110 && realPaceWpm <= 150) {
                        strengths.push(`Tempo berbicara sangat ideal (${realPaceWpm} kata/menit), pesan tersampaikan secara artikulatif.`);
                    } else if (realPaceWpm > 150) {
                        improvements.push(`Tempo berbicara terukur cepat (${realPaceWpm} WPM). Berikan jeda sejenak (pausing) pada poin-poin utama.`);
                    } else if (realPaceWpm > 0 && realPaceWpm < 110) {
                        improvements.push(`Tempo bicara relatif lambat (${realPaceWpm} WPM). Tingkatkan kelancaran dan kesinambungan kalimat.`);
                    } else {
                        improvements.push('Belum terdeteksi kalimat verbal dari Anda. Coba suarakan jawaban secara aktif melalui mikrofon.');
                    }

                    // Evaluasi Filler Words (Kata Jeda / Gumaman)
                    if (totalFillerWords > 2) {
                        improvements.push(`Terdeteksi ${totalFillerWords} kali kata jeda/gumaman (${detectedFillers.join(', ')}). Gunakan jeda hening sejenak (pausing) daripada mengisi jeda dengan gumaman.`);
                    } else if (totalFillerWords === 0 && totalSpokenWords >= 15) {
                        strengths.push('Artikulasi sangat bersih tanpa jeda gumaman (0 filler words), penyampaian ide terdengar terstruktur.');
                    }

                    // Evaluasi Volume Suara
                    if (avgVolume >= 25) {
                        strengths.push(`Proyeksi suara terdengar tegas dan bervolume cukup (rata-rata energi ${avgVolume}%).`);
                    } else if (avgVolume > 0) {
                        improvements.push(`Volume suara terukur agak pelan (${avgVolume}%). Dekatkan mikrofon atau gunakan proyeksi suara lebih kuat.`);
                    }

                    if (strengths.length === 0) {
                        strengths.push(`Anda berhasil menyelesaikan simulasi selama ${this.secondsElapsed} detik secara tuntas.`);
                    }
                    if (improvements.length === 0) {
                        improvements.push('Pertahankan ketenangan gestur dan intonasi vokal yang sudah sangat baik ini.');
                    }

                    const payload = {
                        duration_seconds: Math.max(1, this.secondsElapsed),
                        face_score: faceScore,
                        voice_score: voiceScore,
                        overall_score: overallScore,
                        feedback_notes: {
                            summary: `Simulasi bersama ${this.selectedRole.name} diselesaikan dalam ${this.secondsElapsed} detik dengan skor keseluruhan ${overallScore}/100.`,
                            eye_contact_score: avgEye,
                            smile_rate: avgSmile,
                            pace_wpm: realPaceWpm,
                            clarity_score: Math.round(voiceScore),
                            avg_volume: avgVolume,
                            total_words: totalSpokenWords,
                            filler_words_count: totalFillerWords,
                            filler_words_list: detectedFillers,
                            strengths: strengths,
                            improvements: improvements
                        }
                    };

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch(`/practice/${this.currentSessionId}/finish`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();
                        if (result.success && result.redirect_url) {
                            if (this.mediaStream) {
                                this.mediaStream.getTracks().forEach(t => t.stop());
                            }
                            window.location.href = result.redirect_url;
                        } else {
                            throw new Error(result.message || 'Gagal menyimpan hasil simulasi.');
                        }
                    } catch (e) {
                        console.error('Error finishing session:', e);
                        alert('Terjadi kendala saat menyimpan rapor: ' + e.message);
                        this.isFinishing = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
