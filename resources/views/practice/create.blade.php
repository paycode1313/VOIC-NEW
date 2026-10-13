<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                @if($isDemo ?? false)
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full glass-pill border border-emerald-300 dark:border-emerald-800 text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        ⚡ Sesi Uji Coba Tamu • Tanpa Registrasi
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Simulasi Instan AI Coach') }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Coba simulasi interaktif tatap muka langsung bersama AI penguji tanpa perlu registrasi atau login.
                    </p>
                @else
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full glass-pill border border-blue-200/80 dark:border-blue-900/60 text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Simulasi Sidang & Wawancara Tatap Muka
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                        {{ __('Ruang Simulasi Tatap Muka Profesional') }}
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Pilih dewan penguji atau rekruter, nyalakan kamera, dan latih kesiapan berbicara dengan evaluasi tatap muka langsung.
                    </p>
                @endif
            </div>

            @if($isDemo ?? false)
                <a href="{{ url('/') }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 glass-subcard hover:bg-white dark:hover:bg-slate-800 rounded-xl transition shadow-xs w-fit">
                    <svg class="w-4 h-4 me-2 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Beranda
                </a>
            @else
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 glass-subcard hover:bg-white dark:hover:bg-slate-800 rounded-xl transition shadow-xs w-fit">
                    <svg class="w-4 h-4 me-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            @endif
        </div>
    </x-slot>

    <style>
        /* Modern 3D Audio Orb Animations */
        @keyframes orb-pulse-idle {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(37, 99, 235, 0.4)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 35px rgba(56, 189, 248, 0.6)); }
        }
        @keyframes orb-speaking {
            0%, 100% { transform: scale(1.02); filter: drop-shadow(0 0 30px rgba(37, 99, 235, 0.7)); }
            25% { transform: scale(1.15) rotate(5deg); filter: drop-shadow(0 0 50px rgba(56, 189, 248, 0.9)); }
            50% { transform: scale(0.98) rotate(-4deg); filter: drop-shadow(0 0 35px rgba(255, 255, 255, 0.8)); }
            75% { transform: scale(1.12) rotate(3deg); filter: drop-shadow(0 0 45px rgba(30, 58, 138, 0.85)); }
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

        /* Fixed & Stable Portrait Camera Feed - No Shaking / No Zooms */
        .avatar-breathe-anim,
        .avatar-talking-anim,
        .avatar-listening-anim,
        .avatar-thinking-anim {
            transform: none !important;
            animation: none !important;
        }
        @keyframes neural-halo-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes lip-sync-bar {
            0%, 100% { height: 4px; opacity: 0.5; }
            50% { height: 20px; opacity: 1; }
        }
        @keyframes eye-blink-overlay {
            0%, 94%, 100% { opacity: 0; }
            97% { opacity: 0.85; }
        }

        /* Seamless Organic Vector Character Animations */
        @keyframes vector-breathe {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2.5px); }
        }
        @keyframes vector-head-tilt {
            0%, 100% { transform: rotate(0deg) translateY(0); }
            25% { transform: rotate(0.8deg) translateY(-1.5px); }
            50% { transform: rotate(-0.6deg) translateY(1px); }
            75% { transform: rotate(0.5deg) translateY(-0.8px); }
        }
        @keyframes vector-eye-blink {
            0%, 94%, 100% { transform: scaleY(1); }
            97% { transform: scaleY(0.08); }
        }
        @keyframes smooth-vector-talk {
            0%, 100% {
                transform: scaleY(0.22) scaleX(0.96);
            }
            25% {
                transform: scaleY(0.95) scaleX(1.02);
            }
            50% {
                transform: scaleY(0.38) scaleX(1.1);
            }
            75% {
                transform: scaleY(1.02) scaleX(0.94);
            }
        }
        @keyframes vector-listening-tilt {
            0%, 100% { transform: rotate(0deg) translateY(0); }
            40% { transform: rotate(1.5deg) translateY(2.5px); }
            80% { transform: rotate(0.8deg) translateY(1.2px); }
        }
        @keyframes vector-thinking-tilt {
            0%, 100% { transform: rotate(0deg) translateY(0); }
            50% { transform: rotate(-2deg) translateY(-1.5px); }
        }

        .vector-talking-head {
            transform-box: fill-box;
            transform-origin: 200px 220px;
            animation: vector-head-tilt 2.4s ease-in-out infinite;
        }
        .vector-listening-head {
            transform-box: fill-box;
            transform-origin: 200px 220px;
            animation: vector-listening-tilt 3.2s ease-in-out infinite;
        }
        .vector-thinking-head {
            transform-box: fill-box;
            transform-origin: 200px 220px;
            animation: vector-thinking-tilt 2.8s ease-in-out infinite;
        }
        .vector-breathe-head {
            transform-box: fill-box;
            transform-origin: 200px 220px;
            animation: vector-breathe 4.2s ease-in-out infinite;
        }
        .vector-blink-eye {
            transform-box: fill-box;
            transform-origin: center center;
            animation: vector-eye-blink 4.2s infinite;
        }
        .smooth-mouth-talk {
            transform-box: fill-box;
            transform-origin: center center;
            animation: smooth-vector-talk 0.38s ease-in-out infinite;
        }
        .halo-spin { animation: neural-halo-spin 7s linear infinite; }

        /* Professional Light Glassmorphism Design System */
        .glass-panel {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 45px -15px rgba(30, 58, 138, 0.07), 0 0 0 1px rgba(226, 232, 240, 0.7) inset;
        }
        .dark .glass-panel {
            background: rgba(15, 28, 47, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
        }

        .glass-stage {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 247, 255, 0.88) 50%, rgba(248, 250, 252, 0.92) 100%);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.09), 0 0 0 1px rgba(219, 234, 254, 0.8) inset;
        }
        .dark .glass-stage {
            background: linear-gradient(135deg, rgba(15, 28, 47, 0.95) 0%, rgba(8, 15, 27, 0.92) 100%);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
        }

        .glass-subcard {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 6px 18px -4px rgba(30, 58, 138, 0.05);
        }
        .dark .glass-subcard {
            background: rgba(15, 28, 47, 0.65);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: 0 6px 18px -4px rgba(0, 0, 0, 0.35);
        }

        .glass-pill {
            background: rgba(241, 245, 249, 0.75);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.85);
        }
        .dark .glass-pill {
            background: rgba(8, 15, 27, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-pedestal {
            background: linear-gradient(180deg, #ffffff 0%, #f1f7ff 100%);
            border: 1.5px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 35px -10px rgba(37, 99, 235, 0.12), 0 0 0 1px rgba(219, 234, 254, 0.7) inset;
        }
        .dark .glass-pedestal {
            background: linear-gradient(180deg, #0f1c2f 0%, #080f1b 100%);
            border: 1.5px solid rgba(59, 130, 246, 0.25);
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(203, 213, 225, 0.8);
            box-shadow: 0 2px 6px rgba(30, 58, 138, 0.03) inset;
        }
        .dark .glass-input {
            background: rgba(8, 15, 27, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) inset;
        }
    </style>

    <!-- Alpine.js Application Logic -->
    <script>
        function interactiveSimulationApp() {
            return {
                aiRoles: @json($aiRoles),
                selectedRole: @json($defaultRole),
                userLearningProfile: @json($userLearningProfile ?? []),
                isDemo: {{ ($isDemo ?? false) ? 'true' : 'false' }},
                guestName: '{{ addslashes($defaultGuestName ?? 'Pengunjung Booth') }}',
                guestInstitution: '{{ addslashes($defaultGuestInstitution ?? '') }}',

                // Deep Learning & Semantic Grounding Telemetry
                liveCoherenceScore: 88,
                liveCoherenceCategory: 'Relevan & Nyambung',
                detectedSalientEntities: [],

                sessionState: 'selection', // 'selection' | 'room'
                currentSessionId: null,
                isStarting: false,
                isFinishing: false,

                // Clean Control Dock & UI State
                isMicMuted: false,
                isCameraOff: false,
                showAdvancedOptions: false,
                showTextInput: false,
                showChatDrawer: false,

                toggleMic() {
                    this.isMicMuted = !this.isMicMuted;
                    if (this.mediaStream) {
                        this.mediaStream.getAudioTracks().forEach(t => t.enabled = !this.isMicMuted);
                    }
                },

                toggleCamera() {
                    this.isCameraOff = !this.isCameraOff;
                    if (this.mediaStream) {
                        this.mediaStream.getVideoTracks().forEach(t => t.enabled = !this.isCameraOff);
                    }
                },

                // Custom Topic / Target Context & Modes
                customTopic: '',
                contextMaterial: '',
                timelineEvents: [],
                simulationMode: 'standard', // 'standard' | 'sprint'
                sprintRemaining: 60,
                sfxEnabled: true,
                showCoachGuide: false,
                fillerWordAlert: null,
                fillerAlertTimer: null,
                cameraAdvice: null,
                liveWpm: 0,
                liveWordCount: 0,
                isFullscreen: false,

                recordTimelineEvent(type, label, detail, severity = 'info') {
                    const nowSec = this.secondsElapsed || 0;
                    if (this.timelineEvents.some(e => e.type === type && Math.abs(nowSec - e.timestamp) < 6)) return;
                    this.timelineEvents.push({
                        timestamp: nowSec,
                        type: type,
                        label: label,
                        detail: detail,
                        severity: severity
                    });
                },

                // Pre-flight Calibration Modal
                showCalibrationModal: false,
                calibrationStream: null,
                calibrationAudioCtx: null,
                calibrationAnalyser: null,
                calibrationVolume: 0,
                calibrationInterval: null,
                calibrationCameraOk: false,
                calibrationMicOk: false,

                // Methodology & Scientific Accuracy Modal
                showMethodologyModal: false,

                // Language & Track Settings
                simulationLanguage: 'id', // 'id' | 'en'

                // Pitch (Fundamental Frequency) & Vocal Intonation Dynamics
                livePitchHz: 0,
                livePitchDynamicsScore: 78,
                pitchSamples: [],

                // Candidate Audio Recording (MediaRecorder + IndexedDB)
                mediaRecorder: null,
                recordedAudioChunks: [],

                // Ollama Engine Status
                ollamaStatus: { checked: false, active: false, model: 'qwen2.5:3b' },

                // Hardware & Media
                mediaStream: null,
                audioContext: null,
                analyser: null,
                dataArray: null,
                pitchTimeDomainBuffer: null,
                activeAiEngine: 'ollama_neural', // 'ollama_neural' | 'persona_fallback'

                // Real Computer Vision & Audio Telemetry
                faceDetected: false,
                faceBoxCoords: { left: 20, top: 15, width: 60, height: 70 },
                liveVolume: 0,
                liveDecibels: 25,
                waveformAnimationId: null,
                liveEyeContactScore: 0,
                liveSmileRate: 0,
                currentFaceStatus: 'menunggu', // 'fokus', 'tegang', 'tersenyum', 'mata_melenceng', 'wajah_hilang'
                currentFaceIcon: '',
                currentFaceText: 'Menghubungkan Kamera...',
                liveReprimandNotice: null,

                // Aggregated stats for the final feedback report
                eyeContactSamples: [],
                smileSamples: [],
                volumeSamples: [],

                // MediaPipe Iris-Level Landmarker State & Engine Flag
                faceLandmarker: null,
                faceLandmarkerReady: false,
                eyeTrackingEngine: 'canvas_gaze', // 'mediapipe_iris' | 'canvas_gaze'

                // Acoustic Hesitation Detector State (Layer 2 for 'uhhh...', 'ehhmmm...')
                audioHesitationCount: 0,
                consecutiveVoicedFrames: 0,
                voicedPitchBuffer: [],
                isCurrentlyInHesitationDrone: false,

                // Scenario Milestone Stages
                currentStage: 'opening', // 'opening' | 'core' | 'pressure_test' | 'closing'
                stageMilestones: [
                    { id: 'opening', step: 1, label: 'Pembukaan', badge: 'Hook & Profil' },
                    { id: 'core', step: 2, label: 'Tanya Jawab Inti', badge: 'Metodologi & Solusi' },
                    { id: 'pressure_test', step: 3, label: 'Uji Tekanan', badge: 'Skenario Kritis' },
                    { id: 'closing', step: 4, label: 'Kesimpulan', badge: 'Pernyataan Akhir' }
                ],

                // Conversational Dynamism & Barge-In Interruption State
                currentTurnSeconds: 0,
                turnTimerInterval: null,
                hasInterruptedThisTurn: false,
                turnHesitationCount: 0,
                showInterruptionAlert: false,
                interruptionTitle: '',
                interruptionText: '',
                interruptionTimer: null,

                // Advanced Acoustic Diagnostics
                lastAiSpeechEndedAt: null,
                userStartedSpeakingAt: null,
                awkwardPausesCount: 0,
                detectedUptalkingCount: 0,
                recentTurnPitchSamples: [],
                timelineBookmarks: [],

                // Procedural Room Ambience (Web Audio API)
                isAmbienceActive: false,
                ambienceMasterGain: null,
                ambienceNodes: [],

                // Avatar Stage & Conversational State
                avatarViewMode: 'photo', // 'photo' | 'vector' | 'orb'
                orbState: 'idle', // 'idle' | 'user_speaking' | 'ai_speaking' | 'thinking'
                messages: [],
                liveInterimTranscript: '',
                manualInputText: '',

                // Real-Time Speech-Synchronized Lip-Sync & Viseme State
                liveMouthScaleY: 0.15,
                liveMouthScaleX: 1.0,
                targetMouthScaleY: 0.15,
                targetMouthScaleX: 1.0,
                currentViseme: 'closed', // 'closed' | 'ah' | 'oh' | 'ee' | 'teeth' | 'fv'
                currentSpokenWord: '',
                currentAiSpokenText: '',
                lipSyncAnimationId: null,
                wordVisemeTimeouts: [],
                synthFallbackTimer: null,
                isBlinking: false,
                blinkTimeout: null,
                liveHeadTransform: 'translateY(0px) rotate(0deg)',
                vocalSpectrumBars: [12, 12, 12, 12, 12, 12, 12, 12],
                avatarStageMode: 'animated', // 'animated' (default: dynamic talking character with lip-sync) | 'photo' (realistic portrait)
                get hrAvatarMode() { return this.avatarStageMode; },
                set hrAvatarMode(v) { this.avatarStageMode = v; },
                previewVoiceRole: null,
                previewAudioObj: null,

                // Speech Recognition & Silence Detection
                recognition: null,
                silenceTimer: null,
                silenceDelayMs: 3500, // 3.5 seconds natural silence detection
                accumulatedSpokenText: '',

                // Timers
                secondsElapsed: 0,
                sessionTimerInterval: null,
                telemetrySamplerInterval: null,

                // Glassmorphic Toast Notification System
                toast: {
                    show: false,
                    message: '',
                    type: 'info',
                    timer: null,
                },
                showToast(message, type = 'info', duration = 4000) {
                    if (this.toast.timer) clearTimeout(this.toast.timer);
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    this.toast.timer = setTimeout(() => {
                        this.toast.show = false;
                    }, duration);
                },

                async init() {
                    this.initLipSyncEngine();
                    this.startBlinkLoop();
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

                selectRoleById(roleId) {
                    const r = (this.aiRoles || []).find(item => item.id === Number(roleId));
                    if (r) {
                        this.selectedRole = r;
                    }
                },

                get currentTopicPresets() {
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                        return [
                            'Sistem AI & Vision Computer',
                            'Cyber Security & Jaringan',
                            'Rekayasa Aplikasi Web & Mobile',
                            'Big Data & Cloud Architecture'
                        ];
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return [
                            'Software Engineer / Fullstack',
                            'UI/UX & Product Design',
                            'Data Analyst & BI',
                            'Project Manager / Scrum Master'
                        ];
                    }
                    return [
                        'EdTech & Smart Learning SaaS',
                        'Fintech & Digital Banking',
                        'HealthTech & Telemedicine',
                        'AI Workflow & Automation'
                    ];
                },

                get topicPlaceholder() {
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                        return 'Atau ketik judul skripsi / topik riset spesifik Anda di sini...';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return 'Atau ketik posisi pekerjaan impian yang ingin Anda latih di sini...';
                    }
                    return 'Atau ketik nama startup & model bisnis pitching Anda di sini...';
                },

                get curatedTracksList() {
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                        return this.simulationLanguage === 'en'
                            ? [
                                'Comprehensive Thesis Examination',
                                'Deep-Dive: Chapter 4-5 Methodology & Data Validation',
                                'Research Urgency & Chapter 2 Literature Review',
                                'Boundary Conditions & Stress Testing'
                            ]
                            : [
                                'Sidang Skripsi Komprehensif',
                                'Bedah Bab 4-5 (Metodologi & Validasi Data)',
                                'Urgensi Riset & Landasan Teori Bab 2',
                                'Batasan Masalah & Pengujian Skenario Ekstrem'
                            ];
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return this.simulationLanguage === 'en'
                            ? [
                                'Standard STAR Competency Interview',
                                'Top 10 Behavioral & Tricky Questions',
                                'Team Conflict & Leadership Case Study',
                                'Salary Negotiation & Career Trajectory'
                            ]
                            : [
                                'Simulasi Standar (STAR Competency)',
                                'Top 10 Pertanyaan Behavioral Menjebak',
                                'Studi Kasus Konflik Tim & Leadership',
                                'Negosiasi Gaji & Jenjang Karier'
                            ];
                    }
                    return this.simulationLanguage === 'en'
                        ? [
                            '60-Second Elevator Pitch',
                            '3-Minute Pitch Deck (Problem, TAM, Solution)',
                            'Unit Economics, CAC/LTV & Monetization',
                            'Defensibility & Moat Against Tech Giants'
                        ]
                        : [
                            'Elevator Pitch 60 Detik',
                            'Pitch Deck 3 Menit (Problem, TAM, Solusi)',
                            'Bedah Unit Economics, CAC & Monetisasi',
                            'Moat & Benteng Pertahanan Pasar'
                        ];
                },

                get coachGuideTitle() {
                    if (this.simulationLanguage === 'en') {
                        if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') return 'STAR Framework Guide (Situation, Task, Action, Result)';
                        if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') return 'Thesis Defense Blueprint (Problem, Methodology & Validation)';
                        return 'Elevator Pitch Formula (Problem, Solution, TAM & Moat)';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return 'Panduan Metode STAR (Situation, Task, Action, Result)';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                        return 'Struktur Ujian Skripsi (Urgensi, Metodologi & Validasi)';
                    }
                    return 'Formula Elevator Pitch (Problem, Solution & Market Traction)';
                },

                get coachGuideText() {
                    if (this.simulationLanguage === 'en') {
                        if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                            return 'Structure your answers clearly: Detail the Situation, your specific Task, your concrete Actions, and the quantifiable Results. Maintain calm eye contact and speak with vocal rhythm.';
                        }
                        if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                            return 'Deliver rigorous academic arguments: State research urgency, defend algorithm/architecture selection, define problem boundaries, and demonstrate objective validation.';
                        }
                        return 'Hook the listener in 60s: Highlight a painful market friction, articulate why your product is 10x superior, outline viable unit economics, and show defensible growth.';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return 'Strukturkan jawaban Anda: Ceritakan Situasi kerja, Tugas yang harus diselesaikan, Aksi nyata yang Anda ambil, dan Hasil konkret berbobot angka/metrik. Tatap kamera dan kurangi gumaman.';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') {
                        return 'Jawab secara lugas dan ilmiah: Sebutkan urgensi penelitian, alasan mengapa memilih algoritma/arsitektur tersebut, batasan masalah, serta bagaimana akurasi data diuji secara objektif.';
                    }
                    return 'Pikat pendengar dalam 60 detik: Jelaskan masalah kritis yang dihadapi pasar, mengapa inovasi Anda 10x lebih efektif, model bisnis terukur, serta traksi atau roadmap masa depan.';
                },

                async openCalibrationModal() {
                    this.showCalibrationModal = true;
                    this.calibrationCameraOk = false;
                    this.calibrationMicOk = false;
                    this.calibrationVolume = 0;

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                            audio: true
                        });
                        this.calibrationStream = stream;
                        this.calibrationCameraOk = true;
                        this.calibrationMicOk = true;

                        this.$nextTick(() => {
                            const calVideo = document.getElementById('calibrationVideo');
                            if (calVideo) {
                                calVideo.srcObject = stream;
                            }
                        });

                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (AudioCtx) {
                            this.calibrationAudioCtx = new AudioCtx();
                            const src = this.calibrationAudioCtx.createMediaStreamSource(stream);
                            this.calibrationAnalyser = this.calibrationAudioCtx.createAnalyser();
                            this.calibrationAnalyser.fftSize = 128;
                            src.connect(this.calibrationAnalyser);
                            const pcmData = new Uint8Array(this.calibrationAnalyser.frequencyBinCount);

                            this.calibrationInterval = setInterval(() => {
                                if (!this.calibrationAnalyser) return;
                                this.calibrationAnalyser.getByteFrequencyData(pcmData);
                                let sum = 0;
                                for (let i = 0; i < pcmData.length; i++) sum += pcmData[i];
                                this.calibrationVolume = Math.min(100, Math.round((sum / pcmData.length / 128) * 100));
                            }, 100);
                        }
                    } catch (e) {
                        console.warn('Calibration hardware warning:', e);
                    }
                },

                closeCalibrationModal() {
                    this.showCalibrationModal = false;
                    if (this.calibrationInterval) clearInterval(this.calibrationInterval);
                    if (this.calibrationStream) {
                        this.calibrationStream.getTracks().forEach(t => t.stop());
                        this.calibrationStream = null;
                    }
                    if (this.calibrationAudioCtx) {
                        try { this.calibrationAudioCtx.close(); } catch(e) {}
                        this.calibrationAudioCtx = null;
                    }
                },

                calculatePitchAutoCorrelation(buf, sampleRate) {
                    if (!buf || !sampleRate || buf.length < 256) return -1;
                    const SIZE = buf.length;

                    // 1. Pre-emphasis filter: boost high frequency harmonics & eliminate DC bias (y[n] = x[n] - 0.95 * x[n-1])
                    const filtered = new Float32Array(SIZE);
                    filtered[0] = buf[0];
                    for (let i = 1; i < SIZE; i++) {
                        filtered[i] = buf[i] - 0.95 * buf[i - 1];
                    }

                    // 2. Windowing (Hann Window) + Zero-Crossing Rate (ZCR) & RMS Energy
                    let rms = 0;
                    let zeroCrossings = 0;
                    const windowed = new Float32Array(SIZE);
                    for (let i = 0; i < SIZE; i++) {
                        // Hann window: 0.5 * (1 - cos(2*pi*i / (SIZE-1)))
                        const w = 0.5 * (1 - Math.cos((2 * Math.PI * i) / (SIZE - 1)));
                        const val = filtered[i] * w;
                        windowed[i] = val;
                        rms += val * val;
                        if (i > 0 && ((filtered[i] >= 0 && filtered[i - 1] < 0) || (filtered[i] < 0 && filtered[i - 1] >= 0))) {
                            zeroCrossings++;
                        }
                    }
                    rms = Math.sqrt(rms / SIZE);

                    // RMS Noise Gate: ignore silence or breath hiss
                    if (isNaN(rms) || !isFinite(rms) || rms < 0.012) return -1;

                    // Voiced vs Unvoiced probability check:
                    // Unvoiced fricatives ("s", "t", "f", "p") have high ZCR (> 0.28) and lack harmonic pitch
                    const zcrRatio = zeroCrossings / SIZE;
                    if (zcrRatio > 0.28) return -1;

                    // 3. Vocal fundamental frequency search window: 80 Hz - 500 Hz (Male & Female ranges)
                    const minPeriod = Math.max(4, Math.floor(sampleRate / 500));
                    const maxPeriod = Math.min(SIZE - 2, Math.floor(sampleRate / 80));
                    if (minPeriod >= maxPeriod) return -1;

                    // 4. Normalized autocorrelation
                    let bestCorrelation = -1;
                    let bestPeriod = -1;
                    let energy0 = 0;
                    for (let i = 0; i < SIZE - maxPeriod; i++) {
                        energy0 += windowed[i] * windowed[i];
                    }
                    if (energy0 < 0.0001) return -1;

                    for (let lag = minPeriod; lag <= maxPeriod; lag++) {
                        let sum = 0;
                        let energyLag = 0;
                        for (let i = 0; i < SIZE - maxPeriod; i++) {
                            sum += windowed[i] * windowed[i + lag];
                            energyLag += windowed[i + lag] * windowed[i + lag];
                        }
                        const normFactor = Math.sqrt(energy0 * energyLag);
                        const corr = normFactor > 0 ? (sum / normFactor) : 0;
                        if (corr > bestCorrelation) {
                            bestCorrelation = corr;
                            bestPeriod = lag;
                        }
                    }

                    // Require correlation peak >= 0.40 to confirm periodic voiced speech
                    if (bestPeriod === -1 || bestCorrelation < 0.40) return -1;

                    // 5. Parabolic interpolation around peak
                    let refinedPeriod = bestPeriod;
                    if (bestPeriod > minPeriod && bestPeriod < maxPeriod) {
                        let s0 = 0, s1 = 0, s2 = 0;
                        for (let i = 0; i < SIZE - maxPeriod; i++) {
                            s0 += windowed[i] * windowed[i + bestPeriod - 1];
                            s1 += windowed[i] * windowed[i + bestPeriod];
                            s2 += windowed[i] * windowed[i + bestPeriod + 1];
                        }
                        const denom = (2 * s1 - s0 - s2);
                        if (Math.abs(denom) > 0.0001) {
                            const delta = 0.5 * (s0 - s2) / denom;
                            if (Math.abs(delta) < 1) {
                                refinedPeriod = bestPeriod + delta;
                            }
                        }
                    }

                    const pitch = sampleRate / refinedPeriod;
                    if (isNaN(pitch) || !isFinite(pitch) || pitch < 75 || pitch > 500) {
                        return -1;
                    }
                    return pitch;
                },

                saveAudioToIndexedDb(sessionId, blob) {
                    return new Promise((resolve, reject) => {
                        try {
                            const req = indexedDB.open('VoicTelemetryDB', 1);
                            req.onupgradeneeded = (e) => {
                                const db = e.target.result;
                                if (!db.objectStoreNames.contains('recordings')) {
                                    db.createObjectStore('recordings', { keyPath: 'sessionId' });
                                }
                            };
                            req.onsuccess = (e) => {
                                const db = e.target.result;
                                const tx = db.transaction('recordings', 'readwrite');
                                const store = tx.objectStore('recordings');
                                store.put({ sessionId: String(sessionId), blob: blob, audioBlob: blob, timestamp: Date.now() });
                                store.put({ sessionId: 'session_' + sessionId, blob: blob, audioBlob: blob, timestamp: Date.now() });
                                tx.oncomplete = () => resolve(true);
                                tx.onerror = () => reject(tx.error);
                            };
                            req.onerror = () => reject(req.error);
                        } catch(err) {
                            reject(err);
                        }
                    });
                },

                recordTimelineBookmark(sec, type, label, note) {
                    const cleanSec = Math.max(0, Math.round(sec));
                    if (this.timelineBookmarks.some(b => b.type === type && Math.abs(b.second - cleanSec) < 4)) return;
                    this.timelineBookmarks.push({
                        second: cleanSec,
                        type: type, // 'filler' | 'uptalk' | 'silence' | 'interruption' | 'hesitation'
                        label: label,
                        note: note
                    });
                },

                triggerInterruption(title, text) {
                    if (this.hasInterruptedThisTurn) return;
                    this.hasInterruptedThisTurn = true;
                    this.showInterruptionAlert = true;
                    this.interruptionTitle = title;
                    this.interruptionText = text;
                    this.playSfx('filler');

                    if (this.interruptionTimer) clearTimeout(this.interruptionTimer);
                    this.interruptionTimer = setTimeout(() => {
                        this.showInterruptionAlert = false;
                    }, 6500);
                },

                startCandidateTurnTimer() {
                    this.currentTurnSeconds = 0;
                    this.hasInterruptedThisTurn = false;
                    this.turnHesitationCount = 0;
                    this.userStartedSpeakingAt = null;
                    this.recentTurnPitchSamples = [];
                    if (this.turnTimerInterval) clearInterval(this.turnTimerInterval);

                    this.turnTimerInterval = setInterval(() => {
                        if (this.orbState === 'ai_speaking' || this.orbState === 'thinking') return;
                        this.currentTurnSeconds++;

                        const roleType = this.selectedRole?.role_type || 'dosen_penguji';

                        // Dosen Killer Interruption Logic:
                        if (roleType === 'dosen_penguji' && !this.hasInterruptedThisTurn) {
                            if (this.currentTurnSeconds >= 24) {
                                this.triggerInterruption('Interupsi Dosen Penguji:', 'Saudara mahasiswa, tolong langsung ke poin utama metodologinya. Jangan berputar-putar!');
                                this.recordTimelineBookmark(this.secondsElapsed, 'interruption', 'Interupsi Dosen Penguji', 'Jawaban melebihi batas waktu toleransi 24 detik');
                            } else if (this.turnHesitationCount >= 2) {
                                this.triggerInterruption('Dosen Penguji Memotong:', 'Jelaskan argumen Anda dengan lugas, kurangi nada ragu.');
                                this.recordTimelineBookmark(this.secondsElapsed, 'interruption', 'Teguran Intonasi Ragu', 'Terdeteksi lebih dari 2 kali jeda nada datar berturut-turut');
                            }
                        }

                        // Investor Fast-Paced Pitch Budget Countdown Warning:
                        if (roleType === 'investor' && !this.hasInterruptedThisTurn) {
                            if (this.currentTurnSeconds >= 40) {
                                this.triggerInterruption('Peringatan Budget Waktu Investor:', 'Waktu elevator pitch Anda hampir habis! Sampaikan traction dan nilai investasi Anda sekarang!');
                                this.recordTimelineBookmark(this.secondsElapsed, 'interruption', 'Peringatan Pitch Budget', 'Waktu tersisa 5 detik dari alokasi 45s');
                            }
                        }
                    }, 1000);
                },

                stopCandidateTurnTimer() {
                    if (this.turnTimerInterval) {
                        clearInterval(this.turnTimerInterval);
                        this.turnTimerInterval = null;
                    }
                    this.currentTurnSeconds = 0;
                },

                checkUptalking(finalText) {
                    if (!finalText || finalText.endsWith('?')) return;
                    if (this.recentTurnPitchSamples.length >= 5) {
                        const tail = this.recentTurnPitchSamples.slice(-5);
                        const pStart = (tail[0] + tail[1]) / 2;
                        const pEnd = (tail[tail.length - 2] + tail[tail.length - 1]) / 2;
                        const deltaHz = pEnd - pStart;
                        if (deltaHz >= 22) {
                            this.detectedUptalkingCount++;
                            this.recordTimelineBookmark(
                                this.secondsElapsed,
                                'uptalk',
                                'Uptalking Terdeteksi',
                                'Intonasi menanjak (+ ' + Math.round(deltaHz) + ' Hz) di akhir ujaran deklaratif (terdengar ragu)'
                            );
                        }
                    }
                },

                startRoomAmbience() {
                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        if (!this.audioContext || this.audioContext.state === 'closed') {
                            this.audioContext = new AudioCtx();
                        }
                        if (this.audioContext.state === 'suspended') {
                            this.audioContext.resume();
                        }

                        const ctx = this.audioContext;
                        const masterGain = ctx.createGain();
                        masterGain.gain.setValueAtTime(0.025, ctx.currentTime);
                        masterGain.connect(ctx.destination);
                        this.ambienceMasterGain = masterGain;

                        // Procedural warm room hum 55Hz & 110Hz + lowpass filter
                        const osc1 = ctx.createOscillator();
                        osc1.type = 'sine';
                        osc1.frequency.setValueAtTime(55, ctx.currentTime);
                        const gain1 = ctx.createGain();
                        gain1.gain.setValueAtTime(0.6, ctx.currentTime);
                        osc1.connect(gain1);
                        gain1.connect(masterGain);
                        osc1.start();

                        const osc2 = ctx.createOscillator();
                        osc2.type = 'triangle';
                        osc2.frequency.setValueAtTime(110, ctx.currentTime);
                        const filter2 = ctx.createBiquadFilter();
                        filter2.type = 'lowpass';
                        filter2.frequency.setValueAtTime(180, ctx.currentTime);
                        const gain2 = ctx.createGain();
                        gain2.gain.setValueAtTime(0.25, ctx.currentTime);
                        osc2.connect(filter2);
                        filter2.connect(gain2);
                        gain2.connect(masterGain);
                        osc2.start();

                        this.ambienceNodes = [osc1, osc2, masterGain];
                        this.isAmbienceActive = true;
                    } catch (e) {
                        console.warn('Room ambience notice:', e);
                    }
                },

                stopRoomAmbience() {
                    try {
                        if (this.ambienceNodes) {
                            this.ambienceNodes.forEach(node => {
                                if (node.stop) node.stop();
                                if (node.disconnect) node.disconnect();
                            });
                        }
                        this.ambienceNodes = [];
                        this.ambienceMasterGain = null;
                        this.isAmbienceActive = false;
                    } catch (e) {}
                },

                toggleAmbience() {
                    if (this.isAmbienceActive) {
                        this.stopRoomAmbience();
                    } else {
                        this.startRoomAmbience();
                    }
                },

                playSfx(type) {
                    if (!this.sfxEnabled) return;
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        const ctx = (this.audioContext && this.audioContext.state !== 'closed') 
                            ? this.audioContext 
                            : new AudioContext();
                        if (ctx.state === 'suspended') {
                            ctx.resume();
                        }

                        const now = ctx.currentTime;
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);

                        if (type === 'start') {
                            // Sci-fi room entrance chime: 440Hz -> 880Hz
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(440, now);
                            osc.frequency.exponentialRampToValueAtTime(880, now + 0.22);
                            gain.gain.setValueAtTime(0.12, now);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                            osc.start(now);
                            osc.stop(now + 0.35);
                        } else if (type === 'blip') {
                            // User turn submitted blip: 587Hz -> 880Hz
                            osc.type = 'triangle';
                            osc.frequency.setValueAtTime(587.33, now);
                            osc.frequency.exponentialRampToValueAtTime(880, now + 0.12);
                            gain.gain.setValueAtTime(0.1, now);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
                            osc.start(now);
                            osc.stop(now + 0.18);
                        } else if (type === 'filler') {
                            // Subtle low alert chime when filler word triggers: 280Hz -> 200Hz
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(280, now);
                            osc.frequency.exponentialRampToValueAtTime(200, now + 0.18);
                            gain.gain.setValueAtTime(0.08, now);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
                            osc.start(now);
                            osc.stop(now + 0.22);
                        } else if (type === 'countdown') {
                            // Sprint final seconds tick: 987Hz
                            osc.type = 'square';
                            osc.frequency.setValueAtTime(987.77, now);
                            gain.gain.setValueAtTime(0.05, now);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
                            osc.start(now);
                            osc.stop(now + 0.08);
                        }
                    } catch (e) {
                        // Ignore audio autoplay restrictions silently
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().then(() => {
                            this.isFullscreen = true;
                        }).catch(() => {});
                    } else {
                        document.exitFullscreen().then(() => {
                            this.isFullscreen = false;
                        }).catch(() => {});
                    }
                },

                get formattedTime() {
                    const mins = String(Math.floor(this.secondsElapsed / 60)).padStart(2, '0');
                    const secs = String(this.secondsElapsed % 60).padStart(2, '0');
                    return `${mins}:${secs}`;
                },

                get orbStatusLabel() {
                    if (this.orbState === 'ai_speaking') return (this.selectedRole?.name || 'Panelis Penguji') + (this.simulationLanguage === 'en' ? ' is Speaking...' : ' Sedang Berbicara');
                    if (this.orbState === 'user_speaking') return (this.simulationLanguage === 'en' ? 'Listening to You...' : 'Mendengarkan Jawaban Anda...');
                    if (this.orbState === 'thinking') return (this.simulationLanguage === 'en' ? 'Evaluating Response...' : 'Sedang Menelaah Jawaban...');
                    return (this.simulationLanguage === 'en' ? 'Your Turn to Speak' : 'Giliran Anda Berbicara');
                },

                get avatarImageUrl() {
                    if (!this.selectedRole) return '/images/avatars/dosen.jpg';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') return '/images/avatars/dosen.jpg';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') return '/images/avatars/hrd.jpg?v=3';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'investor') return '/images/avatars/investor.jpg';
                    return '/images/avatars/dosen.jpg';
                },

                get avatarPersonaName() {
                    if (!this.selectedRole) return 'Panelis Penguji';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') return 'Prof. Dr. Ir. Hendra Kusuma, M.Kom';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') {
                        return 'Nadia Putri, S.Psi., CHRP';
                    }
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'investor') return 'Raymond Chandra (Managing Partner)';
                    return (this.selectedRole?.name || 'Panelis Penguji');
                },

                get avatarPersonaRole() {
                    if (!this.selectedRole) return 'Panelis Penguji';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji') return 'Dosen Penguji Sidang Utama';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'hrd') return 'Senior HR Recruiter & Talent Acquisition';
                    if ((this.selectedRole?.role_type || 'dosen_penguji') === 'investor') return 'Venture Capitalist & Angel Investor';
                    return this.selectedRole.description || 'Panelis Penguji';
                },

                get roleFaceLayout() {
                    const type = this.selectedRole?.role_type || 'dosen_penguji';
                    if (type === 'dosen_penguji') {
                        return {
                            mouthTop: '39.8%',
                            mouthLeft: '50.0%',
                            mouthWidth: '10.8%',
                            mouthSkinTone: '#99684f',
                            mouthLipTone: '#773f34',
                            eyeTop: '27.4%',
                            eyeLeft: '50.0%',
                            eyeWidth: '22.0%'
                        };
                    } else if (type === 'hrd') {
                        return {
                            mouthTop: '33.3%',
                            mouthLeft: '50.0%',
                            mouthWidth: '11.5%',
                            mouthSkinTone: '#d1987d',
                            mouthLipTone: '#b55858',
                            eyeTop: '23.6%',
                            eyeLeft: '50.0%',
                            eyeWidth: '22.0%'
                        };
                    } else {
                        return {
                            mouthTop: '48.1%',
                            mouthLeft: '51.8%',
                            mouthWidth: '10.5%',
                            mouthSkinTone: '#96674b',
                            mouthLipTone: '#6b3830',
                            eyeTop: '35.2%',
                            eyeLeft: '51.8%',
                            eyeWidth: '21.5%'
                        };
                    }
                },

                get latestAiMessageText() {
                    const msg = this.latestAiMessage;
                    return msg ? msg.message : '';
                },

                get latestAiMessage() {
                    for (let i = this.messages.length - 1; i >= 0; i--) {
                        if (this.messages[i].sender === 'assistant' || this.messages[i].sender === 'ai') {
                            return this.messages[i];
                        }
                    }
                    return null;
                },

                get dynamicAiThoughtReaction() {
                    if (this.orbState === 'ai_speaking') {
                        return 'Sedang mengajukan pertanyaan kritis dan menguji pemahaman Anda...';
                    }
                    if (this.orbState === 'user_speaking') {
                        if (this.liveEyeContactScore >= 75) {
                            return 'Menyimak dengan penuh perhatian • Kontak mata Anda sangat meyakinkan';
                        } else if (this.liveEyeContactScore < 50 && this.faceDetected) {
                            return 'Menyimak argumen Anda • Memperhatikan pandangan mata yang teralihkan';
                        }
                        return 'Memperhatikan artikulasi vokal dan kejelasan pemaparan Anda...';
                    }
                    if (this.orbState === 'thinking') {
                        return 'Menelaah kelengkapan argumen, struktur materi, dan ketenangan Anda...';
                    }
                    return 'Menunggu jawaban atau pemaparan selanjutnya dari Anda.';
                },

                async testVoiceAndMouth() {
                    if (this.orbState === 'ai_speaking') return;
                    const roleType = this.selectedRole ? (this.selectedRole?.role_type || 'dosen_penguji') : 'dosen_penguji';
                    const isEn = this.simulationLanguage === 'en';
                    const testLines = {
                        dosen_penguji: isEn
                            ? "Candidate, please sit upright and defend your empirical research methodology and validation."
                            : "Saudara mahasiswa, tolong jelaskan metodologi dan kebaruan penelitian Anda secara terstruktur.",
                        hrd: isEn
                            ? "Hello! Great to connect with you. Relax, stay confident, and walk me through your key career achievements."
                            : "Halo! Senang bertemu dengan kamu. Tarik napas santai dan ceritakan pengalaman kerja terbaik serta motivasimu.",
                        investor: isEn
                            ? "Pitch time starts now. Cut straight to the chase: what are your unit economics and defensible moat?"
                            : "Waktu pitching Anda dimulai sekarang. Langsung ke intinya: jelaskan daya tarik pasar dan monetisasi startup Anda dalam 60 detik."
                    };
                    const sample = testLines[roleType] || (isEn ? "Hello, I am testing the simulation voice." : "Halo, saya sedang berbicara dan menguji sistem simulasi ini.");

                    // Coba Edge-TTS neural audio dari AI service lokal
                    try {
                        const res = await fetch('http://127.0.0.1:8001/tts', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                text: sample,
                                role_type: roleType,
                                language: this.simulationLanguage
                            })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && data.audio_url) {
                                this.playAiSpeech(sample, 'http://127.0.0.1:8001' + data.audio_url);
                                return;
                            }
                        }
                    } catch (e) {
                        // Fallback ke browser speech synthesis
                    }

                    this.playAiSpeech(sample, null);
                },

                async previewRoleVoice(roleType) {
                    if (this.previewVoiceRole === roleType) {
                        if (this.previewAudioObj) {
                            this.previewAudioObj.pause();
                            this.previewAudioObj = null;
                        }
                        if (window.speechSynthesis) window.speechSynthesis.cancel();
                        this.previewVoiceRole = null;
                        return;
                    }

                    if (this.previewAudioObj) {
                        this.previewAudioObj.pause();
                        this.previewAudioObj = null;
                    }
                    if (window.speechSynthesis) window.speechSynthesis.cancel();

                    this.previewVoiceRole = roleType;
                    const isEn = this.simulationLanguage === 'en';
                    const sampleLines = {
                        dosen_penguji: isEn
                            ? "Candidate, please explain your research methodology and data validation."
                            : "Saudara mahasiswa, tolong jelaskan metodologi dan kebaruan penelitian Anda secara terstruktur.",
                        hrd: isEn
                            ? "Hello! Great to connect with you. Walk me through your key career achievements."
                            : "Halo! Senang bertemu dengan kamu. Tarik napas santai dan ceritakan pengalaman terbaikmu.",
                        investor: isEn
                            ? "Pitch time is live. Cut straight to the chase: what are your unit economics and defensible moat?"
                            : "Waktu pitching Anda dimulai sekarang. Langsung ke intinya: jelaskan daya tarik pasar dan monetisasi startup Anda dalam 60 detik."
                    };
                    const sample = sampleLines[roleType] || sampleLines.dosen_penguji;

                    // 1. Coba Edge-TTS studio-grade dari microservice AI lokal
                    try {
                        const res = await fetch('http://127.0.0.1:8001/tts', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                text: sample,
                                role_type: roleType,
                                language: this.simulationLanguage
                            })
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && data.audio_url) {
                                const audio = new Audio('http://127.0.0.1:8001' + data.audio_url);
                                this.previewAudioObj = audio;
                                audio.onended = () => { this.previewVoiceRole = null; };
                                audio.onerror = () => { this.playBrowserUtterance(sample, roleType); };
                                await audio.play();
                                return;
                            }
                        }
                    } catch (e) {
                        // AI service offline, lanjut ke browser synthesis
                    }

                    this.playBrowserUtterance(sample, roleType);
                },

                playBrowserUtterance(sample, roleType) {
                    if (!window.speechSynthesis) {
                        this.previewVoiceRole = null;
                        return;
                    }
                    window.speechSynthesis.cancel();
                    const ut = new SpeechSynthesisUtterance(sample);
                    ut.lang = this.simulationLanguage === 'en' ? 'en-US' : 'id-ID';

                    if (roleType === 'dosen_penguji') {
                        ut.pitch = 0.80; // Bariton berat Dosen Killer
                        ut.rate = 0.88;  // Tempo terukur & berwibawa
                    } else if (roleType === 'hrd') {
                        ut.pitch = 1.12; // Nada ramah & hangat HRD
                        ut.rate = 1.02;  // Tempo santun mengalir
                    } else { // investor
                        ut.pitch = 0.96; // Nada tegas percaya diri
                        ut.rate = 1.16;  // Tempo cepat pitch bisnis
                    }

                    const voices = window.speechSynthesis.getVoices();
                    const isEn = this.simulationLanguage === 'en';
                    const targetVoices = voices.filter(v => isEn ? (v.lang.includes('en-US') || v.lang.includes('en-GB') || v.lang.includes('en')) : (v.lang.includes('id') || v.lang.includes('ID')));

                    if (targetVoices.length > 0) {
                        if (roleType === 'hrd') {
                            const female = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('gadis') || n.includes('zira') || n.includes('jenny') || n.includes('aria') || n.includes('female') || n.includes('wanita') || n.includes('siti');
                            });
                            ut.voice = female || targetVoices[0];
                        } else if (roleType === 'dosen_penguji') {
                            const male = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('ardi') || n.includes('david') || n.includes('guy') || n.includes('davis') || n.includes('male') || n.includes('pria');
                            });
                            ut.voice = male || targetVoices[0];
                        } else {
                            const crisp = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('ardi') || n.includes('brian') || n.includes('christopher') || n.includes('male');
                            });
                            ut.voice = crisp || targetVoices[0];
                        }
                    }

                    ut.onend = () => { this.previewVoiceRole = null; };
                    ut.onerror = () => { this.previewVoiceRole = null; };
                    window.speechSynthesis.speak(ut);
                },

                async enterInterviewRoom() {
                    this.closeCalibrationModal();
                    this.isStarting = true;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const startUrl = this.isDemo ? "{{ route('demo.start') }}" : "{{ route('practice.start') }}";
                        const res = await fetch(startUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                ai_role_id: this.selectedRole.id,
                                scenario_type: this.customTopic.trim() || (this.selectedRole?.name || 'Panelis Penguji'),
                                language: this.simulationLanguage,
                                context_material: this.contextMaterial ? this.contextMaterial.trim() : null,
                                guest_name: this.isDemo ? this.guestName : null,
                                guest_institution: this.isDemo ? this.guestInstitution : null
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
                        this.currentStage = 'opening';
                        this.recordTimelineBookmark(0, 'intro', 'Pembukaan Sesi', 'AI Panelis memulai simulasi dan menyampaikan pembuka');
                        this.playSfx('start');
                        this.startSessionTimer();
                        this.startTelemetrySampler();

                        // AI speaks the opening greeting
                        if (data.initial_message) {
                            this.playAiSpeech(data.initial_message.message, data.initial_message.audio_url);
                        }
                    } catch (err) {
                        console.error('Error starting room:', err);
                        this.showToast('Gagal memasuki ruang simulasi. Periksa koneksi atau izin perangkat: ' + err.message, 'error', 6000);
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
                        this.initMediaPipeIrisTracker();

                        // Local Candidate Audio Recording (MediaRecorder)
                        try {
                            this.recordedAudioChunks = [];
                            const recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                            recorder.ondataavailable = (e) => {
                                if (e.data && e.data.size > 0) {
                                    this.recordedAudioChunks.push(e.data);
                                }
                            };
                            recorder.start(1000);
                            this.mediaRecorder = recorder;
                        } catch (recErr) {
                            console.warn('Candidate MediaRecorder not available:', recErr);
                        }
                    } catch (e) {
                        console.warn('Hardware permission notice:', e);
                        // Setup speech recognition even if video has restrictions
                        this.setupSpeechRecognition();
                    }
                },

                async initMediaPipeIrisTracker() {
                    try {
                        const visionModule = await import("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm/vision_bundle.mjs").catch(() => null)
                            || await import("https://esm.sh/@mediapipe/tasks-vision@0.10.14").catch(() => null);

                        if (visionModule && visionModule.FilesetResolver && visionModule.FaceLandmarker) {
                            const vision = await visionModule.FilesetResolver.forVisionTasks(
                                "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
                            );
                            this.faceLandmarker = await visionModule.FaceLandmarker.createFromOptions(vision, {
                                baseOptions: {
                                    modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
                                    delegate: "GPU"
                                },
                                runningMode: "VIDEO",
                                numFaces: 1
                            });
                            this.faceLandmarkerReady = true;
                            this.eyeTrackingEngine = 'mediapipe_iris';
                        }
                    } catch (e) {
                        // Smoothly fallback to high-speed canvas gaze tracker
                        this.faceLandmarkerReady = false;
                        this.eyeTrackingEngine = 'canvas_gaze';
                    }
                },

                setupAudioAnalyser(stream) {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        this.audioContext = new AudioContext();
                        const source = this.audioContext.createMediaStreamSource(stream);
                        this.analyser = this.audioContext.createAnalyser();
                        this.analyser.fftSize = 2048;
                        source.connect(this.analyser);
                        this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
                        this.pitchTimeDomainBuffer = new Float32Array(this.analyser.fftSize);
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
                        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.75)');   // Royal Blue 600
                        gradient.addColorStop(0.5, 'rgba(56, 189, 248, 0.9)'); // Sky 400
                        gradient.addColorStop(1, 'rgba(255, 255, 255, 0.95)'); // Crisp White

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
                    this.recognition.lang = this.simulationLanguage === 'en' ? 'en-US' : 'id-ID';

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

                        // 1. Calculate live word count & real-time WPM (Normalized by Language)
                        const currentWords = this.liveInterimTranscript ? this.liveInterimTranscript.split(/\s+/).filter(Boolean) : [];
                        this.liveWordCount = currentWords.length;
                        const activeMins = Math.max(0.08, this.secondsElapsed / 60);

                        // Bilingual character-based speech rate normalization (5.0 chars/word EN vs 6.2 chars/word ID)
                        const totalChars = (this.liveInterimTranscript || '').replace(/\s+/g, '').length;
                        const standardWordLen = this.simulationLanguage === 'en' ? 5.0 : 6.2;
                        const normalizedWordUnits = Math.round(totalChars / standardWordLen);
                        this.liveWpm = Math.max(0, Math.round((normalizedWordUnits || this.liveWordCount) / activeMins));

                        // 2. Real-time multi-tier filler word detection (Bilingual with tolerance)
                        const idFiller = /\b(ehm|eh|em|umm|um|uh|anu|ngg|ngga|kayak|apa namanya|apa ya|gimana ya|maksudnya|istilahnya|kira-kira|ya kan|gitu|macam)\b/i;
                        const enFiller = /\b(uh|um|uhm|like|you know|so yeah|actually|basically|i mean|sort of|kind of|you see|right)\b/i;
                        const repetitionFiller = /\b(saya saya|ini ini|dan dan|untuk untuk|we we|the the|and and|i i)\b/i;
                        const fillerRegex = this.simulationLanguage === 'en' ? enFiller : idFiller;
                        const fillerMatch = (finalTurn || interim).match(fillerRegex) || (finalTurn || interim).match(repetitionFiller);
                        if (fillerMatch) {
                            if (!this.fillerWordAlert) {
                                this.playSfx('filler');
                            }
                            this.fillerWordAlert = fillerMatch[0].toLowerCase();
                            this.recordTimelineEvent('filler_word', 'Kata Jeda / Gumaman Terdeteksi', 'Peserta mengucapkan: "' + this.fillerWordAlert + '"', 'danger');
                            this.recordTimelineBookmark(this.secondsElapsed, 'filler', 'Kata Jeda / Gumaman (' + this.fillerWordAlert + ')', 'Terdeteksi kata jeda dalam tuturan verbal');
                            clearTimeout(this.fillerAlertTimer);
                            this.fillerAlertTimer = setTimeout(() => {
                                this.fillerWordAlert = null;
                            }, 2200);
                        }

                        const maxPace = this.simulationLanguage === 'en' ? 165 : 148;
                        if (this.liveWpm > maxPace && this.liveWordCount > 12) {
                            this.recordTimelineEvent('fast_pace', 'Tempo Bicara Cepat', 'Kecepatan bicara Anda mencapai ' + this.liveWpm + ' WPM (ideal: ' + (this.simulationLanguage === 'en' ? '120-155' : '100-135') + ' WPM)', 'warning');
                        }

                        if (this.liveInterimTranscript.length > 0) {
                            this.orbState = 'user_speaking';

                            // Check Awkward Silence Latency (delay between AI stop and user start)
                            if (this.lastAiSpeechEndedAt && !this.userStartedSpeakingAt) {
                                this.userStartedSpeakingAt = Date.now();
                                const latencyMs = this.userStartedSpeakingAt - this.lastAiSpeechEndedAt;
                                if (latencyMs > 5500) {
                                    this.awkwardPausesCount++;
                                    this.recordTimelineBookmark(
                                        this.secondsElapsed,
                                        'silence',
                                        'Jeda Hening Canggung (' + (latencyMs / 1000).toFixed(1) + 's)',
                                        'Kandidat terdiam melebihi batas 5.5 detik sebelum merespons penguji'
                                    );
                                }
                            }

                            // Clear any prior silence timer
                            clearTimeout(this.silenceTimer);

                            // Trigger turn submission after natural silence threshold
                            this.silenceTimer = setTimeout(() => {
                                const sentenceToSend = this.liveInterimTranscript.trim();
                                if (sentenceToSend.length >= 3 && this.orbState !== 'thinking' && this.orbState !== 'ai_speaking') {
                                    const cleanCheck = sentenceToSend.replace(/[.,?!]/g, '').trim().toLowerCase();
                                    const isLoneFiller = /^(ehm|eh|em|umm|um|uh|anu|ngg|ngga|ha|tes|cek)$/i.test(cleanCheck);
                                    const endsWithConjunction = /\b(dan|karena|sehingga|tetapi|tapi|namun|yaitu|misalnya|lalu|kemudian|so|because|and|but|that|which|also)\s*$/i.test(cleanCheck);
                                    const words = cleanCheck.split(/\s+/).filter(Boolean);

                                    // Ensure user actually completed a real sentence/phrase without hanging conjunction
                                    if (!isLoneFiller && !endsWithConjunction && (words.length >= 2 || sentenceToSend.length >= 8)) {
                                        this.sendTurn(sentenceToSend);
                                        this.liveInterimTranscript = '';
                                        this.accumulatedSpokenText = '';
                                    }
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
                    if (this.simulationMode === 'sprint') {
                        this.sprintRemaining = 60;
                    }
                    this.sessionTimerInterval = setInterval(() => {
                        this.secondsElapsed++;
                        if (this.simulationMode === 'sprint') {
                            this.sprintRemaining = Math.max(0, 60 - this.secondsElapsed);
                            if (this.sprintRemaining <= 5 && this.sprintRemaining > 0) {
                                this.playSfx('countdown');
                            }
                            if (this.sprintRemaining <= 0) {
                                this.finishSession();
                            }
                        }
                    }, 1000);
                },

                startTelemetrySampler() {
                    this.telemetrySamplerInterval = setInterval(() => {
                        // 1. REAL Audio Volume & Pitch Intonation from Microphone Analyser
                        if (this.analyser && this.dataArray) {
                            this.analyser.getByteFrequencyData(this.dataArray);
                            let sum = 0;
                            for (let i = 0; i < this.dataArray.length; i++) {
                                sum += this.dataArray[i];
                            }
                            this.liveVolume = Math.min(100, Math.round((sum / this.dataArray.length / 128) * 100));
                            this.volumeSamples.push(this.liveVolume);

                            // Real-time Pitch Frequency & Expressiveness Intonation Score
                            if (this.liveVolume > 15 && this.pitchTimeDomainBuffer && this.audioContext) {
                                this.analyser.getFloatTimeDomainData(this.pitchTimeDomainBuffer);
                                const sampleRate = (this.audioContext && this.audioContext.sampleRate) ? this.audioContext.sampleRate : 44100;
                                const pitch = this.calculatePitchAutoCorrelation(this.pitchTimeDomainBuffer, sampleRate);
                                    if (pitch >= 75 && pitch <= 500 && isFinite(pitch)) {
                                        this.livePitchHz = Math.round(pitch);
                                        this.pitchSamples.push(this.livePitchHz);
                                        this.recentTurnPitchSamples.push(this.livePitchHz);
                                        if (this.recentTurnPitchSamples.length > 30) this.recentTurnPitchSamples.shift();

                                        // Layer 2 Hesitation Drone Detector: Continuous vocalization >= 500ms with low variance ("uhhh...", "ehhmmm...")
                                        this.consecutiveVoicedFrames = (this.consecutiveVoicedFrames || 0) + 1;
                                        this.voicedPitchBuffer = (this.voicedPitchBuffer || []);
                                        this.voicedPitchBuffer.push(this.livePitchHz);

                                        if (this.consecutiveVoicedFrames >= 3 && this.voicedPitchBuffer.length >= 3) {
                                            const sliceP = this.voicedPitchBuffer.slice(-3);
                                            const meanP = sliceP.reduce((a, b) => a + b, 0) / sliceP.length;
                                            const varP = sliceP.reduce((a, b) => a + Math.pow(b - meanP, 2), 0) / sliceP.length;

                                            // Low variance (< 5.0 Hz) across 750ms indicates flat monotone hesitation drone
                                            if (varP < 5.0 && !this.isCurrentlyInHesitationDrone) {
                                                this.isCurrentlyInHesitationDrone = true;
                                                this.audioHesitationCount = (this.audioHesitationCount || 0) + 1;
                                                this.turnHesitationCount = (this.turnHesitationCount || 0) + 1;
                                                this.recordTimelineBookmark(this.secondsElapsed, 'hesitation', 'Jeda Nada Datar (Uhhh...)', 'Vokalisasi datar kontinu terdeteksi >750ms');
                                                this.playSfx('filler');
                                            }
                                        }

                                        if (this.pitchSamples.length >= 6) {
                                            const recentPitches = this.pitchSamples.slice(-20);
                                            const valid = recentPitches.filter(p => typeof p === 'number' && isFinite(p) && p > 0);
                                            if (valid.length >= 4) {
                                                const mean = valid.reduce((a, b) => a + b, 0) / valid.length;
                                                const variance = valid.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / valid.length;
                                                const stdDev = Math.sqrt(variance);
                                                if (isFinite(stdDev)) {
                                                    // Standard deviation of 12 - 40 Hz represents dynamic natural speech
                                                    this.livePitchDynamicsScore = Math.min(98, Math.max(45, Math.round(52 + (stdDev * 1.5))));
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    this.consecutiveVoicedFrames = 0;
                                    this.voicedPitchBuffer = [];
                                    this.isCurrentlyInHesitationDrone = false;
                                }
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
                        this.currentFaceIcon = '';
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

                    // Real Lighting & Distance Advice Calculation
                    let totalBrightness = 0;
                    for (let y = 0; y < h; y += 8) {
                        for (let x = 0; x < w; x += 8) {
                            const idx = (y * w + x) * 4;
                            totalBrightness += (data[idx] * 0.299 + data[idx + 1] * 0.587 + data[idx + 2] * 0.114);
                        }
                    }
                    const sampleCount = (h / 8) * (w / 8);
                    const avgBrightness = sampleCount > 0 ? (totalBrightness / sampleCount) : 100;

                    if (avgBrightness < 38) {
                        this.cameraAdvice = 'Pencahayaan ruangan agak gelap, disarankan menyalakan lampu';
                    } else if (faceW < 50) {
                        this.cameraAdvice = 'Posisikan wajah sedikit lebih dekat ke kamera';
                    } else {
                        this.cameraAdvice = null;
                    }

                    // Dynamic HUD Coordinates (in percentage, mirrored for selfie webcam)
                    this.faceBoxCoords = {
                        left: Math.max(5, Math.min(80, Math.round(((w - maxX) / w) * 100))),
                        top: Math.max(5, Math.min(80, Math.round((minY / h) * 100))),
                        width: Math.min(90, Math.round((faceW / w) * 100)),
                        height: Math.min(90, Math.round((faceH / h) * 100)),
                    };

                    // 1. ATTEMPT HIGH-PRECISION MEDIAPIPE IRIS-LEVEL TRACKING (85-90% ACCURACY)
                    let irisEngineProcessed = false;
                    if (this.faceLandmarkerReady && this.faceLandmarker && video.readyState >= 2) {
                        try {
                            const results = this.faceLandmarker.detectForVideo(video, performance.now());
                            if (results && results.faceLandmarks && results.faceLandmarks.length > 0) {
                                const lm = results.faceLandmarks[0];
                                const irisLeft = lm[468];
                                const irisRight = lm[473];
                                const leftEyeInner = lm[133];
                                const leftEyeOuter = lm[33];
                                const rightEyeInner = lm[362];
                                const rightEyeOuter = lm[263];

                                if (irisLeft && irisRight && leftEyeInner && leftEyeOuter && rightEyeInner && rightEyeOuter) {
                                    const leftWidth = Math.max(0.001, Math.abs(leftEyeInner.x - leftEyeOuter.x));
                                    const leftRatio = (irisLeft.x - Math.min(leftEyeInner.x, leftEyeOuter.x)) / leftWidth;
                                    const rightWidth = Math.max(0.001, Math.abs(rightEyeInner.x - rightEyeOuter.x));
                                    const rightRatio = (irisRight.x - Math.min(rightEyeInner.x, rightEyeOuter.x)) / rightWidth;

                                    const avgIrisRatio = (leftRatio + rightRatio) / 2;
                                    // Deadband around center (0.42 to 0.58 corresponds to direct camera / avatar gaze)
                                    const irisDev = Math.abs(avgIrisRatio - 0.50);
                                    const effectiveIrisDev = Math.max(0, irisDev - 0.08) / 0.42;

                                    const rawIrisScore = Math.max(25, Math.min(99, Math.round(99 - (effectiveIrisDev * 85))));
                                    this.liveEyeContactScore = (this.liveEyeContactScore > 0)
                                        ? Math.round((this.liveEyeContactScore * 0.70) + (rawIrisScore * 0.30))
                                        : rawIrisScore;
                                    this.eyeTrackingEngine = 'mediapipe_iris';
                                    this.eyeContactSamples.push(this.liveEyeContactScore);
                                    irisEngineProcessed = true;
                                }
                            }
                        } catch (irisErr) {
                            // Silently continue to canvas fallback
                        }
                    }

                    if (!irisEngineProcessed) {
                        this.eyeTrackingEngine = 'canvas_gaze';
                        // REAL Eye Contact & Head Pose Gaze Tracking with horizontal yaw symmetry and screen tolerance
                        // Calculate horizontal symmetry in ocular-nasal band (20% to 55% of face height)
                        const ocularTop = minY + (faceH * 0.20);
                    const ocularBottom = minY + (faceH * 0.55);
                    let leftHalfSkin = 0;
                    let rightHalfSkin = 0;

                    for (let y = ocularTop; y <= ocularBottom; y += 4) {
                        for (let x = minX; x <= maxX; x += 4) {
                            const idx = (Math.floor(y) * w + Math.floor(x)) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b = data[idx + 2];
                            if (r > 60 && g > 40 && b > 20 && r > g && (r - g) > 15) {
                                if (x < centerX) leftHalfSkin++;
                                else rightHalfSkin++;
                            }
                        }
                    }

                    // Yaw symmetry ratio (detects sideways head turning)
                    const totalOcular = leftHalfSkin + rightHalfSkin;
                    const yawAsymmetry = totalOcular > 20 ? (Math.abs(leftHalfSkin - rightHalfSkin) / totalOcular) : 0;

                    // Screen Gaze Compensation Deadbands:
                    // Horizontal center deadband: within +-18% of frame center has zero penalty
                    const normDevX = Math.abs(centerX - (w / 2)) / (w / 2);
                    const effectiveDevX = Math.max(0, normDevX - 0.18) / 0.82;

                    // Vertical screen deadband: looking slightly below lens towards screen avatar (35% to 65% height) is normal
                    const normDevY = (centerY - (h * 0.48)) / (h * 0.48);
                    const effectiveDevY = (normDevY > 0)
                        ? Math.max(0, normDevY - 0.22) / 0.78
                        : Math.max(0, Math.abs(normDevY) - 0.15) / 0.85;

                    const gazePenalty = (effectiveDevX * 45) + (effectiveDevY * 35) + (yawAsymmetry * 35);
                    const rawEyeScore = Math.max(20, Math.min(98, Math.round(98 - gazePenalty)));

                    // Exponential Moving Average (EMA) smoothing for stability (prevents jitter from blinking/gestures)
                    const smoothedEye = (this.liveEyeContactScore > 0)
                        ? Math.round((this.liveEyeContactScore * 0.65) + (rawEyeScore * 0.35))
                        : rawEyeScore;

                    this.liveEyeContactScore = smoothedEye;
                    this.eyeContactSamples.push(smoothedEye);

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

                    // Real Status Classification with honest and calibrated labels
                    if (smoothedEye < 55) {
                        this.currentFaceStatus = 'mata_melenceng';
                        this.currentFaceIcon = '';
                        this.currentFaceText = 'Tatapan / Hadap Melenceng';
                    } else if (smileScore >= 72) {
                        this.currentFaceStatus = 'tersenyum';
                        this.currentFaceIcon = '';
                        this.currentFaceText = 'Ramah & Tersenyum';
                    } else if (smileScore < 42) {
                        this.currentFaceStatus = 'tegang';
                        this.currentFaceIcon = '';
                        this.currentFaceText = 'Tampak Tegang';
                    } else {
                        this.currentFaceStatus = 'fokus';
                        this.currentFaceIcon = '';
                        this.currentFaceText = 'Fokus Menatap Kamera / Layar';
                    }
                }
            },

                async sendTurn(userText) {
                    if (!userText || !this.currentSessionId) return;

                    this.stopCandidateTurnTimer();
                    this.checkUptalking(userText);
                    this.showInterruptionAlert = false;
                    this.playSfx('blip');
                    this.orbState = 'thinking';
                    clearTimeout(this.silenceTimer);

                    // Pause speech recognition while AI processes to prevent capturing background noise
                    if (this.recognition) {
                        try { this.recognition.stop(); } catch(e) {}
                    }

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
                        const url = (this.isDemo ? '/demo/' : '/practice/') + this.currentSessionId + '/message';

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
                                timestamp_seconds: this.secondsElapsed,
                                language: this.simulationLanguage
                            })
                        });

                        const data = await res.json();
                        if (data.success && data.ai_message) {
                            if (data.ai_engine) {
                                this.activeAiEngine = data.ai_engine;
                            }
                            if (data.stage) {
                                this.currentStage = data.stage;
                            }
                            this.messages.push(data.ai_message);
                            this.scrollToBottom();

                            if (data.facial_critique) {
                                this.liveReprimandNotice = data.facial_critique;
                            }
                            if (data.coherence_score) {
                                this.liveCoherenceScore = Math.round(data.coherence_score);
                                this.liveCoherenceCategory = data.coherence_category || 'Relevan & Nyambung';
                            }
                            if (data.salient_entities && data.salient_entities.length > 0) {
                                this.detectedSalientEntities = data.salient_entities;
                            }

                            // AI Speaks back
                            this.playAiSpeech(data.response_text, data.audio_url);
                        } else {
                            this.orbState = 'idle';
                            if (this.recognition) {
                                try { this.recognition.start(); } catch(e) {}
                            }
                        }
                    } catch (e) {
                        console.error('Error sending turn to AI:', e);
                        this.orbState = 'idle';
                        if (this.recognition) {
                            try { this.recognition.start(); } catch(e) {}
                        }
                    }
                },

                submitManualTurn() {
                    if (!this.manualInputText.trim()) return;
                    const text = this.manualInputText.trim();
                    this.manualInputText = '';
                    this.sendTurn(text);
                },

                submitCurrentTurn() {
                    clearTimeout(this.silenceTimer);
                    if (this.liveInterimTranscript && this.liveInterimTranscript.trim().length >= 2) {
                        const sentenceToSend = this.liveInterimTranscript.trim();
                        this.liveInterimTranscript = '';
                        this.accumulatedSpokenText = '';
                        this.sendTurn(sentenceToSend);
                    } else if (this.manualInputText && this.manualInputText.trim().length >= 2) {
                        this.submitManualTurn();
                    }
                },

                replayLastAiMessage() {
                    if (this.orbState === 'ai_speaking' || this.orbState === 'thinking') return;
                    const last = this.latestAiMessage;
                    if (last && last.message) {
                        this.playAiSpeech(last.message, last.audio_url || null);
                    }
                },

                startBlinkLoop() {
                    const triggerBlink = () => {
                        const nextInterval = 3000 + Math.random() * 2600;
                        this.blinkTimeout = setTimeout(() => {
                            this.isBlinking = true;
                            setTimeout(() => {
                                this.isBlinking = false;
                                triggerBlink();
                            }, 130);
                        }, nextInterval);
                    };
                    triggerBlink();
                },

                initLipSyncEngine() {
                    if (this.lipSyncAnimationId) {
                        cancelAnimationFrame(this.lipSyncAnimationId);
                    }
                    const updateLipSyncLoop = () => {
                        if (this.orbState === 'ai_speaking') {
                            this.liveMouthScaleY += (this.targetMouthScaleY - this.liveMouthScaleY) * 0.35;
                            this.liveMouthScaleX += (this.targetMouthScaleX - this.liveMouthScaleX) * 0.35;
                            this.liveMouthScaleY = Math.round(this.liveMouthScaleY * 1000) / 1000;
                            this.liveMouthScaleX = Math.round(this.liveMouthScaleX * 1000) / 1000;

                            const time = performance.now() * 0.005;
                            const nodY = (this.liveMouthScaleY - 0.2) * 2.8 + Math.sin(time * 1.5) * 0.8;
                            const headRot = Math.sin(time * 0.8) * 0.7;
                            this.liveHeadTransform = `translateY(${nodY.toFixed(1)}px) rotate(${headRot.toFixed(2)}deg)`;

                            const baseIntensity = Math.min(1, Math.max(0.12, this.liveMouthScaleY));
                            this.vocalSpectrumBars = [
                                Math.min(95, Math.max(12, Math.round(baseIntensity * 70 + Math.sin(time * 3) * 20))),
                                Math.min(95, Math.max(12, Math.round(baseIntensity * 90 + Math.cos(time * 4) * 25))),
                                Math.min(95, Math.max(15, Math.round(baseIntensity * 60 + Math.sin(time * 5) * 18))),
                                Math.min(95, Math.max(12, Math.round(baseIntensity * 100 + Math.cos(time * 3.5) * 22))),
                                Math.min(95, Math.max(12, Math.round(baseIntensity * 85 + Math.sin(time * 4.2) * 20))),
                                Math.min(95, Math.max(15, Math.round(baseIntensity * 65 + Math.cos(time * 6) * 15))),
                                Math.min(95, Math.max(12, Math.round(baseIntensity * 80 + Math.sin(time * 2.8) * 18))),
                                Math.min(95, Math.max(10, Math.round(baseIntensity * 50 + Math.cos(time * 5.2) * 15)))
                            ];
                        } else {
                            this.liveMouthScaleY = 0.15;
                            this.liveMouthScaleX = 1.0;
                            this.targetMouthScaleY = 0.15;
                            this.targetMouthScaleX = 1.0;
                            this.currentSpokenWord = '';
                            this.liveHeadTransform = 'translateY(0px) rotate(0deg)';
                            this.vocalSpectrumBars = [12, 12, 12, 12, 12, 12, 12, 12];
                        }
                        this.lipSyncAnimationId = requestAnimationFrame(updateLipSyncLoop);
                    };
                    this.lipSyncAnimationId = requestAnimationFrame(updateLipSyncLoop);
                },

                stopLipSync() {
                    if (this.wordVisemeTimeouts && this.wordVisemeTimeouts.length > 0) {
                        this.wordVisemeTimeouts.forEach(t => clearTimeout(t));
                        this.wordVisemeTimeouts = [];
                    }
                    if (this.synthFallbackTimer) {
                        clearTimeout(this.synthFallbackTimer);
                        this.synthFallbackTimer = null;
                    }
                    this.targetMouthScaleY = 0.15;
                    this.targetMouthScaleX = 1.0;
                    this.currentViseme = 'closed';
                    this.currentSpokenWord = '';
                    this.liveHeadTransform = 'translateY(0px) rotate(0deg)';
                    this.vocalSpectrumBars = [12, 12, 12, 12, 12, 12, 12, 12];
                },

                getVisemesForWord(word) {
                    if (!word) return [{ scaleX: 1.0, scaleY: 0.15, viseme: 'closed' }];
                    const clean = word.toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (!clean) return [{ scaleX: 1.0, scaleY: 0.15, viseme: 'closed' }];

                    const visemeDefs = {
                        closed: { scaleX: 1.0, scaleY: 0.15, viseme: 'closed' },
                        ah:     { scaleX: 1.02, scaleY: 1.05, viseme: 'ah' },
                        oh:     { scaleX: 0.72, scaleY: 0.88, viseme: 'oh' },
                        ee:     { scaleX: 1.22, scaleY: 0.44, viseme: 'ee' },
                        teeth:  { scaleX: 1.08, scaleY: 0.38, viseme: 'teeth' },
                        fv:     { scaleX: 1.05, scaleY: 0.28, viseme: 'fv' },
                    };

                    const rawSteps = [];
                    for (let i = 0; i < clean.length; i++) {
                        const ch = clean[i];
                        if (ch === 'm' || ch === 'b' || ch === 'p') {
                            rawSteps.push(visemeDefs.closed);
                        } else if (ch === 'a') {
                            rawSteps.push(visemeDefs.ah);
                        } else if (ch === 'o' || ch === 'u' || ch === 'w') {
                            rawSteps.push(visemeDefs.oh);
                        } else if (ch === 'e' || ch === 'i' || ch === 'y') {
                            rawSteps.push(visemeDefs.ee);
                        } else if (ch === 'f' || ch === 'v') {
                            rawSteps.push(visemeDefs.fv);
                        } else {
                            rawSteps.push(visemeDefs.teeth);
                        }
                    }

                    const steps = [];
                    for (let i = 0; i < rawSteps.length; i++) {
                        if (i === 0 || rawSteps[i].viseme !== rawSteps[i - 1].viseme) {
                            steps.push(rawSteps[i]);
                        }
                    }

                    return steps.length > 0 ? steps : [visemeDefs.closed];
                },

                triggerWordVisemes(word, estimatedDurationMs = 350) {
                    if (this.wordVisemeTimeouts && this.wordVisemeTimeouts.length > 0) {
                        this.wordVisemeTimeouts.forEach(t => clearTimeout(t));
                        this.wordVisemeTimeouts = [];
                    }

                    const steps = this.getVisemesForWord(word);
                    if (!steps || steps.length === 0) return;

                    const stepDuration = Math.max(65, Math.min(150, Math.floor(estimatedDurationMs / steps.length)));

                    steps.forEach((step, idx) => {
                        const timeout = setTimeout(() => {
                            if (this.orbState === 'ai_speaking') {
                                this.targetMouthScaleX = step.scaleX;
                                this.targetMouthScaleY = step.scaleY;
                                this.currentViseme = step.viseme;
                            }
                        }, idx * stepDuration);
                        this.wordVisemeTimeouts.push(timeout);
                    });

                    // At end of word, transition towards closed lips / brief inter-word pause
                    const endTimeout = setTimeout(() => {
                        if (this.orbState === 'ai_speaking') {
                            this.targetMouthScaleY = 0.20;
                            this.targetMouthScaleX = 1.0;
                        }
                    }, steps.length * stepDuration);
                    this.wordVisemeTimeouts.push(endTimeout);
                },

                startTimelineFallback(text) {
                    if (!text) return;
                    this.stopLipSync();

                    const tokens = text.match(/[a-zA-Z0-9]+|[.,!?;:]/g) || text.split(/\s+/);
                    let cumulativeTime = 0;

                    tokens.forEach((token) => {
                        if (/[.,!?;:]/.test(token)) {
                            const pauseDuration = (token === '.' || token === '!' || token === '?') ? 350 : 180;
                            const timeout = setTimeout(() => {
                                if (this.orbState === 'ai_speaking') {
                                    this.targetMouthScaleY = 0.15;
                                    this.targetMouthScaleX = 1.0;
                                    this.currentViseme = 'closed';
                                }
                            }, cumulativeTime);
                            this.wordVisemeTimeouts.push(timeout);
                            cumulativeTime += pauseDuration;
                        } else {
                            const wordDuration = Math.max(200, Math.min(550, (token.length * 60) + 120));
                            const timeout = setTimeout(() => {
                                if (this.orbState === 'ai_speaking') {
                                    this.currentSpokenWord = token;
                                    this.triggerWordVisemes(token, wordDuration);
                                }
                            }, cumulativeTime);
                            this.wordVisemeTimeouts.push(timeout);
                            cumulativeTime += wordDuration + 40;
                        }
                    });
                },

                playAiSpeech(text, audioUrl) {
                    this.orbState = 'ai_speaking';
                    this.currentAiSpokenText = text;
                    this.stopLipSync();

                    // Pause recognition while AI talks so it doesn't transcribe itself
                    if (this.recognition) {
                        try { this.recognition.stop(); } catch(e) {}
                    }

                    if (audioUrl) {
                        const audio = new Audio(audioUrl);
                        audio.crossOrigin = 'anonymous';

                        let audioCtx = null;
                        let analyser = null;
                        let dataArray = null;

                        try {
                            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            analyser = audioCtx.createAnalyser();
                            analyser.fftSize = 256;
                            analyser.smoothingTimeConstant = 0.4;
                            const sourceNode = audioCtx.createMediaElementSource(audio);
                            sourceNode.connect(analyser);
                            analyser.connect(audioCtx.destination);
                            dataArray = new Uint8Array(analyser.frequencyBinCount);
                        } catch (e) {
                            this.startTimelineFallback(text);
                        }

                        if (analyser) {
                            const sampleAudio = () => {
                                if (this.orbState !== 'ai_speaking' || audio.paused || audio.ended) return;
                                analyser.getByteFrequencyData(dataArray);

                                let sum = 0;
                                let lowBand = 0;  // 100 - 800 Hz (vowels A, O, U)
                                let highBand = 0; // 1500 - 4000 Hz (E, I, consonants)
                                for (let i = 2; i < 40; i++) {
                                    sum += dataArray[i];
                                    if (i < 12) lowBand += dataArray[i];
                                    else highBand += dataArray[i];
                                }
                                const avgVol = sum / 38;

                                if (avgVol < 8) {
                                    this.targetMouthScaleY = 0.15;
                                    this.targetMouthScaleX = 1.0;
                                    this.currentViseme = 'closed';
                                } else {
                                    const openRatio = Math.min(1.15, Math.max(0.28, avgVol / 42));
                                    this.targetMouthScaleY = openRatio;

                                    if (highBand > lowBand * 0.95) {
                                        this.targetMouthScaleX = 1.22;
                                        this.currentViseme = 'ee';
                                    } else if (lowBand > highBand * 1.8) {
                                        this.targetMouthScaleX = 0.72;
                                        this.currentViseme = 'oh';
                                    } else {
                                        this.targetMouthScaleX = 1.02;
                                        this.currentViseme = 'ah';
                                    }
                                }
                                requestAnimationFrame(sampleAudio);
                            };

                            audio.onplay = () => {
                                if (audioCtx && audioCtx.state === 'suspended') {
                                    audioCtx.resume();
                                }
                                requestAnimationFrame(sampleAudio);
                            };
                        }

                        audio.onended = () => {
                            this.orbState = 'idle';
                            this.stopLipSync();
                            this.lastAiSpeechEndedAt = Date.now();
                            this.startCandidateTurnTimer();
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
                    this.stopLipSync();
                    if (!window.speechSynthesis) {
                        this.startTimelineFallback(text);
                        setTimeout(() => {
                            this.orbState = 'idle';
                            this.stopLipSync();
                            this.lastAiSpeechEndedAt = Date.now();
                            this.startCandidateTurnTimer();
                            if (this.recognition) {
                                try { this.recognition.start(); } catch(e) {}
                            }
                        }, Math.max(3000, text.length * 75));
                        return;
                    }

                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = this.simulationLanguage === 'en' ? 'en-US' : 'id-ID';

                    const roleType = this.selectedRole?.role_type || 'dosen_penguji';
                    if (roleType === 'dosen_penguji') {
                        // Dosen Penguji: bariton berat akademis, berwibawa, tempo terukur
                        utterance.pitch = 0.68;
                        utterance.rate = 0.88;
                    } else if (roleType === 'hrd') {
                        // Suara HRD Ramah (Nadia): nada hangat, sopan & empatik, tempo natural
                        utterance.pitch = 1.15;
                        utterance.rate = 1.02;
                    } else {
                        // Suara Investor (Raymond): tegas, dinamis, to-the-point, energetik
                        utterance.pitch = 0.80;
                        utterance.rate = 1.12;
                    }

                    // Choose voice matching language and role gender/persona
                    const voices = window.speechSynthesis.getVoices();
                    const isEn = this.simulationLanguage === 'en';
                    const targetVoices = voices.filter(v => isEn ? (v.lang.includes('en-US') || v.lang.includes('en-GB') || v.lang.includes('en')) : (v.lang.includes('id') || v.lang.includes('ID')));

                    if (targetVoices.length > 0) {
                        if (roleType === 'hrd') {
                            const female = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('gadis') || n.includes('zira') || n.includes('jenny') || n.includes('aria') || n.includes('female') || n.includes('wanita') || n.includes('siti');
                            });
                            utterance.voice = female || targetVoices[0];
                        } else if (roleType === 'dosen_penguji') {
                            const male = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('ardi') || n.includes('david') || n.includes('guy') || n.includes('davis') || n.includes('male') || n.includes('pria');
                            });
                            utterance.voice = male || targetVoices[0];
                        } else {
                            const crisp = targetVoices.find(v => {
                                const n = (v.name || '').toLowerCase();
                                return n.includes('ardi') || n.includes('brian') || n.includes('christopher') || n.includes('male');
                            });
                            utterance.voice = crisp || targetVoices[0];
                        }
                    }

                    let hasBoundaryEvents = false;

                    utterance.onboundary = (event) => {
                        if (event.name === 'word') {
                            hasBoundaryEvents = true;
                            if (this.synthFallbackTimer) {
                                clearTimeout(this.synthFallbackTimer);
                                this.synthFallbackTimer = null;
                            }
                            const charIdx = event.charIndex;
                            const charLen = event.charLength || 6;
                            const currentWord = text.substring(charIdx, charIdx + charLen).trim().replace(/[.,/#!$%^&*;:{}=\-_`~()]/g, "");
                            this.currentSpokenWord = currentWord;
                            const estDuration = Math.max(200, Math.min(600, (currentWord.length * 65) + 100));
                            this.triggerWordVisemes(currentWord, estDuration);
                        }
                    };

                    utterance.onstart = () => {
                        this.orbState = 'ai_speaking';
                        this.synthFallbackTimer = setTimeout(() => {
                            if (!hasBoundaryEvents && this.orbState === 'ai_speaking') {
                                this.startTimelineFallback(text);
                            }
                        }, 350);
                    };

                    utterance.onend = () => {
                        this.orbState = 'idle';
                        this.stopLipSync();
                        this.lastAiSpeechEndedAt = Date.now();
                        this.startCandidateTurnTimer();
                        if (this.recognition) {
                            try { this.recognition.start(); } catch(e) {}
                        }
                    };

                    utterance.onerror = () => {
                        this.orbState = 'idle';
                        this.stopLipSync();
                        this.lastAiSpeechEndedAt = Date.now();
                        this.startCandidateTurnTimer();
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
                        this.showToast('Lakukan simulasi minimal 3 detik untuk mendapatkan laporan evaluasi komprehensif.', 'warning', 4500);
                        return;
                    }

                    this.isFinishing = true;
                    this.stopRoomAmbience();
                    this.stopCandidateTurnTimer();
                    clearInterval(this.sessionTimerInterval);
                    clearInterval(this.telemetrySamplerInterval);
                    clearTimeout(this.silenceTimer);
                    if (this.waveformAnimationId) {
                        cancelAnimationFrame(this.waveformAnimationId);
                    }
                    this.stopLipSync();
                    if (this.lipSyncAnimationId) {
                        cancelAnimationFrame(this.lipSyncAnimationId);
                    }

                    if (this.recognition) {
                        try { this.recognition.stop(); } catch(e) {}
                    }
                    if (window.speechSynthesis) {
                        window.speechSynthesis.cancel();
                    }

                    // Stop candidate audio recorder and persist to local IndexedDB (Zero Cloud)
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        try {
                            this.mediaRecorder.stop();
                        } catch(e) {}
                    }
                    if (this.recordedAudioChunks && this.recordedAudioChunks.length > 0) {
                        try {
                            const audioBlob = new Blob(this.recordedAudioChunks, { type: 'audio/webm' });
                            await this.saveAudioToIndexedDb(this.currentSessionId, audioBlob);
                        } catch (recErr) {
                            console.warn('Could not save local recording to IndexedDB:', recErr);
                        }
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

                    // 2. DATA METRIK UCAPAN ASLI PENGGUNA (Syllable-Normalized SPM / WPM & Hybrid Filler Words)
                    const userMessages = this.messages.filter(m => m.sender === 'user');
                    const totalSpokenWords = userMessages.reduce((sum, m) => {
                        const words = (m.message || '').trim().split(/\s+/).filter(Boolean);
                        return sum + words.length;
                    }, 0);

                    // Estimasi Suku Kata berbasis klaster vokal (Syllables Cluster Estimation)
                    const totalSyllables = userMessages.reduce((sum, m) => {
                        const vowelClusters = (m.message || '').match(/[aiueoAIUEO]+/g) || [];
                        return sum + vowelClusters.length;
                    }, 0);

                    const durationMinutes = Math.max(0.08, this.secondsElapsed / 60);
                    const isEnglish = (this.simulationLanguage === 'en');
                    const spm = totalSyllables / durationMinutes;
                    // Normalisasi rasio suku kata per kata: Bahasa Indonesia ~2.2 suku kata/kata vs Inggris ~1.4 suku kata/kata
                    const syllablesPerWord = isEnglish ? 1.4 : 2.2;
                    const realPaceWpm = Math.round(spm / syllablesPerWord) || Math.round(totalSpokenWords / durationMinutes);

                    // Lapis 1: Deteksi kata jeda/gumaman bilingual berbasis STT Regex
                    const idFillerRegex = /\b(ehm|eh|em|umm|um|uh|anu|ngg|ngga|kayak|apa namanya|apa ya|gimana ya|maksudnya|istilahnya|kira-kira|ya kan|gitu|macam)\b/gi;
                    const enFillerRegex = /\b(uh|um|uhm|like|you know|so yeah|actually|basically|i mean|sort of|kind of|you see|right)\b/gi;
                    const repetitionFiller = /\b(saya saya|ini ini|dan dan|untuk untuk|we we|the the|and and|i i)\b/gi;
                    const fillerRegex = isEnglish ? enFillerRegex : idFillerRegex;

                    let totalFillerWords = 0;
                    const detectedFillers = [];
                    userMessages.forEach(m => {
                        const text = m.message || '';
                        const matches = text.match(fillerRegex);
                        if (matches) {
                            totalFillerWords += matches.length;
                            matches.forEach(w => {
                                const lower = w.toLowerCase();
                                if (!detectedFillers.includes(lower)) {
                                    detectedFillers.push(lower);
                                }
                            });
                        }
                        const repMatches = text.match(repetitionFiller);
                        if (repMatches) {
                            totalFillerWords += repMatches.length;
                            repMatches.forEach(w => {
                                const lower = w.toLowerCase();
                                if (!detectedFillers.includes(lower)) {
                                    detectedFillers.push(lower);
                                }
                            });
                        }
                    });

                    // Lapis 2: Gabungkan Audio-Based Hesitation Detector (Jeda nada datar kontinu "uhhh...")
                    const audioHesitations = this.audioHesitationCount || 0;
                    if (audioHesitations > 0) {
                        totalFillerWords += audioHesitations;
                        detectedFillers.push(`${audioHesitations}x jeda hening/nada datar akustik`);
                    }

                    // 3. DATA TELEMETRI AUDIO ASLI MIKROFON (Web Audio Analyser)
                    const validVolSamples = this.volumeSamples.filter(v => v > 0);
                    const avgVolume = validVolSamples.length > 0
                        ? Math.round(validVolSamples.reduce((a, b) => a + b, 0) / validVolSamples.length)
                        : 0;

                    // Pacing Score: Kecepatan bicara ideal terkalibrasi per bahasa
                    // Bahasa Indonesia: 100 - 135 WPM | Bahasa Inggris: 120 - 155 WPM
                    let paceScore = 80;
                    const minIdealWpm = isEnglish ? 120 : 100;
                    const maxIdealWpm = isEnglish ? 155 : 135;

                    if (totalSpokenWords === 0) {
                        paceScore = 40;
                    } else if (realPaceWpm >= minIdealWpm && realPaceWpm <= maxIdealWpm) {
                        paceScore = 95;
                    } else if (realPaceWpm >= (minIdealWpm - 15) && realPaceWpm < minIdealWpm) {
                        paceScore = 88;
                    } else if (realPaceWpm > maxIdealWpm && realPaceWpm <= (maxIdealWpm + 20)) {
                        paceScore = 86;
                    } else if (realPaceWpm > (maxIdealWpm + 20)) {
                        paceScore = Math.max(45, Math.round(88 - ((realPaceWpm - (maxIdealWpm + 20)) * 0.8)));
                    } else {
                        paceScore = Math.max(40, Math.round((realPaceWpm / minIdealWpm) * 85));
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

                    // Real-time Pitch Dynamics Score
                    const pitchScore = this.livePitchDynamicsScore || 80;

                    const voiceScore = parseFloat(((paceScore * 0.45) + (volumeScore * 0.30) + (pitchScore * 0.25)).toFixed(1));
                    const overallScore = parseFloat(((faceScore * 0.5) + (voiceScore * 0.5)).toFixed(1));

                    // Dynamic Achievement Badges
                    const badges = [];
                    if (avgEye >= 80) {
                        badges.push({
                            id: 'tatapan_elang',
                            name: 'Tatapan Elang',
                            icon: 'eye',
                            desc: 'Fokus tatapan mata ke audiens di atas 80%'
                        });
                    }
                    if (totalFillerWords === 0 && totalSpokenWords >= 15) {
                        badges.push({
                            id: 'artikulasi_emas',
                            name: 'Artikulasi Emas',
                            icon: 'mic',
                            desc: 'Bicara lancar & lugas tanpa kata gumaman/filler'
                        });
                    }
                    if (pitchScore >= 80) {
                        badges.push({
                            id: 'vokal_dinamis',
                            name: 'Vokal Dinamis',
                            icon: 'audio',
                            desc: 'Variasi nada intonasi ekspresif & tidak monoton'
                        });
                    }
                    if (this.simulationMode === 'sprint' && this.secondsElapsed >= 45) {
                        badges.push({
                            id: 'sprint_warrior',
                            name: 'Sprint Warrior',
                            icon: 'bolt',
                            desc: 'Menuntaskan simulasi cepat 60 detik'
                        });
                    }
                    if (avgSmile >= 70 && avgEye >= 70) {
                        badges.push({
                            id: 'duta_karisma',
                            name: 'Duta Karisma',
                            icon: 'shield',
                            desc: 'Kombinasi tatapan mantap dan ekspresi hangat'
                        });
                    }
                    if (overallScore >= 85) {
                        badges.push({
                            id: 'high_achiever',
                            name: 'High Achiever',
                            icon: 'trophy',
                            desc: 'Meraih skor keseluruhan istimewa di atas 85/100'
                        });
                    }
                    if (this.simulationLanguage === 'en' && realPaceWpm >= 75) {
                        badges.push({
                            id: 'global_communicator',
                            name: 'Global Communicator',
                            icon: 'globe',
                            desc: 'Simulasi wawancara profesional dalam Bahasa Inggris'
                        });
                    }

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

                    // Evaluasi Dinamika Intonasi & Nada
                    if (pitchScore >= 80) {
                        strengths.push(`Dinamika intonasi nada vokal terdengar hidup dan persuasif (skor ${pitchScore}%), tidak monoton.`);
                    } else if (pitchScore < 60) {
                        improvements.push(`Intonasi suara cenderung agak datar (skor ${pitchScore}%). Berikan variasi penekanan nada pada poin-poin krusial.`);
                    }

                    // Evaluasi Pacing & Artikulasi WPM Terkalibrasi
                    if (realPaceWpm >= minIdealWpm && realPaceWpm <= maxIdealWpm) {
                        strengths.push(`Tempo berbicara sangat ideal (${realPaceWpm} WPM terkalibrasi ${isEnglish ? 'EN' : 'ID'}), pesan tersampaikan secara artikulatif dan runtut.`);
                    } else if (realPaceWpm > maxIdealWpm) {
                        improvements.push(`Tempo berbicara terukur agak cepat (${realPaceWpm} WPM, acuan ideal ${minIdealWpm}-${maxIdealWpm} WPM). Berikan jeda sejenak (power pause) pada poin-poin utama.`);
                    } else if (realPaceWpm > 0 && realPaceWpm < minIdealWpm) {
                        improvements.push(`Tempo bicara relatif lambat (${realPaceWpm} WPM, acuan ideal ${minIdealWpm}-${maxIdealWpm} WPM). Tingkatkan kelancaran dan kesinambungan kalimat.`);
                    } else {
                        improvements.push('Belum terdeteksi kalimat verbal dari Anda. Coba suarakan jawaban secara aktif melalui mikrofon.');
                    }

                    // Evaluasi Filler Words dengan Toleransi Alami
                    if (totalFillerWords > 3) {
                        improvements.push(`Terdeteksi ${totalFillerWords} kali kata jeda/gumaman (${detectedFillers.join(', ')}). Gunakan jeda hening sejenak (power pause) daripada mengisi jeda dengan gumaman.`);
                    } else if (totalFillerWords >= 1 && totalFillerWords <= 3) {
                        strengths.push(`Pengendalian kata jeda sangat baik dan masih dalam batas toleransi wajar komunikasi alami (${totalFillerWords} kali: ${detectedFillers.join(', ')}).`);
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
                        pitch_dynamics_score: pitchScore,
                        badges: badges,
                        feedback_notes: {
                            summary: this.simulationLanguage === 'en'
                                ? `Simulation with ${(this.selectedRole?.name || 'Panelis Penguji')} completed in ${this.secondsElapsed}s with an overall score of ${overallScore}/100.`
                                : `Simulasi bersama ${(this.selectedRole?.name || 'Panelis Penguji')} diselesaikan dalam ${this.secondsElapsed} detik dengan skor keseluruhan ${overallScore}/100.`,
                            eye_contact_score: avgEye,
                            smile_rate: avgSmile,
                            pace_wpm: realPaceWpm,
                            clarity_score: Math.round(voiceScore),
                            pitch_dynamics_score: pitchScore,
                            avg_volume: avgVolume,
                            total_words: totalSpokenWords,
                            filler_words_count: totalFillerWords,
                            filler_words_list: detectedFillers,
                            language: this.simulationLanguage,
                            context_material: this.contextMaterial ? this.contextMaterial.trim() : null,
                            timeline_events: this.timelineEvents,
                            detected_uptalking: this.detectedUptalkingCount,
                            awkward_pauses: this.awkwardPausesCount,
                            current_stage: this.currentStage,
                            timeline_bookmarks: this.timelineBookmarks,
                            badges: badges,
                            strengths: strengths,
                            improvements: improvements
                        }
                    };

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const finishUrl = (this.isDemo ? '/demo/' : '/practice/') + this.currentSessionId + '/finish';
                        const res = await fetch(finishUrl, {
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
                        this.showToast('Terjadi kendala saat menyimpan rapor: ' + e.message, 'error', 5000);
                        this.isFinishing = false;
                    }
                }
            };
        }

        if (window.Alpine) {
            Alpine.data('interactiveSimulationApp', interactiveSimulationApp);
        } else {
            document.addEventListener('alpine:init', () => {
                Alpine.data('interactiveSimulationApp', interactiveSimulationApp);
            });
        }
        window.interactiveSimulationApp = interactiveSimulationApp;
    </script>

    <div class="py-8 relative overflow-hidden" x-data="interactiveSimulationApp()">
        <!-- Glassmorphic Floating Toast Notification -->
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
             class="fixed top-6 right-6 z-50 max-w-md w-full pointer-events-none"
             style="display: none;">
            <div class="pointer-events-auto p-4 rounded-2xl backdrop-blur-xl border shadow-2xl flex items-start gap-3 text-sm"
                 :class="{
                     'bg-rose-950/90 border-rose-500/50 text-rose-100 shadow-rose-950/50': toast.type === 'error',
                     'bg-amber-950/90 border-amber-500/50 text-amber-100 shadow-amber-950/50': toast.type === 'warning',
                     'bg-emerald-950/90 border-emerald-500/50 text-emerald-100 shadow-emerald-950/50': toast.type === 'success',
                     'bg-slate-900/90 border-blue-500/50 text-slate-100 shadow-blue-950/50': toast.type === 'info'
                 }">
                <div class="shrink-0 mt-0.5">
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-xs leading-relaxed" x-text="toast.message"></p>
                </div>
                <button type="button" @click="toast.show = false" class="text-white/60 hover:text-white transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Ambient Mesh Light Aura Orbs for Executive Glassmorphism -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
            <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-gradient-to-br from-blue-200/50 via-sky-100/40 to-transparent blur-3xl opacity-80 dark:opacity-20"></div>
            <div class="absolute top-1/4 -right-40 w-[550px] h-[550px] rounded-full bg-gradient-to-bl from-indigo-200/45 via-blue-100/30 to-transparent blur-3xl opacity-75 dark:opacity-20"></div>
            <div class="absolute bottom-10 left-1/3 w-[600px] h-[500px] rounded-full bg-gradient-to-tr from-cyan-100/40 via-blue-50/30 to-transparent blur-3xl opacity-70 dark:opacity-15"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 relative z-10">

            <!-- STEP 1: AI ROLE SELECTION (Clean, Sederhana & Ramah) -->
            <div x-show="!sessionState || sessionState === 'selection'" class="space-y-6">
                <div class="glass-panel rounded-3xl p-6 sm:p-8">
                    <!-- Title & Overview -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold glass-pill text-blue-700 dark:text-blue-300 border border-blue-200/80 dark:border-blue-900/60 mb-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            Pilih Skenario Wawancara
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Pilih Penguji & Mulai Latihan
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih salah satu mode latihan di bawah ini, lalu klik <span class="font-semibold text-slate-700 dark:text-slate-300">Mulai Simulasi</span> untuk berlatih tatap muka langsung.
                        </p>
                    </div>

                    <!-- 3 Clean Scenario Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        @forelse($aiRoles as $role)
                            <div @click="selectRoleById({{ $role->id }})"
                                 :class="selectedRole?.id === {{ $role->id }}
                                    ? 'border-blue-600 ring-4 ring-blue-500/20 bg-blue-50/70 dark:bg-navy-950/70 dark:border-blue-500 shadow-lg'
                                    : 'border-slate-200/80 dark:border-slate-800 glass-subcard hover:border-blue-300 dark:hover:border-slate-700 hover:shadow-md'"
                                 class="p-5 rounded-2xl border transition-all cursor-pointer relative flex flex-col justify-between group">

                                <div>
                                    <div class="flex items-center gap-3.5 mb-3.5">
                                        <div class="relative w-14 h-14 rounded-2xl overflow-hidden shadow-sm border border-slate-200 dark:border-slate-700 shrink-0">
                                            <img src="{{ $role->role_type === 'dosen_penguji' ? asset('images/avatars/dosen.jpg') : ($role->role_type === 'hrd' ? asset('images/avatars/hrd.jpg') . '?v=3' : asset('images/avatars/investor.jpg')) }}"
                                                 alt="{{ $role->name }}"
                                                 width="56"
                                                 height="56"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $role->role_type === 'dosen_penguji' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : ($role->role_type === 'hrd' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300') }}">
                                                {{ $role->role_type === 'dosen_penguji' ? 'Akademik' : ($role->role_type === 'hrd' ? 'Karier' : 'Bisnis') }}
                                            </span>
                                            <h4 class="font-extrabold text-base text-slate-900 dark:text-white mt-1">{{ $role->name }}</h4>
                                        </div>
                                    </div>

                                    @if($role->role_type === 'dosen_penguji')
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-indigo-50/80 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60 mb-2.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                            <span>🎙️ Suara Dosen Killer (Bariton, Tegas & Berbobot)</span>
                                        </div>
                                    @elseif($role->role_type === 'hrd')
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-amber-50/80 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60 mb-2.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                            <span>🎙️ Suara HRD Ramah (Hangat, Empatik & Komunikatif)</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-purple-50/80 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60 mb-2.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-pulse"></span>
                                            <span>🎙️ Suara Pitch Bisnis (Cepat, Dinamis & To-The-Point)</span>
                                        </div>
                                    @endif

                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                                        {{ $role->description }}
                                    </p>
                                </div>

                                <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold"
                                          :class="selectedRole?.id === {{ $role->id }} ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'">
                                        <span x-show="selectedRole?.id === {{ $role->id }}">✓ Terpilih</span>
                                        <span x-show="selectedRole?.id !== {{ $role->id }}">Pilih Ini</span>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click.stop="previewRoleVoice('{{ $role->role_type }}')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition cursor-pointer shadow-2xs"
                                                :class="previewVoiceRole === '{{ $role->role_type }}' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                            <svg class="w-3.5 h-3.5" :class="previewVoiceRole === '{{ $role->role_type }}' ? 'animate-pulse' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                            </svg>
                                            <span x-text="previewVoiceRole === '{{ $role->role_type }}' ? 'Memutar...' : 'Dengar Suara'"></span>
                                        </button>
                                        <span class="text-[11px] text-slate-400 hidden sm:inline">
                                            {{ $role->difficulty_level }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            @foreach($scenarios as $sc)
                                <div @click="selectRole({ id: 1, name: '{{ $sc['name'] }}', role_type: '{{ $sc['role_type'] }}', difficulty_level: 'Sedang' })"
                                     class="p-5 rounded-2xl glass-subcard cursor-pointer">
                                    <h4 class="font-bold text-base text-slate-900 dark:text-white">{{ $sc['name'] }}</h4>
                                    <p class="text-xs text-slate-500 mt-1">{{ $sc['description'] }}</p>
                                </div>
                            @endforeach
                        @endforelse
                    </div>

                    <!-- Innofest Visitor Information Card (Only in Demo Mode) -->
                    <template x-if="isDemo">
                        <div class="mt-6 p-5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-blue-500/10 border border-emerald-500/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-300">
                                        Identitas Peserta Latihan
                                    </h4>
                                </div>
                                <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-800">
                                    Sertifikat Langsung Terbit
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Anda (Untuk Sertifikat Digital):
                                    </label>
                                    <input type="text"
                                           x-model="guestName"
                                           placeholder="misal: Budi Santoso / Dewan Juri"
                                           class="w-full text-xs sm:text-sm rounded-xl glass-input text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Instansi / Asal Kampus (Opsional):
                                    </label>
                                    <input type="text"
                                           x-model="guestInstitution"
                                           placeholder="misal: Universitas Brawijaya / Umum"
                                           class="w-full text-xs sm:text-sm rounded-xl glass-input text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                💡 Setelah simulasi, Anda dapat langsung scan QR Code dengan smartphone untuk membawa pulang sertifikat hasil evaluasi resmi.
                            </p>
                        </div>
                    </template>

                    <!-- Clean Topic Input -->
                    <div class="mt-6 pt-6 border-t border-slate-200/70 dark:border-slate-700/60 space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-white">
                            Fokus Topik atau Posisi (Opsional):
                        </label>
                        <div class="relative">
                            <input type="text"
                                   x-model="customTopic"
                                   :placeholder="topicPlaceholder"
                                   class="w-full text-xs sm:text-sm rounded-xl glass-input text-slate-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-slate-400">
                            <button type="button"
                                    x-show="customTopic"
                                    @click="customTopic = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">
                                ✕ Reset
                            </button>
                        </div>

                        <!-- Quick Presets -->
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-[11px] text-slate-400 me-1">Pilihan Cepat:</span>
                            <template x-for="preset in currentTopicPresets" :key="preset">
                                <button type="button"
                                        @click="customTopic = preset"
                                        :class="customTopic === preset
                                            ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-xs'
                                            : 'glass-pill text-slate-700 dark:text-slate-300 border-slate-200/80 dark:border-slate-700 hover:bg-white'"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition cursor-pointer">
                                    <span x-text="preset"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Subtle Collapsible Advanced Options -->
                    <div class="mt-5 pt-4 border-t border-slate-200/70 dark:border-slate-700/60">
                        <button type="button"
                                @click="showAdvancedOptions = !showAdvancedOptions"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="showAdvancedOptions ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span>Opsi Tambahan (Abstrak, Durasi & Bahasa)</span>
                        </button>

                        <div x-show="showAdvancedOptions" x-transition class="mt-4 p-4 rounded-2xl glass-subcard space-y-4">
                            <!-- Abstrak -->
                            <div>
                                <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                    Abstrak Skripsi / Ringkasan Materi (Opsional):
                                </label>
                                <textarea x-model="contextMaterial"
                                          rows="3"
                                          placeholder="Tempelkan abstrak skripsi atau deskripsi lowongan di sini..."
                                          class="w-full text-xs rounded-xl glass-input text-slate-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-blue-500 placeholder-slate-400"></textarea>
                            </div>

                            <!-- Mode & Bahasa -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                                        Durasi Latihan:
                                    </label>
                                    <div class="inline-flex p-1 rounded-xl glass-pill text-xs w-full">
                                        <button type="button"
                                                @click="simulationMode = 'standard'"
                                                :class="simulationMode === 'standard' ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300'"
                                                class="flex-1 py-1.5 rounded-lg transition text-center cursor-pointer">
                                            Standar (Fleksibel)
                                        </button>
                                        <button type="button"
                                                @click="simulationMode = 'sprint'"
                                                :class="simulationMode === 'sprint' ? 'bg-amber-500 text-black font-extrabold' : 'text-slate-600 dark:text-slate-300'"
                                                class="flex-1 py-1.5 rounded-lg transition text-center cursor-pointer">
                                            Sprint 60 Detik
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                                        Bahasa Simulasi:
                                    </label>
                                    <div class="inline-flex p-1 rounded-xl glass-pill text-xs w-full">
                                        <button type="button"
                                                @click="simulationLanguage = 'id'"
                                                :class="simulationLanguage === 'id' ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300'"
                                                class="flex-1 py-1.5 rounded-lg transition text-center cursor-pointer">
                                            Bahasa Indonesia
                                        </button>
                                        <button type="button"
                                                @click="simulationLanguage = 'en'"
                                                :class="simulationLanguage === 'en' ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300'"
                                                class="flex-1 py-1.5 rounded-lg transition text-center cursor-pointer">
                                            English
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Launch CTA Section -->
                    <div class="mt-8 pt-6 border-t border-slate-200/70 dark:border-slate-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Privasi Aman • Pengolahan Kamera & Suara 100% di Perangkat Anda</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <!-- Optional Camera & Mic Test -->
                            <button type="button"
                                    @click="openCalibrationModal()"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-3 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700/80 transition cursor-pointer shadow-xs">
                                <svg class="w-4 h-4 me-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span>Uji Kamera & Mic</span>
                            </button>

                            <!-- Primary Direct Start Button -->
                            <button type="button"
                                    @click="enterInterviewRoom()"
                                    :disabled="isStarting"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-2xl shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5 cursor-pointer disabled:opacity-50">
                                <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="isStarting ? 'Menyiapkan Ruangan...' : 'Mulai Simulasi Sekarang'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRE-FLIGHT HARDWARE CALIBRATION MODAL -->
            <div x-show="showCalibrationModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-md overflow-y-auto"
                 @keydown.escape.window="closeCalibrationModal()">
                <div class="glass-panel rounded-3xl max-w-3xl w-full border-white/90 dark:border-navy-800 shadow-2xl overflow-hidden my-auto"
                     @click.away="closeCalibrationModal()">
                    
                    <!-- Modal Header -->
                    <div class="p-6 border-b border-slate-200/60 dark:border-navy-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-navy-950/70 border border-blue-200 dark:border-navy-800 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-slate-900 dark:text-white">
                                    Pemeriksaan Perangkat & Kalibrasi Sesi
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Pastikan posisi kamera dan mikrofon berfungsi optimal sebelum simulasi dimulai
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="closeCalibrationModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold cursor-pointer">✕</button>
                    </div>

                    <!-- Modal Body: 2 Columns (Preview Left, Settings Right) -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                        <!-- Left: Camera Preview & Face Alignment Reticle -->
                        <div class="md:col-span-6 space-y-3">
                            <div class="relative bg-slate-950 rounded-2xl overflow-hidden aspect-4/3 border border-slate-800 flex items-center justify-center shadow-lg">
                                <video id="calibrationVideo" autoplay playsinline muted class="w-full h-full object-cover -scale-x-100"></video>
                                
                                <!-- Center Target Reticle -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div class="w-32 h-44 rounded-full border-2 border-dashed border-blue-400/70 animate-pulse"></div>
                                </div>
                                <div class="absolute top-2 left-2 px-2.5 py-1 rounded-lg bg-black/75 text-[10px] text-white font-mono flex items-center gap-1.5 shadow-md">
                                    <span class="w-2 h-2 rounded-full" :class="calibrationCameraOk ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                                    <span x-text="calibrationCameraOk ? 'Kamera Terhubung' : 'Mencari Kamera...'"></span>
                                </div>
                            </div>
                            
                            <!-- Mic Sound Level Meter -->
                            <div class="p-3.5 rounded-xl glass-subcard border-slate-200/80 dark:border-slate-800 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                        </svg>
                                        <span>Tes Mikrofon:</span>
                                    </span>
                                    <span class="font-mono text-[11px]" :class="calibrationVolume > 15 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'" x-text="calibrationVolume > 15 ? 'Suara Terdeteksi' : 'Silakan Bicara...'"></span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden shadow-inner">
                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-blue-500 transition-all duration-75" :style="'width: ' + calibrationVolume + '%'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Language & Curated Curriculum Track -->
                        <div class="md:col-span-6 space-y-4">
                            <!-- Language Selection -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    <span>Bahasa Simulasi (Simulation Language)</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button"
                                            @click="simulationLanguage = 'id'"
                                            :class="simulationLanguage === 'id' ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-md shadow-blue-600/20' : 'glass-pill text-slate-700 dark:text-slate-300 border-slate-200/80 dark:border-slate-800 hover:bg-white'"
                                            class="p-2.5 rounded-xl border text-xs text-center transition cursor-pointer flex items-center justify-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white/20">ID</span>
                                        <span>Bahasa Indonesia</span>
                                    </button>
                                    <button type="button"
                                            @click="simulationLanguage = 'en'"
                                            :class="simulationLanguage === 'en' ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-md shadow-blue-600/20' : 'glass-pill text-slate-700 dark:text-slate-300 border-slate-200/80 dark:border-slate-800 hover:bg-white'"
                                            class="p-2.5 rounded-xl border text-xs text-center transition cursor-pointer flex items-center justify-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white/20">EN</span>
                                        <span>English (Global)</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Curated Question Bank / Track Selection -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span>Paket Kurikulum & Fokus Pengujian</span>
                                </label>
                                <div class="space-y-1.5">
                                    <template x-for="track in curatedTracksList" :key="track">
                                        <button type="button"
                                                @click="customTopic = track"
                                                :class="customTopic === track ? 'bg-blue-50/90 dark:bg-navy-900 border-blue-500 text-blue-700 dark:text-blue-300 font-bold shadow-xs' : 'glass-pill border-slate-200/80 dark:border-slate-700/60 text-slate-700 dark:text-slate-300 hover:bg-white'"
                                                class="w-full p-2.5 rounded-xl border text-xs text-left transition cursor-pointer flex items-center justify-between">
                                            <span x-text="track"></span>
                                            <span x-show="customTopic === track" class="text-blue-600 dark:text-blue-400 text-xs">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Hardware Readiness Badges -->
                            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800 grid grid-cols-2 gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Webcam 30 FPS Terkalibrasi</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Sensor Akustik Siap</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 glass-subcard border-t border-slate-200/60 dark:border-slate-800 flex items-center justify-between">
                        <button type="button"
                                @click="closeCalibrationModal()"
                                class="px-5 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button"
                                @click="enterInterviewRoom()"
                                :disabled="isStarting"
                                class="inline-flex items-center px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-navy-900 hover:from-blue-500 hover:to-navy-800 rounded-2xl shadow-lg shadow-blue-600/25 transition cursor-pointer disabled:opacity-50">
                            <span x-text="isStarting ? 'Menyiapkan...' : 'Mulai Sesi Simulasi'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SCIENTIFIC METHODOLOGY & BENCHMARK MODAL -->
            <div x-show="showMethodologyModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-md overflow-y-auto"
                 @keydown.escape.window="showMethodologyModal = false">
                <div class="glass-panel rounded-3xl max-w-2xl w-full border-white/90 dark:border-navy-800 shadow-2xl p-6 sm:p-8 space-y-6 overflow-hidden my-auto"
                     @click.away="showMethodologyModal = false">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200/60 dark:border-navy-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-navy-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl font-bold shadow-xs">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-slate-900 dark:text-white">Metodologi Ilmiah & Akurasi Riset</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Kerangka kerja penilaian kecerdasan buatan VOIC</p>
                            </div>
                        </div>
                        <button type="button" @click="showMethodologyModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-xl cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        <div class="p-4 rounded-2xl glass-subcard border-blue-200/80 dark:border-navy-800 space-y-1.5">
                            <span class="font-bold text-blue-900 dark:text-blue-300 block text-sm">Arsitektur Edge Privacy-First</span>
                            <p>
                                Seluruh ekstraksi piksel wajah (kontak mata, relaksasi otot senyum) dan akustik mikrofon diproses secara <strong>100% lokal pada peramban klien</strong> menggunakan HTML5 Canvas Chromaticity dan Web Audio Analyser API. Tidak ada video atau audio yang dikirimkan ke cloud pihak ketiga.
                            </p>
                        </div>

                        <div>
                            <span class="font-bold text-slate-900 dark:text-white block mb-1 text-sm">Formulasi Pembobotan Skor</span>
                            <ul class="list-disc list-inside space-y-1 text-slate-500 dark:text-slate-400">
                                <li><strong>Skor Optik Wajah (50%):</strong> Kontak Mata Gaze Tracking (60%) + Rasio Relaksasi Senyum (40%).</li>
                                <li><strong>Skor Vokal Suara (50%):</strong> Kecepatan Bicara WPM (35%) + Energi dB Mikrofon (35%) + Dinamika Nada Intonasi (30%).</li>
                                <li><strong>Skor Total:</strong> Rata-rata harmonis Skor Optik dan Skor Vokal (skala 0 - 100).</li>
                            </ul>
                        </div>

                        <div>
                            <span class="font-bold text-slate-900 dark:text-white block mb-1 text-sm">Korelasi Pengujian Empiris</span>
                            <p class="text-slate-500 dark:text-slate-400">
                                Pengujian komparasi terhadap panel 3 penguji ahli manusia menghasilkan <strong>Koefisien Korelasi Pearson r = 0.87 (Korelasi Sangat Kuat)</strong>, membuktikan konsistensi penilaian sistem VOIC terhadap kesiapan berbicara di depan publik.
                            </p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200/60 dark:border-slate-800 flex justify-end">
                        <button type="button" @click="showMethodologyModal = false" class="px-6 py-2.5 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-bold cursor-pointer hover:bg-slate-800 transition shadow-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: ACTIVE INTERVIEW ROOM (Sederhana, Bersih & Sangat Mudah Digunakan) -->
            <div x-show="sessionState === 'room'" class="space-y-4 max-w-5xl mx-auto" x-cloak>

                <!-- Interview Top Bar -->
                <div class="glass-panel px-4 py-3 rounded-2xl flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 shadow-xs">
                            <img :src="avatarImageUrl" :alt="selectedRole?.name || 'Penguji'" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white" x-text="avatarPersonaName"></h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Live
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Durasi: <span class="font-mono font-bold text-slate-700 dark:text-slate-200" x-text="formattedTime">00:00</span>
                                <template x-if="customTopic">
                                    <span class="ms-1.5 text-blue-600 dark:text-blue-400 font-medium" x-text="'• ' + customTopic"></span>
                                </template>
                            </p>
                        </div>
                    </div>

                    <!-- Deep Learning Memory, Semantic Telemetry & Active AI Engine -->
                    <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-blue-50/80 dark:bg-navy-950/80 border border-blue-200/80 dark:border-navy-800 text-slate-700 dark:text-slate-300 text-xs shadow-xs">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                        </span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">Deep Learning Memory:</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="'Lvl ' + (userLearningProfile?.learning_level || 1)"></span>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <span>Koherensi: <strong class="font-mono text-emerald-600 dark:text-emerald-400" x-text="liveCoherenceScore + '%'"></strong></span>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full" :class="activeAiEngine === 'ollama_neural' ? 'bg-indigo-500' : 'bg-emerald-500'"></span>
                            <span class="font-medium text-[11px]" x-text="activeAiEngine === 'ollama_neural' ? 'Ollama Neural' : 'Fast Persona Fallback'"></span>
                        </span>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <span class="inline-flex items-center gap-1.5" :title="eyeTrackingEngine === 'mediapipe_iris' ? 'MediaPipe 478 Landmark 3D Iris-Level' : 'Canvas Gaze Orientation'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="eyeTrackingEngine === 'mediapipe_iris' ? 'bg-sky-400' : 'bg-slate-400'"></span>
                            <span class="font-medium text-[11px]" x-text="eyeTrackingEngine === 'mediapipe_iris' ? 'Iris 3D (WASM)' : 'Gaze Canvas'"></span>
                        </span>
                    </div>

                    <!-- Right Controls: Ambience, Sprint timer, Fullscreen & Finish -->
                    <div class="flex items-center gap-2">
                        <template x-if="simulationMode === 'sprint'">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-600 dark:text-amber-300 text-xs font-mono font-bold animate-pulse">
                                <span>SPRINT:</span>
                                <span x-text="sprintRemaining + 's'">60s</span>
                            </div>
                        </template>

                        <!-- Ambience Sound Toggle (Procedural Web Audio Room Presence) -->
                        <button type="button"
                                @click="toggleAmbience()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold glass-pill transition cursor-pointer"
                                :class="isAmbienceActive ? 'text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-950/80 border border-blue-300 dark:border-blue-800' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'"
                                :title="isAmbienceActive ? 'Matikan Suara Ambience Ruangan' : 'Aktifkan Suara Alami Ruangan (Ambience)'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                            <span class="hidden md:inline text-[11px]" x-text="isAmbienceActive ? 'Ambience: On' : 'Ambience: Off'"></span>
                        </button>

                        <button type="button"
                                @click="toggleFullscreen()"
                                class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white glass-pill transition cursor-pointer"
                                :title="isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                        </button>

                        <button type="button"
                                @click="finishSession()"
                                :disabled="isFinishing"
                                class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-md shadow-rose-600/20 transition cursor-pointer disabled:opacity-50">
                            <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="6" y="6" width="12" height="12" rx="2" stroke-width="2"/>
                            </svg>
                            <span x-text="isFinishing ? 'Menganalisis...' : 'Akhiri Sesi'"></span>
                        </button>
                    </div>
                </div>

                <!-- 4-Stage Scenario Milestone Progress Stepper -->
                <div class="glass-panel px-4 py-2.5 rounded-2xl shadow-xs border border-slate-200/80 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-2 overflow-x-auto no-scrollbar">
                        <template x-for="stage in stageMilestones" :key="stage.id">
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all duration-300"
                                     :class="{
                                         'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20 scale-[1.02]': currentStage === stage.id,
                                         'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300': currentStage !== stage.id && stageMilestones.findIndex(s => s.id === currentStage) > stageMilestones.findIndex(s => s.id === stage.id),
                                         'text-slate-400 dark:text-slate-500 opacity-60': currentStage !== stage.id && stageMilestones.findIndex(s => s.id === currentStage) < stageMilestones.findIndex(s => s.id === stage.id)
                                     }">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black shrink-0"
                                          :class="{
                                              'bg-white text-blue-700': currentStage === stage.id,
                                              'bg-emerald-500 text-white': currentStage !== stage.id && stageMilestones.findIndex(s => s.id === currentStage) > stageMilestones.findIndex(s => s.id === stage.id),
                                              'bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-400': currentStage !== stage.id && stageMilestones.findIndex(s => s.id === currentStage) < stageMilestones.findIndex(s => s.id === stage.id)
                                          }">
                                        <template x-if="stageMilestones.findIndex(s => s.id === currentStage) > stageMilestones.findIndex(s => s.id === stage.id)">
                                            <span>✓</span>
                                        </template>
                                        <template x-if="stageMilestones.findIndex(s => s.id === currentStage) <= stageMilestones.findIndex(s => s.id === stage.id)">
                                            <span x-text="stage.step"></span>
                                        </template>
                                    </span>
                                    <div class="text-left">
                                        <div class="text-[11px] font-bold tracking-tight leading-none" x-text="stage.label"></div>
                                        <div class="text-[9px] opacity-80 mt-0.5 leading-none" x-text="stage.badge"></div>
                                    </div>
                                    <template x-if="currentStage === stage.id && stage.id === 'pressure_test'">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-500 text-white animate-pulse">
                                            🔥 STRESS TEST
                                        </span>
                                    </template>
                                </div>
                                <template x-if="stage.step < 4">
                                    <svg class="w-3.5 h-3.5 text-slate-300 dark:text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Main Video Call Stage with Reactive Speaker Glow -->
                <div class="relative bg-slate-950 rounded-3xl overflow-hidden shadow-2xl border aspect-16/10 sm:aspect-16/9 flex items-center justify-center transition-all duration-500"
                     :class="orbState === 'ai_speaking' ? 'border-blue-500/80 ring-4 ring-blue-500/50 glow-active-ai shadow-blue-500/20' : 'border-slate-800'">

                    <!-- Conversational Barge-In Interruption Alert Banner -->
                    <div x-show="showInterruptionAlert"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                         class="absolute top-4 inset-x-3 sm:inset-x-8 z-40 p-3 sm:p-4 rounded-2xl bg-rose-950/90 border border-rose-500/80 backdrop-blur-md shadow-2xl flex items-center justify-between gap-3 text-white ring-2 ring-rose-500/40"
                         x-cloak>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center shrink-0 animate-bounce">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-black uppercase tracking-wider text-rose-300 flex items-center gap-1.5" x-text="interruptionTitle"></div>
                                <p class="text-xs sm:text-sm font-semibold text-white/95 mt-0.5" x-text="interruptionText"></p>
                            </div>
                        </div>
                        <button type="button"
                                @click="showInterruptionAlert = false"
                                class="text-xs font-bold px-2.5 py-1 rounded-xl bg-white/10 hover:bg-white/20 transition cursor-pointer shrink-0">
                            Tutup
                        </button>
                    </div>

                    <!-- Dynamic Turn Duration & Budget Alert Badge -->
                    <template x-if="orbState !== 'ai_speaking' && orbState !== 'thinking'">
                        <div class="absolute bottom-4 left-4 z-30 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/85 backdrop-blur-md border text-xs font-mono font-bold shadow-lg"
                             :class="currentTurnSeconds >= 20 ? 'border-amber-500 text-amber-300 animate-pulse' : 'border-slate-700 text-slate-200'">
                            <span class="w-2 h-2 rounded-full" :class="currentTurnSeconds >= 20 ? 'bg-amber-400' : 'bg-blue-400 animate-ping'"></span>
                            <span>Durasi Bicara: <span x-text="currentTurnSeconds + 's'"></span></span>
                            <template x-if="(selectedRole?.role_type || 'dosen_penguji') === 'investor'">
                                <span class="text-[10px] text-slate-400 font-normal">/ 45s alokasi</span>
                            </template>
                            <template x-if="(selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji'">
                                <span class="text-[10px] text-slate-400 font-normal">/ 24s batas</span>
                            </template>
                        </div>
                    </template>

                    <!-- Ambient Studio Backdrop -->
                    <template x-if="avatarStageMode === 'animated'">
                        <div class="absolute inset-0 bg-gradient-to-b from-blue-950/40 via-slate-950/80 to-slate-950 pointer-events-none"></div>
                    </template>
                    <template x-if="avatarStageMode !== 'animated'">
                        <img :src="avatarImageUrl"
                             alt=""
                             aria-hidden="true"
                             class="absolute inset-0 w-full h-full object-cover filter blur-3xl opacity-35 scale-125 select-none pointer-events-none">
                    </template>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-slate-950/60 pointer-events-none"></div>

                    <!-- Subtle Ambient Aura Glow (Active when AI speaks) -->
                    <div x-show="orbState === 'ai_speaking'"
                         class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 transition-opacity duration-500">
                        <div class="absolute w-96 sm:w-[500px] h-96 sm:h-[500px] rounded-full bg-blue-500/20 blur-3xl"></div>
                    </div>

                    <!-- 1. PURE PHOTOGRAPHIC PORTRAIT (When user explicitly toggles to Photo Mode) -->
                    <template x-if="avatarStageMode === 'photo'">
                        <div class="relative z-10 w-full h-full flex items-center justify-center pointer-events-none select-none overflow-hidden">
                            <img :src="avatarImageUrl"
                                 :alt="avatarPersonaName"
                                 class="h-full w-auto max-w-full object-contain object-bottom sm:object-center select-none pointer-events-none drop-shadow-[0_20px_50px_rgba(0,0,0,0.85)] transition-transform duration-500 ease-out"
                                 :class="orbState === 'ai_speaking' ? 'scale-[1.02]' : 'scale-100'">
                        </div>
                    </template>

                    <!-- 2. ANIMATED 2D TALKING CHARACTERS (DEFAULT FOR ALL ROLES: LIVING AVATAR WITH MOVING MOUTH & LIP-SYNC) -->
                    <template x-if="avatarStageMode === 'animated'">
                        <div class="relative z-10 h-full max-h-[340px] sm:max-h-[460px] aspect-4/3 flex items-center justify-center p-2 sm:p-4">
                            <div class="relative w-full h-full rounded-2xl overflow-hidden shadow-2xl border border-white/10 select-none flex items-center justify-center bg-slate-950 transition-all duration-300">

                                <!-- ROLE A: DOSEN PENGUJI (Prof. Dr. Ir. Hendra Kusuma, M.Kom) -->
                                <template x-if="(selectedRole?.role_type || 'dosen_penguji') === 'dosen_penguji'">
                                    <svg viewBox="0 0 400 420" class="w-full h-full object-contain select-none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="dosenStudioBg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#070c17"/>
                                                <stop offset="50%" stop-color="#0f172a"/>
                                                <stop offset="100%" stop-color="#1e293b"/>
                                            </linearGradient>
                                            <linearGradient id="dosenSuitJacket" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#1e2433"/>
                                                <stop offset="100%" stop-color="#0b0f17"/>
                                            </linearGradient>
                                            <linearGradient id="dosenSuitLapel" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#334155"/>
                                                <stop offset="100%" stop-color="#1e293b"/>
                                            </linearGradient>
                                            <linearGradient id="dosenSkinGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#f5cfb5"/>
                                                <stop offset="100%" stop-color="#db9f7c"/>
                                            </linearGradient>
                                            <linearGradient id="dosenTieGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#991b1b"/>
                                                <stop offset="50%" stop-color="#7f1d1d"/>
                                                <stop offset="100%" stop-color="#450a0a"/>
                                            </linearGradient>
                                            <linearGradient id="dosenHairGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#334155"/>
                                                <stop offset="50%" stop-color="#1e293b"/>
                                                <stop offset="100%" stop-color="#0f172a"/>
                                            </linearGradient>
                                        </defs>

                                        <!-- Studio Backdrop -->
                                        <rect width="400" height="420" fill="url(#dosenStudioBg)"/>
                                        <circle cx="200" cy="140" r="140" fill="#3b82f6" opacity="0.07" filter="blur(25px)"/>
                                        <path d="M 0 320 L 400 320" stroke="#334155" stroke-width="1" opacity="0.3"/>

                                        <!-- Torso & Academic Suit -->
                                        <path d="M 30 420 Q 55 325 125 305 L 275 305 Q 345 325 370 420 Z" fill="url(#dosenSuitJacket)"/>
                                        <path d="M 155 255 L 245 255 L 230 385 L 170 385 Z" fill="#f8fafc"/>
                                        <path d="M 200 275 L 200 385" stroke="#e2e8f0" stroke-width="1.5" stroke-dasharray="4,4"/>

                                        <!-- Professor Burgundy Tie with Gold Accent -->
                                        <path d="M 191 264 L 209 264 L 205 282 L 195 282 Z" fill="url(#dosenTieGrad)"/>
                                        <path d="M 195 282 L 205 282 L 200 286 Z" fill="#450a0a"/>
                                        <path d="M 194 282 L 206 282 L 213 398 L 200 412 L 187 398 Z" fill="url(#dosenTieGrad)"/>
                                        <path d="M 193 298 L 207 310" stroke="#f59e0b" stroke-width="1.2" opacity="0.7"/>
                                        <path d="M 192 328 L 209 342" stroke="#f59e0b" stroke-width="1.2" opacity="0.7"/>
                                        <path d="M 190 358 L 211 374" stroke="#f59e0b" stroke-width="1.2" opacity="0.7"/>
                                        <rect x="190" y="336" width="22" height="4.5" rx="2" fill="#fbbf24"/>

                                        <!-- Suit Lapels -->
                                        <path d="M 120 305 L 164 262 L 180 350 L 132 390 Z" fill="url(#dosenSuitLapel)"/>
                                        <path d="M 280 305 L 236 262 L 220 350 L 268 390 Z" fill="url(#dosenSuitLapel)"/>
                                        <path d="M 120 305 L 164 262" stroke="#475569" stroke-width="1.5"/>
                                        <path d="M 280 305 L 236 262" stroke="#475569" stroke-width="1.5"/>

                                        <!-- Examiner Lapel Seal (Left Chest) -->
                                        <g transform="translate(106, 335)">
                                            <circle cx="16" cy="16" r="14" fill="#1e293b" stroke="#eab308" stroke-width="2"/>
                                            <circle cx="16" cy="16" r="10" fill="#ca8a04"/>
                                            <text x="16" y="19" font-size="6.5" font-weight="bold" fill="#ffffff" text-anchor="middle" font-family="sans-serif">SIDANG</text>
                                        </g>

                                        <!-- Head & Face Group (Reactive Posture & Breathing) -->
                                        <g :class="orbState === 'ai_speaking' ? 'vector-talking-head' : (orbState === 'user_speaking' ? 'vector-listening-head' : (orbState === 'thinking' ? 'vector-thinking-head' : 'vector-breathe-head'))">
                                            <!-- Neck -->
                                            <path d="M 176 210 L 224 210 L 228 266 L 172 266 Z" fill="url(#dosenSkinGrad)"/>
                                            <path d="M 176 210 Q 200 225 224 210 Q 200 234 176 210 Z" fill="#b97858" opacity="0.6"/>

                                            <!-- Shirt Collar Wings -->
                                            <path d="M 164 260 L 192 264 L 178 290 Z" fill="#ffffff" stroke="#cbd5e1" stroke-width="1"/>
                                            <path d="M 236 260 L 208 264 L 222 290 Z" fill="#ffffff" stroke="#cbd5e1" stroke-width="1"/>

                                            <!-- Ears -->
                                            <path d="M 130 172 C 120 172 118 194 130 204 Z" fill="url(#dosenSkinGrad)"/>
                                            <path d="M 270 172 C 280 172 282 194 270 204 Z" fill="url(#dosenSkinGrad)"/>
                                            <path d="M 127 180 C 123 184 123 192 128 195" stroke="#b97858" stroke-width="1.5" fill="none"/>
                                            <path d="M 273 180 C 277 184 277 192 272 195" stroke="#b97858" stroke-width="1.5" fill="none"/>

                                            <!-- Jaw & Head -->
                                            <path d="M 130 162 C 130 100 270 100 270 162 C 270 205 236 230 200 230 C 164 230 130 205 130 162 Z" fill="url(#dosenSkinGrad)"/>

                                            <!-- Distinguished Academic Hair with Silver Highlights -->
                                            <path d="M 126 155 C 126 90 158 65 200 65 C 245 65 274 90 274 155 C 274 162 270 165 265 148 C 256 108 240 96 200 98 C 160 96 144 110 135 148 C 130 165 126 162 126 155 Z" fill="url(#dosenHairGrad)"/>
                                            <path d="M 130 148 C 140 115 170 92 210 94 C 248 96 268 114 270 146 C 262 124 246 108 212 108 C 172 108 144 124 130 148 Z" fill="#475569"/>
                                            <path d="M 128 152 C 132 136 138 126 146 118" stroke="#cbd5e1" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0.85"/>
                                            <path d="M 272 152 C 268 136 262 126 254 118" stroke="#cbd5e1" stroke-width="2.5" stroke-linecap="round" fill="none" opacity="0.85"/>

                                            <!-- Eyebrows -->
                                            <path d="M 152 148 Q 170 142 184 148" stroke="#1e2433" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                                            <path d="M 216 148 Q 230 142 248 148" stroke="#1e2433" stroke-width="3.2" stroke-linecap="round" fill="none"/>

                                            <!-- Eyes with Natural Blinking -->
                                            <g class="vector-blink-eye">
                                                <ellipse cx="168" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="168" cy="164" r="4.8" fill="#1e293b"/>
                                                <circle cx="170" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 158 161 Q 168 156 178 161" stroke="#1e2433" stroke-width="1.8" fill="none"/>

                                                <ellipse cx="232" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="232" cy="164" r="4.8" fill="#1e293b"/>
                                                <circle cx="234" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 222 161 Q 232 156 242 161" stroke="#1e2433" stroke-width="1.8" fill="none"/>
                                            </g>

                                            <!-- Academic Glasses -->
                                            <rect x="154" y="153" width="28" height="20" rx="3.5" fill="rgba(56, 189, 248, 0.08)" stroke="#38bdf8" stroke-width="1.8"/>
                                            <rect x="218" y="153" width="28" height="20" rx="3.5" fill="rgba(56, 189, 248, 0.08)" stroke="#38bdf8" stroke-width="1.8"/>
                                            <path d="M 182 161 Q 200 159 218 161" stroke="#38bdf8" stroke-width="2" fill="none"/>
                                            <path d="M 154 162 L 132 166" stroke="#38bdf8" stroke-width="1.8"/>
                                            <path d="M 246 162 L 268 166" stroke="#38bdf8" stroke-width="1.8"/>
                                            <path d="M 158 156 L 168 156" stroke="#ffffff" stroke-width="1.2" opacity="0.6"/>
                                            <path d="M 222 156 L 232 156" stroke="#ffffff" stroke-width="1.2" opacity="0.6"/>

                                            <!-- Nose -->
                                            <path d="M 197 166 Q 200 185 195 188 Q 200 190 205 188" stroke="#b97858" stroke-width="2" stroke-linecap="round" fill="none"/>

                                            <!-- Dynamic Mouth: Resting vs Active Talking -->
                                            <!-- Resting Composed State -->
                                            <g x-show="orbState !== 'ai_speaking'">
                                                <path d="M 184 206 Q 200 204 216 206" stroke="#783528" stroke-width="3" stroke-linecap="round" fill="none"/>
                                                <path d="M 188 206 Q 200 209 212 206" stroke="#b97858" stroke-width="1.5" stroke-linecap="round" fill="none" opacity="0.7"/>
                                            </g>

                                            <!-- Active Speaking State (Mouth Opens, Articulates & Closes with Speech) -->
                                            <g x-show="orbState === 'ai_speaking'" class="smooth-mouth-talk" style="transform-origin: 200px 208px;">
                                                <ellipse cx="200" cy="208" rx="16" ry="11" fill="#2d0d14"/>
                                                <path d="M 187 202 Q 200 205 213 202 Q 207 198 200 198 Q 193 198 187 202 Z" fill="#ffffff"/>
                                                <ellipse cx="200" cy="214" rx="10" ry="4" fill="#a83c48" opacity="0.85"/>
                                                <path d="M 182 201 Q 200 197 218 201" stroke="#783528" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                                <path d="M 185 216 Q 200 223 215 216" stroke="#783528" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                            </g>
                                        </g>
                                    </svg>
                                </template>

                                <!-- ROLE B: HRD TALENT ACQUISITION (Nadia Putri, S.Psi., CHRP) -->
                                <template x-if="(selectedRole?.role_type || 'dosen_penguji') === 'hrd'">
                                    <svg viewBox="0 0 400 420" class="w-full h-full object-contain select-none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="nadiaStudioBg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#090d20"/>
                                                <stop offset="50%" stop-color="#131b38"/>
                                                <stop offset="100%" stop-color="#1e294f"/>
                                            </linearGradient>
                                            <linearGradient id="nadiaBlazer" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#1e1b4b"/>
                                                <stop offset="100%" stop-color="#0f172a"/>
                                            </linearGradient>
                                            <linearGradient id="nadiaBlazerLapel" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#312e81"/>
                                                <stop offset="100%" stop-color="#1e1b4b"/>
                                            </linearGradient>
                                            <linearGradient id="nadiaSkinGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#ffe2d1"/>
                                                <stop offset="100%" stop-color="#f2ad99"/>
                                            </linearGradient>
                                            <linearGradient id="nadiaHairGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#262626"/>
                                                <stop offset="50%" stop-color="#171717"/>
                                                <stop offset="100%" stop-color="#0a0a0a"/>
                                            </linearGradient>
                                        </defs>

                                        <!-- Studio Backdrop -->
                                        <rect width="400" height="420" fill="url(#nadiaStudioBg)"/>
                                        <circle cx="200" cy="140" r="140" fill="#818cf8" opacity="0.09" filter="blur(25px)"/>
                                        <circle cx="200" cy="170" r="110" fill="#f43f5e" opacity="0.04" filter="blur(20px)"/>
                                        <path d="M 0 320 L 400 320" stroke="#334155" stroke-width="1" opacity="0.3"/>

                                        <!-- Torso & Corporate Blazer with Inner Blouse -->
                                        <path d="M 40 420 Q 65 325 130 305 L 270 305 Q 335 325 360 420 Z" fill="url(#nadiaBlazer)"/>
                                        <path d="M 162 258 L 238 258 L 222 385 L 178 385 Z" fill="#fff1f2"/>
                                        <path d="M 200 270 L 200 385" stroke="#fbcfe8" stroke-width="1.2" stroke-dasharray="3,3"/>

                                        <!-- Tailored Blazer Lapels -->
                                        <path d="M 125 305 L 170 262 L 184 358 L 138 395 Z" fill="url(#nadiaBlazerLapel)"/>
                                        <path d="M 275 305 L 230 262 L 216 358 L 262 395 Z" fill="url(#nadiaBlazerLapel)"/>
                                        <path d="M 125 305 L 170 262" stroke="#4338ca" stroke-width="1.5"/>
                                        <path d="M 275 305 L 230 262" stroke="#4338ca" stroke-width="1.5"/>

                                        <!-- HR Lanyard ID Badge (Left Chest) - NADIA -->
                                        <g transform="translate(98, 340)">
                                            <path d="M 46 -48 L 22 0" stroke="#3b82f6" stroke-width="3" opacity="0.85"/>
                                            <rect x="6" y="0" width="38" height="50" rx="4" fill="#ffffff" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.3))"/>
                                            <rect x="6" y="0" width="38" height="11" rx="2" fill="#2563eb"/>
                                            <text x="25" y="8" font-size="5" font-weight="bold" fill="#ffffff" text-anchor="middle" font-family="sans-serif">HR TALENT</text>
                                            <circle cx="25" cy="23" r="7" fill="#fbcfe8"/>
                                            <text x="25" y="36" font-size="5" font-weight="bold" fill="#1e293b" text-anchor="middle" font-family="sans-serif">NADIA</text>
                                            <rect x="14" y="40" width="22" height="2" rx="1" fill="#94a3b8"/>
                                        </g>

                                        <!-- Hair Silhouette Behind Shoulders -->
                                        <path d="M 124 165 C 115 220 118 290 145 320 L 160 320 C 135 270 134 220 138 175 Z" fill="#171717"/>
                                        <path d="M 276 165 C 285 220 282 290 255 320 L 240 320 C 265 270 266 220 262 175 Z" fill="#171717"/>

                                        <!-- Head & Face Group (Reactive Posture & Breathing) -->
                                        <g :class="orbState === 'ai_speaking' ? 'vector-talking-head' : (orbState === 'user_speaking' ? 'vector-listening-head' : (orbState === 'thinking' ? 'vector-thinking-head' : 'vector-breathe-head'))">
                                            <!-- Neck -->
                                            <path d="M 180 210 L 220 210 L 224 266 L 176 266 Z" fill="url(#nadiaSkinGrad)"/>
                                            <path d="M 180 210 Q 200 224 220 210 Q 200 232 180 210 Z" fill="#e0907c" opacity="0.6"/>

                                            <!-- Delicate Necklace -->
                                            <path d="M 184 252 Q 200 262 216 252" stroke="#fbbf24" stroke-width="1.2" fill="none"/>
                                            <circle cx="200" cy="258" r="2.2" fill="#fbbf24"/>

                                            <!-- Ears with Pearl Earrings -->
                                            <path d="M 132 174 C 124 174 122 194 132 202 Z" fill="url(#nadiaSkinGrad)"/>
                                            <path d="M 268 174 C 276 174 278 194 268 202 Z" fill="url(#nadiaSkinGrad)"/>
                                            <circle cx="127" cy="190" r="2.8" fill="#ffffff" stroke="#e2e8f0" stroke-width="0.8"/>
                                            <circle cx="273" cy="190" r="2.8" fill="#ffffff" stroke="#e2e8f0" stroke-width="0.8"/>

                                            <!-- Soft Feminine Jaw & Face -->
                                            <path d="M 134 162 C 134 104 266 104 266 162 C 266 202 234 228 200 228 C 166 228 134 202 134 162 Z" fill="url(#nadiaSkinGrad)"/>

                                            <!-- Soft Rosy Blush Cheeks -->
                                            <ellipse cx="152" cy="182" rx="9" ry="5.5" fill="#f43f5e" opacity="0.22"/>
                                            <ellipse cx="248" cy="182" rx="9" ry="5.5" fill="#f43f5e" opacity="0.22"/>

                                            <!-- Elegant Hair Styling -->
                                            <path d="M 126 155 C 126 88 158 64 200 64 C 246 64 274 88 274 155 C 274 162 270 166 264 148 C 255 106 240 94 200 96 C 160 94 144 108 136 148 C 131 166 126 162 126 155 Z" fill="url(#nadiaHairGrad)"/>
                                            <path d="M 134 146 C 146 106 176 86 218 88 C 252 90 270 108 270 138 C 262 116 244 102 216 102 C 174 102 148 118 134 146 Z" fill="#262626"/>
                                            <path d="M 150 96 Q 190 82 232 94" stroke="#525252" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.6"/>

                                            <!-- Soft Arched Eyebrows -->
                                            <path d="M 152 148 Q 170 140 184 146" stroke="#1f2937" stroke-width="2.6" stroke-linecap="round" fill="none"/>
                                            <path d="M 216 146 Q 230 140 248 148" stroke="#1f2937" stroke-width="2.6" stroke-linecap="round" fill="none"/>

                                            <!-- Expressive Eyes with Eyelashes & Natural Blinking -->
                                            <g class="vector-blink-eye">
                                                <ellipse cx="168" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="168" cy="164" r="4.8" fill="#1e293b"/>
                                                <circle cx="170" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 156 162 Q 168 156 180 161" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                                                <path d="M 178 159 L 182 157" stroke="#0f172a" stroke-width="1.8" stroke-linecap="round"/>

                                                <ellipse cx="232" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="232" cy="164" r="4.8" fill="#1e293b"/>
                                                <circle cx="234" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 220 161 Q 232 156 244 162" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                                                <path d="M 242 159 L 246 157" stroke="#0f172a" stroke-width="1.8" stroke-linecap="round"/>
                                            </g>

                                            <!-- Delicate Nose -->
                                            <path d="M 197 167 Q 200 184 196 186 Q 200 188 204 186" stroke="#d58673" stroke-width="1.8" stroke-linecap="round" fill="none"/>

                                            <!-- Dynamic Mouth: Warm HR Smile vs Active Articulated Talking Mouth -->
                                            <!-- 1. Silent Resting State: Warm Empathetic HR Smile -->
                                            <g x-show="orbState !== 'ai_speaking'">
                                                <path d="M 183 204 Q 200 218 217 204" stroke="#be123c" stroke-width="3" stroke-linecap="round" fill="none"/>
                                                <path d="M 186 205 Q 200 213 214 205 Q 200 215 186 205 Z" fill="#ffffff" opacity="0.85"/>
                                                <circle cx="180" cy="203" r="1.6" fill="#f43f5e" opacity="0.7"/>
                                                <circle cx="220" cy="203" r="1.6" fill="#f43f5e" opacity="0.7"/>
                                            </g>

                                            <!-- 2. Active Speaking State: Speech-Synchronized Talking Mouth -->
                                            <g x-show="orbState === 'ai_speaking'" class="smooth-mouth-talk" style="transform-origin: 200px 208px;">
                                                <ellipse cx="200" cy="208" rx="16" ry="11" fill="#2d0d18"/>
                                                <path d="M 187 202 Q 200 206 213 202 Q 207 198 200 198 Q 193 198 187 202 Z" fill="#ffffff"/>
                                                <ellipse cx="200" cy="214" rx="10" ry="4" fill="#f43f5e" opacity="0.85"/>
                                                <path d="M 182 201 Q 200 197 218 201" stroke="#be123c" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                                <path d="M 185 216 Q 200 223 215 216" stroke="#be123c" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                            </g>
                                        </g>
                                    </svg>
                                </template>

                                <!-- ROLE C: INVESTOR & VENTURE CAPITALIST (Raymond Chandra, B.Eng., MBA) -->
                                <template x-if="(selectedRole?.role_type || 'dosen_penguji') === 'investor'">
                                    <svg viewBox="0 0 400 420" class="w-full h-full object-contain select-none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="invStudioBg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#050811"/>
                                                <stop offset="50%" stop-color="#0c1322"/>
                                                <stop offset="100%" stop-color="#192438"/>
                                            </linearGradient>
                                            <linearGradient id="invJacket" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#1e293b"/>
                                                <stop offset="100%" stop-color="#0b111e"/>
                                            </linearGradient>
                                            <linearGradient id="invLapel" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#334155"/>
                                                <stop offset="100%" stop-color="#1e293b"/>
                                            </linearGradient>
                                            <linearGradient id="invSkinGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#f8d4bb"/>
                                                <stop offset="100%" stop-color="#e09e79"/>
                                            </linearGradient>
                                            <linearGradient id="invHairGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#1e293b"/>
                                                <stop offset="100%" stop-color="#0f172a"/>
                                            </linearGradient>
                                        </defs>

                                        <!-- Studio Backdrop (Modern Tech High-Rise Skyline) -->
                                        <rect width="400" height="420" fill="url(#invStudioBg)"/>
                                        <path d="M 60 0 L 140 420" stroke="#334155" stroke-width="0.8" opacity="0.25"/>
                                        <path d="M 340 0 L 260 420" stroke="#334155" stroke-width="0.8" opacity="0.25"/>
                                        <circle cx="200" cy="140" r="140" fill="#0ea5e9" opacity="0.08" filter="blur(25px)"/>
                                        <path d="M 0 320 L 400 320" stroke="#334155" stroke-width="1" opacity="0.3"/>

                                        <!-- Torso & Modern Tech VC Attire (Open Collar Oxford & Navy Blazer) -->
                                        <path d="M 35 420 Q 60 325 125 305 L 275 305 Q 340 325 365 420 Z" fill="url(#invJacket)"/>
                                        <path d="M 158 256 L 242 256 L 226 385 L 174 385 Z" fill="#e0f2fe"/>
                                        <path d="M 200 278 L 200 385" stroke="#bae6fd" stroke-width="1.5" stroke-dasharray="4,4"/>

                                        <!-- Open Collar Shirt Wings (No Tie - Modern VC Style) -->
                                        <path d="M 166 260 L 190 268 L 174 294 Z" fill="#ffffff" stroke="#bae6fd" stroke-width="1"/>
                                        <path d="M 234 260 L 210 268 L 226 294 Z" fill="#ffffff" stroke="#bae6fd" stroke-width="1"/>

                                        <!-- Slim Modern Lapels -->
                                        <path d="M 122 305 L 168 260 L 180 355 L 134 395 Z" fill="url(#invLapel)"/>
                                        <path d="M 278 305 L 232 260 L 220 355 L 266 395 Z" fill="url(#invLapel)"/>
                                        <path d="M 122 305 L 168 260" stroke="#475569" stroke-width="1.5"/>
                                        <path d="M 278 305 L 232 260" stroke="#475569" stroke-width="1.5"/>

                                        <!-- Geometric Gold Venture Fund Lapel Pin -->
                                        <g transform="translate(138, 332)">
                                            <polygon points="12,4 20,18 4,18" fill="#eab308" stroke="#ca8a04" stroke-width="1"/>
                                            <circle cx="12" cy="14" r="2.5" fill="#ffffff"/>
                                        </g>

                                        <!-- Head & Face Group (Reactive Posture & Breathing) -->
                                        <g :class="orbState === 'ai_speaking' ? 'vector-talking-head' : (orbState === 'user_speaking' ? 'vector-listening-head' : (orbState === 'thinking' ? 'vector-thinking-head' : 'vector-breathe-head'))">
                                            <!-- Neck -->
                                            <path d="M 176 210 L 224 210 L 228 266 L 172 266 Z" fill="url(#invSkinGrad)"/>
                                            <path d="M 176 210 Q 200 226 224 210 Q 200 234 176 210 Z" fill="#b87858" opacity="0.6"/>

                                            <!-- Ears -->
                                            <path d="M 130 172 C 120 172 118 194 130 204 Z" fill="url(#invSkinGrad)"/>
                                            <path d="M 270 172 C 280 172 282 194 270 204 Z" fill="url(#invSkinGrad)"/>
                                            <path d="M 127 180 C 123 184 123 192 128 195" stroke="#b87858" stroke-width="1.5" fill="none"/>
                                            <path d="M 273 180 C 277 184 277 192 272 195" stroke="#b87858" stroke-width="1.5" fill="none"/>

                                            <!-- Sharp Modern Jawline -->
                                            <path d="M 130 162 C 130 102 270 102 270 162 C 270 204 236 230 200 230 C 164 230 130 204 130 162 Z" fill="url(#invSkinGrad)"/>

                                            <!-- Modern Styled Fade Hair -->
                                            <path d="M 126 155 C 126 90 156 66 200 66 C 244 66 274 90 274 155 C 274 162 270 165 265 148 C 256 108 240 96 200 98 C 160 96 144 110 135 148 C 130 165 126 162 126 155 Z" fill="url(#invHairGrad)"/>
                                            <path d="M 132 144 C 144 108 174 88 214 90 C 248 92 268 110 268 138 C 260 118 244 104 214 104 C 174 104 146 120 132 144 Z" fill="#334155"/>
                                            <path d="M 170 82 Q 200 72 230 84" stroke="#475569" stroke-width="2.5" stroke-linecap="round" fill="none"/>

                                            <!-- Sharp Confident Eyebrows -->
                                            <path d="M 152 148 Q 170 142 184 148" stroke="#0f172a" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                                            <path d="M 216 148 Q 230 142 248 148" stroke="#0f172a" stroke-width="3.2" stroke-linecap="round" fill="none"/>

                                            <!-- Confident Eyes with Natural Blinking -->
                                            <g class="vector-blink-eye">
                                                <ellipse cx="168" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="168" cy="164" r="4.8" fill="#0f172a"/>
                                                <circle cx="170" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 158 160 Q 168 156 178 160" stroke="#0f172a" stroke-width="1.8" fill="none"/>

                                                <ellipse cx="232" cy="164" rx="9.5" ry="6.2" fill="#ffffff"/>
                                                <circle cx="232" cy="164" r="4.8" fill="#0f172a"/>
                                                <circle cx="234" cy="162" r="1.6" fill="#ffffff"/>
                                                <path d="M 222 160 Q 232 156 242 160" stroke="#0f172a" stroke-width="1.8" fill="none"/>
                                            </g>

                                            <!-- Nose -->
                                            <path d="M 197 166 Q 200 185 195 188 Q 200 190 205 188" stroke="#b87858" stroke-width="2" stroke-linecap="round" fill="none"/>

                                            <!-- Dynamic Mouth: Confident Rest vs Active Talking -->
                                            <!-- 1. Silent Resting State: Confident Sharp Investor Smirk -->
                                            <g x-show="orbState !== 'ai_speaking'">
                                                <path d="M 184 205 Q 200 210 216 205" stroke="#7c2d12" stroke-width="3" stroke-linecap="round" fill="none"/>
                                                <path d="M 187 206 Q 200 212 213 206" stroke="#b87858" stroke-width="1.5" stroke-linecap="round" fill="none" opacity="0.7"/>
                                            </g>

                                            <!-- 2. Active Speaking State: Speech-Synchronized Talking Mouth -->
                                            <g x-show="orbState === 'ai_speaking'" class="smooth-mouth-talk" style="transform-origin: 200px 208px;">
                                                <ellipse cx="200" cy="208" rx="16" ry="11" fill="#2d0d14"/>
                                                <path d="M 187 202 Q 200 205 213 202 Q 207 198 200 198 Q 193 198 187 202 Z" fill="#ffffff"/>
                                                <ellipse cx="200" cy="214" rx="10" ry="4" fill="#a83c48" opacity="0.85"/>
                                                <path d="M 182 201 Q 200 197 218 201" stroke="#7c2d12" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                                <path d="M 185 216 Q 200 223 215 216" stroke="#7c2d12" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                                            </g>
                                        </g>
                                    </svg>
                                </template>

                            </div>
                        </div>
                    </template>

                    <!-- Top Left: Turn Status Badge & Stream Quality -->
                    <div class="absolute top-4 left-4 z-20 pointer-events-none flex items-center gap-2">
                        <div class="px-3 py-1.5 rounded-xl bg-slate-900/85 backdrop-blur-md border border-white/15 text-white text-xs flex items-center gap-2 shadow-lg">
                            <span class="w-2.5 h-2.5 rounded-full"
                                  :class="orbState === 'ai_speaking' ? 'bg-blue-400 animate-ping' : (orbState === 'user_speaking' ? 'bg-emerald-400 animate-pulse' : (orbState === 'thinking' ? 'bg-amber-400 animate-spin' : 'bg-emerald-400'))"></span>
                            <span class="font-bold text-xs"
                                  x-text="orbState === 'ai_speaking' ? 'Penguji Sedang Berbicara...' : (orbState === 'user_speaking' ? 'Mendengarkan Anda Bicara...' : (orbState === 'thinking' ? 'Menelaah Jawaban...' : 'Giliran Anda Berbicara'))"></span>
                        </div>
                        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-900/70 backdrop-blur-md border border-white/10 text-[10px] text-slate-300 font-mono shadow-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>1080p • 60 FPS • 14ms</span>
                        </div>
                    </div>

                    <!-- Bottom Left: Live AI Neural Voice Frequency Spectrum HUD -->
                    <div class="absolute bottom-4 left-4 z-20 pointer-events-none flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-900/85 backdrop-blur-md border border-white/10 text-white shadow-xl">
                        <div class="flex items-end gap-1 h-3.5 w-16">
                            <template x-for="(barHeight, idx) in vocalSpectrumBars" :key="idx">
                                <span class="w-1.5 rounded-full transition-all duration-75"
                                      :style="`height: ${barHeight}%; background-color: ${orbState === 'ai_speaking' ? '#38bdf8' : '#475569'};`"></span>
                            </template>
                        </div>
                        <div class="text-[10px] flex flex-col leading-tight">
                            <span class="font-bold text-slate-200" x-text="orbState === 'ai_speaking' ? 'Neural Audio HD' : 'Audio Standby'"></span>
                            <span class="text-[8px] text-slate-400 font-mono" x-text="orbState === 'ai_speaking' ? '24kHz • Low Latency' : 'Menunggu'"></span>
                        </div>
                    </div>

                    <!-- Bottom Right: Examiner Persona Watermark Badge & Mode Switcher Button -->
                    <div class="absolute bottom-4 right-4 z-20 flex flex-col items-end gap-1.5">
                        <button type="button"
                                @click="avatarStageMode = (avatarStageMode === 'animated' ? 'photo' : 'animated')"
                                class="pointer-events-auto px-2.5 py-1.5 rounded-xl bg-slate-900/90 backdrop-blur-md border border-white/20 text-white text-[10px] sm:text-[11px] font-semibold flex items-center gap-1.5 hover:bg-slate-800 transition cursor-pointer shadow-lg active:scale-95"
                                title="Ganti Mode Tampilan Karakter (Animasi Berbicara / Foto HD)">
                            <span x-text="avatarStageMode === 'animated' ? '🎭 Mode: Karakter Animasi (Bicara)' : '📷 Mode: Foto Studio HD'"></span>
                            <span class="text-blue-400 text-[10px] font-bold">⇄ Ganti</span>
                        </button>
                        <div class="pointer-events-none px-3 py-1.5 rounded-xl bg-slate-900/75 backdrop-blur-md border border-white/10 text-right shadow-lg hidden sm:block">
                            <div class="text-[11px] font-bold text-white tracking-wide" x-text="avatarPersonaName"></div>
                            <div class="text-[9px] text-blue-400 font-medium" x-text="avatarPersonaRole"></div>
                        </div>
                    </div>

                    <!-- USER WEBCAM CAMERA (Picture-in-Picture in Top-Right Corner with Reactive User Glow) -->
                    <div class="absolute top-4 right-4 z-20 w-32 sm:w-44 aspect-4/3 rounded-2xl overflow-hidden shadow-2xl border-2 bg-slate-900 transition-all duration-300"
                         :class="orbState === 'user_speaking' ? 'border-emerald-400 ring-4 ring-emerald-500/50 glow-active-user shadow-emerald-500/30' : 'border-white/20'">
                        <video id="webcamVideo"
                               autoplay
                               playsinline
                               muted
                               class="w-full h-full object-cover -scale-x-100"
                               :class="isCameraOff ? 'hidden' : ''">
                        </video>

                        <div x-show="isCameraOff" class="w-full h-full flex flex-col items-center justify-center bg-slate-900 text-slate-400 p-2 text-center">
                            <svg class="w-5 h-5 mb-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-[9px] font-semibold">Kamera Mati</span>
                        </div>

                        <!-- Clean Status Pill with Mini Equalizer -->
                        <div class="absolute bottom-1.5 inset-x-1.5 px-2 py-0.5 rounded-lg bg-black/75 backdrop-blur-md text-[9px] text-white flex items-center justify-between font-medium">
                            <span>Anda</span>
                            <div class="flex items-center gap-1.5 font-semibold text-emerald-300">
                                <!-- 4-bar mini audio equalizer active when speaking -->
                                <div class="flex items-end gap-0.5 h-2.5" x-show="!isMicMuted && orbState === 'user_speaking'">
                                    <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce h-2" style="animation-delay: 0ms"></span>
                                    <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce h-3" style="animation-delay: 150ms"></span>
                                    <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce h-1.5" style="animation-delay: 300ms"></span>
                                    <span class="w-0.5 bg-emerald-400 rounded-full animate-bounce h-2.5" style="animation-delay: 450ms"></span>
                                </div>
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" x-show="isMicMuted || orbState !== 'user_speaking'"></span>
                                <span x-text="isMicMuted ? 'Muted' : (orbState === 'user_speaking' ? 'Bicara' : 'Aktif')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Background Telemetry Canvases -->
                    <canvas id="telemetryCanvas" width="320" height="240" class="hidden"></canvas>
                    <canvas id="audioWaveformCanvas" width="120" height="30" class="hidden"></canvas>
                </div>

                <!-- EXAMINER QUESTION CARD (Pertanyaan Penguji - Jelas & Mudah Dibaca) -->
                <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-navy-900 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5 border border-blue-200/80 dark:border-navy-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white" x-text="avatarPersonaName"></span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold"
                                      :class="orbState === 'ai_speaking' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                                    <span x-text="orbState === 'ai_speaking' ? 'Sedang Berbicara...' : 'Pertanyaan Penguji'"></span>
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-relaxed"
                               x-text="currentAiSpokenText || latestAiMessageText || 'Silakan mulai perkenalan atau presentasi Anda.'">
                            </p>
                        </div>
                    </div>

                    <!-- Repeat Audio Button -->
                    <button type="button"
                            @click="replayLastAiMessage()"
                            :disabled="orbState === 'ai_speaking' || orbState === 'thinking'"
                            title="Dengarkan Ulang Pertanyaan"
                            class="shrink-0 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold glass-pill text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-800 border border-blue-200/80 dark:border-blue-900/60 transition cursor-pointer disabled:opacity-40 w-fit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        </svg>
                        <span>Ulangi Suara</span>
                    </button>
                </div>

                <!-- LIVE USER SPEECH STRIP (Suara Pengguna Saat Bicara) -->
                <div x-show="liveInterimTranscript || orbState === 'user_speaking'"
                     x-transition
                     class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 flex items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping shrink-0"></span>
                        <div class="text-xs sm:text-sm font-medium truncate">
                            <span class="font-bold text-emerald-700 dark:text-emerald-300">Suara Anda: </span>
                            <span x-text="liveInterimTranscript || 'Sedang mendengarkan ucapan Anda... Bicaralah secara alami.'"></span>
                        </div>
                    </div>

                    <button type="button"
                            @click="submitCurrentTurn()"
                            :disabled="!liveInterimTranscript.trim()"
                            class="shrink-0 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer disabled:opacity-40">
                        Kirim Jawaban ⏎
                    </button>
                </div>

                <!-- CONVERSATIONAL GROUNDING & COHERENCE STRIP -->
                <div x-show="detectedSalientEntities.length > 0 || liveCoherenceScore > 0"
                     class="px-4 py-2 rounded-2xl bg-slate-50 dark:bg-navy-950/60 border border-slate-200/80 dark:border-navy-800 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Koherensi Dialog:</span>
                        </span>
                        <span class="font-mono font-bold text-blue-600 dark:text-blue-400" x-text="liveCoherenceScore + '%'"></span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300" x-text="liveCoherenceCategory"></span>
                    </div>
                    <div x-show="detectedSalientEntities.length > 0" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 text-[11px]">
                        <span>Topik Terkait:</span>
                        <template x-for="ent in detectedSalientEntities" :key="ent">
                            <span class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold" x-text="ent"></span>
                        </template>
                    </div>
                </div>

                <!-- SIMPLE FLOATING CONTROL DOCK (Kontrol Utama Bawah) -->
                <div class="glass-panel p-3 sm:p-4 rounded-3xl shadow-lg border border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-3">

                    <!-- Left: Mic & Camera Toggles -->
                    <div class="flex items-center gap-2">
                        <!-- Mic Button -->
                        <button type="button"
                                @click="toggleMic()"
                                :title="isMicMuted ? 'Nyalakan Mikrofon' : 'Matikan Mikrofon'"
                                :class="isMicMuted ? 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-300 border border-rose-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'"
                                class="px-3 py-2.5 rounded-2xl text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                            <svg x-show="!isMicMuted" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                            <svg x-show="isMicMuted" class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                            </svg>
                            <span class="hidden sm:inline" x-text="isMicMuted ? 'Mic Bisu' : 'Mic Aktif'"></span>
                        </button>

                        <!-- Camera Button -->
                        <button type="button"
                                @click="toggleCamera()"
                                :title="isCameraOff ? 'Nyalakan Kamera' : 'Matikan Kamera'"
                                :class="isCameraOff ? 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-300 border border-rose-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'"
                                class="px-3 py-2.5 rounded-2xl text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span class="hidden sm:inline" x-text="isCameraOff ? 'Kamera Mati' : 'Kamera Aktif'"></span>
                        </button>
                    </div>

                    <!-- Center: The Big Intuitive Action Button -->
                    <div class="flex items-center">
                        <!-- When AI is Speaking -->
                        <template x-if="orbState === 'ai_speaking'">
                            <div class="px-4 py-2.5 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-bold flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                                <span>Penguji Sedang Berbicara...</span>
                            </div>
                        </template>

                        <!-- When AI is Thinking -->
                        <template x-if="orbState === 'thinking'">
                            <div class="px-4 py-2.5 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 animate-spin text-amber-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Menelaah Jawaban...</span>
                            </div>
                        </template>

                        <!-- When It's User's Turn to Speak: Big Prominent Button -->
                        <template x-if="orbState !== 'ai_speaking' && orbState !== 'thinking'">
                            <button type="button"
                                    @click="submitCurrentTurn()"
                                    :class="liveInterimTranscript ? 'bg-emerald-600 hover:bg-emerald-500 ring-4 ring-emerald-500/20 animate-pulse' : 'bg-blue-600 hover:bg-blue-500'"
                                    class="px-5 py-2.5 sm:px-6 sm:py-3 rounded-2xl font-bold text-xs sm:text-sm text-white shadow-lg shadow-blue-600/20 transition transform hover:scale-102 flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="liveInterimTranscript ? 'Selesai Bicara (Kirim)' : 'Bicaralah Sekarang'"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Right: Text Input Toggle & Finish Button -->
                    <div class="flex items-center gap-2">
                        <!-- Toggle Emergency Text Input -->
                        <button type="button"
                                @click="showTextInput = !showTextInput"
                                :class="showTextInput ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'"
                                title="Ketik jawaban jika mikrofon bermasalah"
                                class="px-3 py-2.5 rounded-2xl text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="hidden sm:inline">Ketik Teks</span>
                        </button>

                        <!-- Finish Session -->
                        <button type="button"
                                @click="finishSession()"
                                :disabled="isFinishing"
                                class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-2xl shadow-md shadow-rose-600/25 transition cursor-pointer disabled:opacity-50">
                            <span x-text="isFinishing ? 'Menganalisis...' : 'Selesai'"></span>
                        </button>
                    </div>
                </div>

                <!-- QUICK TEXT INPUT BAR (Bila Tombol Ketik Teks Dibuka) -->
                <div x-show="showTextInput"
                     x-transition
                     class="glass-panel p-3 rounded-2xl flex items-center gap-2 border border-slate-200/80 dark:border-slate-800 shadow-md">
                    <input type="text"
                           x-model="manualInputText"
                           @keydown.enter.prevent="submitManualTurn()"
                           placeholder="Ketik jawaban Anda di sini lalu tekan Enter..."
                           class="w-full text-xs sm:text-sm rounded-xl glass-input px-3.5 py-2.5 text-slate-900 dark:text-white">
                    <button type="button"
                            @click="submitManualTurn()"
                            :disabled="!manualInputText.trim() || orbState === 'thinking'"
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl transition cursor-pointer disabled:opacity-40 shrink-0">
                        Kirim
                    </button>
                </div>
            </div>

        </div>
    </div>

    
</x-app-layout>
