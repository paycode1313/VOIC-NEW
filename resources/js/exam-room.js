/**
 * Alpine component for the three-part mock exam room.
 *
 * Flow per turn: examiner speaks -> candidate answers (speech or typing)
 * -> answer is POSTed -> examiner reply comes back -> repeat. Part 2 adds a
 * preparation countdown and a timed long turn that is never cut off by
 * silence detection.
 */

const SILENCE_MS = 2600;
const MEDIAPIPE_VERSION = '0.10.14';

export default function examRoom(config) {
    return {
        config,
        state: config.initialState,
        transcript: config.transcript || [],
        status: 'ready', // ready | speaking | listening | thinking | prep | long_turn | finishing | error
        started: false,
        subtitle: config.initialState?.examiner_message?.text || '',
        interim: '',
        finalText: '',
        typed: '',
        typingMode: false,
        speechSupported: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
        recognition: null,
        silenceTimer: null,
        answerStartedAt: null,
        sessionStartedAt: Date.now(),
        countdown: 0,
        countdownTimer: null,
        notes: '',
        errorMessage: '',
        micLevel: 0,
        micReady: false,
        micStream: null,
        audioContext: null,
        cameraOn: false,
        cameraStream: null,
        faceLandmarker: null,
        faceSamples: { total: 0, visible: 0, looking: 0 },
        faceLoop: null,
        currentAudio: null,
        engine: null,

        get isEn() {
            return this.config.language === 'en';
        },

        get cueCard() {
            return this.state.cue_card;
        },

        get liveAnswer() {
            return (this.finalText + ' ' + this.interim).trim();
        },

        get countdownLabel() {
            const m = Math.floor(this.countdown / 60);
            const s = this.countdown % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },

        init() {
            this.typingMode = !this.speechSupported;
            this.checkEngine();
            this.$watch('transcript', () => this.$nextTick(() => this.scrollTranscript()));
            window.addEventListener('beforeunload', () => this.teardown());
        },

        t(id, en) {
            return this.isEn ? en : id;
        },

        async checkEngine() {
            try {
                const response = await fetch(this.config.statusUrl, { headers: { Accept: 'application/json' } });
                if (response.ok) this.engine = (await response.json()).engine;
            } catch (e) { /* the badge simply stays hidden */ }
        },

        // ----- Start & mic check -------------------------------------------

        async checkMic() {
            try {
                this.micStream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.audioContext.createMediaStreamSource(this.micStream);
                const analyser = this.audioContext.createAnalyser();
                analyser.fftSize = 512;
                source.connect(analyser);
                const data = new Uint8Array(analyser.frequencyBinCount);
                const tick = () => {
                    if (!this.micStream) return;
                    analyser.getByteTimeDomainData(data);
                    let peak = 0;
                    for (let i = 0; i < data.length; i++) peak = Math.max(peak, Math.abs(data[i] - 128));
                    this.micLevel = Math.min(100, Math.round((peak / 128) * 160));
                    requestAnimationFrame(tick);
                };
                tick();
                this.micReady = true;
            } catch (e) {
                this.micReady = false;
                this.typingMode = true;
                this.errorMessage = this.t('Mikrofon tidak bisa diakses. Kamu tetap bisa menjawab dengan mengetik.', 'Microphone unavailable. You can still type your answers.');
            }
        },

        async begin() {
            this.started = true;
            this.sessionStartedAt = Date.now();
            // Unlock speech synthesis on the user gesture (required by Safari/Chrome).
            if (window.speechSynthesis) window.speechSynthesis.speak(new SpeechSynthesisUtterance(''));
            await this.playExaminer(this.state.examiner_message);
            this.afterExaminerTurn();
        },

        // ----- Examiner voice ----------------------------------------------

        async playExaminer(message) {
            if (!message?.text) return;
            this.status = 'speaking';
            this.subtitle = message.text;
            try {
                if (message.audio_url) {
                    await this.playAudio(message.audio_url);
                } else {
                    await this.speakBrowser(message.text);
                }
            } catch (e) {
                await this.speakBrowser(message.text).catch(() => {});
            }
        },

        playAudio(url) {
            return new Promise((resolve, reject) => {
                const audio = new Audio(url);
                this.currentAudio = audio;
                audio.onended = () => resolve();
                audio.onerror = () => reject(new Error('audio failed'));
                audio.play().catch(reject);
            });
        },

        speakBrowser(text) {
            return new Promise((resolve) => {
                if (!window.speechSynthesis) return resolve();
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                const lang = this.isEn ? 'en-US' : 'id-ID';
                utterance.lang = lang;
                utterance.rate = this.isEn ? 1.0 : 1.02;
                const voices = window.speechSynthesis.getVoices();
                const voice = voices.find((v) => v.lang === lang && /natural|google|online/i.test(v.name)) || voices.find((v) => v.lang.startsWith(lang.slice(0, 2)));
                if (voice) utterance.voice = voice;
                // Chrome sometimes never fires onend; guard with a length-based timeout.
                const guard = setTimeout(resolve, Math.max(4000, text.length * 90));
                utterance.onend = () => { clearTimeout(guard); resolve(); };
                utterance.onerror = () => { clearTimeout(guard); resolve(); };
                window.speechSynthesis.speak(utterance);
            });
        },

        stopVoice() {
            if (this.currentAudio) { this.currentAudio.pause(); this.currentAudio = null; }
            if (window.speechSynthesis) window.speechSynthesis.cancel();
        },

        async repeatQuestion() {
            if (this.status === 'listening') this.stopListening(false);
            await this.playExaminer({ text: this.subtitle });
            this.afterExaminerTurn();
        },

        afterExaminerTurn() {
            if (this.state.completed) return this.finish();
            if (this.state.phase === 'cue_card') return this.startPrep();
            this.startListening();
        },

        // ----- Listening ----------------------------------------------------

        startListening(longTurn = false) {
            this.finalText = '';
            this.interim = '';
            this.typed = '';
            this.answerStartedAt = Date.now();
            this.faceSamples = { total: 0, visible: 0, looking: 0 };
            this.status = longTurn ? 'long_turn' : 'listening';
            if (this.typingMode) return;

            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new Recognition();
            recognition.lang = this.isEn ? 'en-US' : 'id-ID';
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.onresult = (event) => {
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const piece = event.results[i][0].transcript;
                    if (event.results[i].isFinal) this.finalText = (this.finalText + ' ' + piece).trim();
                    else interim += piece;
                }
                this.interim = interim;
                this.armSilenceTimer();
            };
            recognition.onerror = (event) => {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    this.typingMode = true;
                    this.errorMessage = this.t('Izin mikrofon ditolak. Silakan ketik jawabanmu.', 'Microphone permission denied. Please type your answer.');
                }
            };
            // Chrome ends recognition on its own after a while; restart while we still listen.
            recognition.onend = () => {
                if ((this.status === 'listening' || this.status === 'long_turn') && this.recognition === recognition) {
                    try { recognition.start(); } catch (e) { /* already started */ }
                }
            };
            this.recognition = recognition;
            try { recognition.start(); } catch (e) { /* ignore */ }
        },

        armSilenceTimer() {
            clearTimeout(this.silenceTimer);
            if (this.status !== 'listening') return; // never auto-stop the Part 2 long turn
            this.silenceTimer = setTimeout(() => {
                if (this.liveAnswer.split(/\s+/).length >= 3) this.submitAnswer();
            }, SILENCE_MS);
        },

        stopListening(clear = true) {
            clearTimeout(this.silenceTimer);
            const recognition = this.recognition;
            this.recognition = null;
            if (recognition) { try { recognition.stop(); } catch (e) { /* ignore */ } }
            if (clear) { this.interim = ''; }
        },

        // ----- Part 2 cue card ---------------------------------------------

        startPrep() {
            this.status = 'prep';
            this.notes = '';
            this.runCountdown(this.cueCard?.prep_seconds || 60, () => this.startLongTurn());
        },

        startLongTurn() {
            this.clearCountdown();
            this.startListening(true);
            this.runCountdown(this.cueCard?.talk_seconds || 120, () => this.submitAnswer());
        },

        runCountdown(seconds, done) {
            this.clearCountdown();
            this.countdown = seconds;
            this.countdownTimer = setInterval(() => {
                this.countdown -= 1;
                if (this.countdown <= 0) { this.clearCountdown(); done(); }
            }, 1000);
        },

        clearCountdown() {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        },

        // ----- Submitting ---------------------------------------------------

        async submitAnswer() {
            if (this.status !== 'listening' && this.status !== 'long_turn') return;
            this.clearCountdown();
            this.stopListening();
            const answer = (this.typingMode ? this.typed : this.liveAnswer).trim();
            const durationSeconds = Math.round((Date.now() - (this.answerStartedAt || Date.now())) / 1000);
            this.status = 'thinking';
            const last = this.transcript[this.transcript.length - 1];
            if (answer && !(last && last.sender === 'user' && last.message === answer)) {
                this.transcript.push({ id: 'u' + Date.now(), sender: 'user', message: answer, exam_part: this.state.part });
            }

            try {
                const response = await this.post(this.config.answerUrl, {
                    answer,
                    duration_seconds: Math.min(900, durationSeconds),
                    timestamp_seconds: Math.round((Date.now() - this.sessionStartedAt) / 1000),
                    facial: this.facialSummary(),
                });
                this.state = response;
                const message = response.examiner_message;
                if (message?.engine) this.engine = message.engine;
                if (message) this.transcript.push({ id: message.id, sender: 'ai', message: message.text, exam_part: response.part });
                this.finalText = '';
                this.typed = '';
                await this.playExaminer(message);
                this.afterExaminerTurn();
            } catch (e) {
                this.status = 'error';
                this.errorMessage = this.t('Koneksi terputus. Coba kirim ulang jawabanmu.', 'Connection lost. Please try sending again.');
                this.typed = answer;
                this.typingMode = true;
            }
        },

        retry() {
            this.errorMessage = '';
            this.status = 'listening';
            this.answerStartedAt = Date.now();
            this.submitAnswer();
        },

        async finish() {
            if (this.status === 'finishing') return;
            this.stopListening();
            this.clearCountdown();
            this.stopVoice();
            this.status = 'finishing';
            try {
                const response = await this.post(this.config.finishUrl, {
                    duration_seconds: Math.round((Date.now() - this.sessionStartedAt) / 1000),
                });
                this.teardown();
                window.location.href = response.redirect_url;
            } catch (e) {
                this.status = 'error';
                this.errorMessage = this.t('Gagal membuat laporan. Coba lagi.', 'Could not build the report. Try again.');
            }
        },

        async post(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        },

        // ----- Optional camera: eye contact --------------------------------

        async toggleCamera() {
            if (this.cameraOn) return this.stopCamera();
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
                this.cameraOn = true;
                await this.$nextTick();
                this.$refs.video.srcObject = this.cameraStream;
                await this.$refs.video.play();
                this.loadFaceModel();
            } catch (e) {
                this.cameraOn = false;
                this.errorMessage = this.t('Kamera tidak bisa diakses.', 'Camera unavailable.');
            }
        },

        stopCamera() {
            clearInterval(this.faceLoop);
            this.cameraStream?.getTracks().forEach((track) => track.stop());
            this.cameraStream = null;
            this.cameraOn = false;
        },

        async loadFaceModel() {
            if (this.faceLandmarker) return this.startFaceLoop();
            try {
                const base = `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${MEDIAPIPE_VERSION}`;
                const vision = await import(/* @vite-ignore */ `${base}/vision_bundle.mjs`);
                const fileset = await vision.FilesetResolver.forVisionTasks(`${base}/wasm`);
                this.faceLandmarker = await vision.FaceLandmarker.createFromOptions(fileset, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
                        delegate: 'GPU',
                    },
                    runningMode: 'VIDEO',
                    numFaces: 1,
                    outputFacialTransformationMatrixes: true,
                });
                this.startFaceLoop();
            } catch (e) {
                console.warn('Face model unavailable', e);
            }
        },

        startFaceLoop() {
            clearInterval(this.faceLoop);
            this.faceLoop = setInterval(() => {
                const video = this.$refs.video;
                if (!this.faceLandmarker || !video || video.readyState < 2) return;
                if (this.status !== 'listening' && this.status !== 'long_turn') return;
                const result = this.faceLandmarker.detectForVideo(video, performance.now());
                this.faceSamples.total += 1;
                const matrix = result.facialTransformationMatrixes?.[0]?.data;
                if (!matrix) return;
                this.faceSamples.visible += 1;
                // Column-major 4x4: derive yaw and pitch of the head in degrees.
                const yaw = Math.atan2(-matrix[2], Math.hypot(matrix[0], matrix[1])) * (180 / Math.PI);
                const pitch = Math.atan2(matrix[6], matrix[10]) * (180 / Math.PI);
                if (Math.abs(yaw) < 16 && Math.abs(pitch) < 16) this.faceSamples.looking += 1;
            }, 350);
        },

        facialSummary() {
            const { total, visible, looking } = this.faceSamples;
            if (!this.cameraOn || total < 3) return null;
            return {
                eye_contact: Math.round((looking / total) * 100),
                face_visible: Math.round((visible / total) * 100),
                samples: total,
            };
        },

        // ----- Misc ---------------------------------------------------------

        scrollTranscript() {
            const el = this.$refs.transcript;
            if (el) el.scrollTop = el.scrollHeight;
        },

        teardown() {
            this.stopListening();
            this.clearCountdown();
            this.stopVoice();
            this.stopCamera();
            this.micStream?.getTracks().forEach((track) => track.stop());
            this.micStream = null;
            this.audioContext?.close().catch(() => {});
        },
    };
}
