/**
 * Alpine component for the three-part mock exam room.
 *
 * Turn loop, tuned to feel like a live conversation:
 *   examiner speaks (sentence by sentence, streamed) -> candidate answers
 *   -> end of speech detected locally (~1s of silence) -> recording is sent
 *   once -> local Whisper transcribes -> the model streams the next turn and
 *   the first sentence is already playing while the rest is generated.
 *
 * Voice input modes, chosen automatically:
 *   local   : MediaRecorder + on-device voice activity detection, transcribed
 *             by faster-whisper on the AI laptop (fully offline)
 *   browser : Web Speech API (needs internet in Chrome/Edge)
 *   typing  : keyboard fallback
 *
 * Barge-in (optional, needs a headset): speaking while the examiner talks
 * stops the examiner and starts listening, like a real conversation.
 */

import TalkingAvatar from './avatar';

const MEDIAPIPE_VERSION = '0.10.14';
const LOCAL_MEDIAPIPE = '/vendor/mediapipe';
const END_SILENCE_MS = 1100; // pause that ends a normal answer
const END_SILENCE_SHORT_MS = 1700; // more patience while the answer is still very short
const MIN_SPEECH_MS = 250; // ignore clicks and coughs
const BARGE_IN_MS = 350;
const MAX_ANSWER_MS = 90000;

export default function examRoom(config) {
    // Kept outside Alpine's reactive state: canvas and Web Audio objects must not be proxied.
    let avatar = null;
    let playbackContext = null;
    let voiceAnalyser = null;

    const moodFor = (status) => ({
        speaking: 'speaking',
        listening: 'listening',
        long_turn: 'listening',
        thinking: 'thinking',
        finishing: 'thinking',
    }[status] ?? 'idle');

    return {
        config,
        avatarReady: false,
        state: config.initialState,
        transcript: config.transcript || [],
        status: 'ready', // ready | speaking | listening | thinking | prep | long_turn | finishing | error
        started: false,
        subtitle: config.initialState?.examiner_message?.text || '',
        lastTurn: [],
        interim: '',
        finalText: '',
        typed: '',
        voiceMode: 'typing', // local | browser | typing
        speechSupported: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
        recognition: null,
        silenceTimer: null,
        answerStartedAt: null,
        sessionStartedAt: Date.now(),
        countdown: 0,
        countdownTimer: null,
        notes: '',
        errorMessage: '',
        hint: '',
        micLevel: 0,
        micReady: false,
        micStream: null,
        audioContext: null,
        analyser: null,
        noiseFloor: 0.01,
        recorder: null,
        chunks: [],
        vad: { speaking: false, speechMs: 0, silenceMs: 0, aboveMs: 0, lastTick: 0 },
        recordSeconds: 0,
        bargeIn: false,
        cameraOn: false,
        cameraStream: null,
        faceLandmarker: null,
        faceSamples: { total: 0, visible: 0, looking: 0, smiling: 0, tense: 0 },
        faceLoop: null,
        // Live label under the camera: 'relaxed' | 'smiling' | 'tense' | 'away' | null
        expression: null,
        currentAudio: null,
        playToken: 0,
        engine: null,
        aiStatus: null,
        firstSentenceMs: null,
        coachRemaining: config.initialState?.remaining_seconds ?? null,
        coachTimer: null,
        timeUpPending: false,

        get isEn() {
            return this.config.language === 'en';
        },

        get typingMode() {
            return this.voiceMode === 'typing';
        },

        get cueCard() {
            return this.state.cue_card;
        },

        get isCoach() {
            return this.state.session_type === 'coach';
        },

        get coachClock() {
            const left = Math.max(0, this.coachRemaining ?? 0);
            const m = Math.floor(left / 60);
            const s = left % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },

        get isListening() {
            return this.status === 'listening' || this.status === 'long_turn';
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
            this.voiceMode = this.speechSupported ? 'browser' : 'typing';
            this.checkEngine();
            try { this.bargeIn = localStorage.getItem('voic_barge_in') === '1'; } catch (e) { /* ignore */ }
            this.$watch('bargeIn', (value) => { try { localStorage.setItem('voic_barge_in', value ? '1' : '0'); } catch (e) { /* ignore */ } });
            this.$watch('transcript', () => this.$nextTick(() => this.scrollTranscript()));
            this.$watch('status', (status) => avatar?.setMood(moodFor(status)));
            this.$watch('vad.speaking', (speaking) => avatar?.setUserTalking(Boolean(speaking)));
            this.$nextTick(() => this.startAvatar());
            window.addEventListener('beforeunload', () => this.teardown());
        },

        // ----- Live avatar ---------------------------------------------------

        startAvatar() {
            const rig = this.config.avatar?.rig;
            const canvas = this.$refs.avatarCanvas;
            if (!rig || !canvas || !canvas.getContext) return;
            try {
                avatar = new TalkingAvatar(canvas, this.config.avatar.image, rig);
                avatar.image.addEventListener('load', () => { this.avatarReady = true; }, { once: true });
            } catch (e) {
                avatar = null;
            }
        },

        /**
         * Route the examiner's voice through an analyser so the avatar's mouth follows it.
         * Must be created on a user gesture (the start button).
         */
        startVoiceAnalyser() {
            if (playbackContext || !avatar) return;
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                playbackContext = new AudioCtx();
                voiceAnalyser = playbackContext.createAnalyser();
                voiceAnalyser.fftSize = 1024;
                voiceAnalyser.smoothingTimeConstant = 0.5;
                voiceAnalyser.connect(playbackContext.destination);
                playbackContext.resume?.();
                avatar.useAnalyser(voiceAnalyser);
            } catch (e) {
                playbackContext = null;
                voiceAnalyser = null;
            }
        },

        /**
         * Connect one audio element to the analyser. Only same-origin or data: audio: a
         * cross-origin source would be silenced by the browser once routed through Web Audio.
         */
        connectVoice(audio, url) {
            let routable = false;
            try {
                routable = url.startsWith('data:') || new URL(url, window.location.href).origin === window.location.origin;
            } catch (e) { /* not routable */ }

            if (routable && playbackContext?.state === 'running' && voiceAnalyser) {
                try {
                    playbackContext.createMediaElementSource(audio).connect(voiceAnalyser);
                    avatar?.simulate(false);
                    return;
                } catch (e) { /* fall through to simulated lips */ }
            }
            avatar?.simulate(true);
        },

        t(id, en) {
            return this.isEn ? en : id;
        },

        async checkEngine() {
            try {
                const response = await fetch(this.config.statusUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                this.aiStatus = await response.json();
                this.engine = this.aiStatus.engine;
                // Prefer fully local speech recognition when the AI laptop has Whisper ready.
                // Speed mode "online": the browser's own recognition is instant, local Whisper takes seconds on a CPU.
                const browserFirst = this.aiStatus.prefer_browser_stt && this.speechSupported;
                if (this.aiStatus.stt_local && window.MediaRecorder && !browserFirst) this.voiceMode = 'local';
            } catch (e) { /* badges stay hidden */ }
        },

        // ----- Microphone & voice activity detection ----------------------

        async ensureMic() {
            if (this.micStream) return true;
            try {
                this.micStream = await navigator.mediaDevices.getUserMedia({
                    audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
                });
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.audioContext.createMediaStreamSource(this.micStream);
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 1024;
                source.connect(this.analyser);
                this.micReady = true;
                this.calibrate();
                this.vadLoop();
                return true;
            } catch (e) {
                this.micReady = false;
                this.voiceMode = 'typing';
                this.errorMessage = this.t('Mikrofon tidak bisa diakses. Kamu tetap bisa menjawab dengan mengetik.', 'Microphone unavailable. You can still type your answers.');
                return false;
            }
        },

        async checkMic() {
            await this.ensureMic();
        },

        rms() {
            if (!this.analyser) return 0;
            const data = new Float32Array(this.analyser.fftSize);
            this.analyser.getFloatTimeDomainData(data);
            let sum = 0;
            for (let i = 0; i < data.length; i++) sum += data[i] * data[i];
            return Math.sqrt(sum / data.length);
        },

        calibrate() {
            // Measure room noise for half a second so the threshold fits the booth.
            const samples = [];
            const started = performance.now();
            const sample = () => {
                samples.push(this.rms());
                if (performance.now() - started < 500) return requestAnimationFrame(sample);
                samples.sort((a, b) => a - b);
                this.noiseFloor = Math.max(0.004, samples[Math.floor(samples.length * 0.6)] || 0.01);
            };
            sample();
        },

        get speechThreshold() {
            return Math.max(this.noiseFloor * 2.8, 0.018);
        },

        vadLoop() {
            const tick = (now) => {
                if (!this.micStream) return;
                const level = this.rms();
                this.micLevel = Math.min(100, Math.round((level / 0.12) * 100));
                const delta = this.vad.lastTick ? now - this.vad.lastTick : 16;
                this.vad.lastTick = now;
                const loud = level > this.speechThreshold;

                if (this.status === 'speaking' && this.bargeIn) {
                    // Interrupting needs to be clearly louder than the examiner's echo.
                    this.vad.aboveMs = level > this.speechThreshold * 1.8 ? this.vad.aboveMs + delta : 0;
                    if (this.vad.aboveMs > BARGE_IN_MS) this.interrupt();
                } else if (this.voiceMode === 'local' && this.isListening && this.recorder) {
                    this.trackSpeech(loud, delta);
                }

                requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        },

        trackSpeech(loud, delta) {
            const vad = this.vad;
            if (loud) {
                vad.speechMs += delta;
                vad.silenceMs = 0;
                if (vad.speechMs > MIN_SPEECH_MS) vad.speaking = true;
            } else {
                vad.silenceMs += delta;
            }

            this.recordSeconds = Math.round((Date.now() - this.answerStartedAt) / 1000);

            if (this.status === 'long_turn') return; // Part 2: only the timer or the button ends it

            const patience = vad.speechMs < 1500 ? END_SILENCE_SHORT_MS : END_SILENCE_MS;
            if (vad.speaking && vad.silenceMs > patience) {
                this.submitAnswer();
            } else if (Date.now() - this.answerStartedAt > MAX_ANSWER_MS) {
                this.submitAnswer();
            }
        },

        async begin() {
            this.started = true;
            this.sessionStartedAt = Date.now();
            // Unlock audio + speech synthesis on the user gesture (required by Safari/Chrome).
            if (window.speechSynthesis) window.speechSynthesis.speak(new SpeechSynthesisUtterance(''));
            this.startVoiceAnalyser();
            if (this.voiceMode !== 'typing') await this.ensureMic();
            if (this.isCoach) this.startCoachTimer();
            const opening = this.state.examiner_message;
            await this.playSentences([{ text: opening?.text || '', audio_url: opening?.audio_url || null }]);
            this.afterExaminerTurn();
        },

        // ----- Examiner voice: a queue of sentences -----------------------

        async playSentences(sentences) {
            this.lastTurn = sentences;
            this.status = 'speaking';
            this.subtitle = sentences.map((s) => s.text).join(' ');
            const token = ++this.playToken;
            for (const sentence of sentences) {
                if (token !== this.playToken) return false;
                await this.playOne(sentence);
            }
            return token === this.playToken;
        },

        async playOne(sentence) {
            if (!sentence.text) return;
            try {
                if (sentence.audio_url) {
                    await this.playAudio(sentence.audio_url);
                    return;
                }
            } catch (e) { /* fall back to the browser voice */ }
            await this.speakBrowser(sentence.text);
        },

        playAudio(url) {
            return new Promise((resolve, reject) => {
                const audio = new Audio(url);
                this.currentAudio = audio;
                this.connectVoice(audio, url);
                audio.onended = () => resolve();
                audio.onpause = () => resolve();
                audio.onerror = () => reject(new Error('audio failed'));
                audio.play().catch(reject);
            });
        },

        speakBrowser(text) {
            return new Promise((resolve) => {
                if (!window.speechSynthesis) return resolve();
                const utterance = new SpeechSynthesisUtterance(text);
                const lang = this.isEn ? 'en-US' : 'id-ID';
                utterance.lang = lang;
                utterance.rate = this.isEn ? 1.0 : 1.04;
                const voices = window.speechSynthesis.getVoices();
                const voice = voices.find((v) => v.lang === lang && /natural|google|online/i.test(v.name)) || voices.find((v) => v.lang.startsWith(lang.slice(0, 2)));
                if (voice) utterance.voice = voice;
                // Chrome sometimes never fires onend; guard with a length-based timeout.
                // The browser voice exposes no audio signal: move the lips on a speech-like rhythm.
                avatar?.simulate(true);
                const done = () => { clearTimeout(guard); avatar?.simulate(false); resolve(); };
                const guard = setTimeout(done, Math.max(3000, text.length * 85));
                utterance.onend = done;
                utterance.onerror = done;
                window.speechSynthesis.speak(utterance);
            });
        },

        stopVoice() {
            this.playToken++;
            if (this.currentAudio) { this.currentAudio.pause(); this.currentAudio = null; }
            if (window.speechSynthesis) window.speechSynthesis.cancel();
        },

        interrupt() {
            this.vad.aboveMs = 0;
            this.stopVoice();
            this.hint = this.t('Silakan, saya dengarkan.', 'Go ahead, I am listening.');
            this.afterExaminerTurn();
        },

        async repeatQuestion() {
            if (this.isListening) this.stopListening(true);
            await this.playSentences(this.lastTurn.length ? this.lastTurn : [{ text: this.subtitle, audio_url: null }]);
            this.afterExaminerTurn();
        },

        // ----- Coaching timer -----------------------------------------------

        startCoachTimer() {
            clearInterval(this.coachTimer);
            this.coachTimer = setInterval(() => {
                if (this.coachRemaining === null) return;
                this.coachRemaining = Math.max(0, this.coachRemaining - 1);
                if (this.coachRemaining === 0) {
                    clearInterval(this.coachTimer);
                    this.onCoachTimeUp();
                }
            }, 1000);
        },

        onCoachTimeUp() {
            if (this.state.completed) return;
            const silent = this.status === 'listening' && !this.liveAnswer.trim() && !this.typed.trim() && !(this.voiceMode === 'local' && this.vad.speaking);
            // Mid-answer: let them finish; the server closes on that turn. Otherwise close now.
            if (silent) return this.sendTimeUp();
            this.timeUpPending = true;
        },

        async sendTimeUp() {
            this.timeUpPending = false;
            this.stopListening(true);
            this.status = 'thinking';
            const form = new FormData();
            form.append('time_up', '1');
            form.append('duration_seconds', '0');
            form.append('timestamp_seconds', Math.round((Date.now() - this.sessionStartedAt) / 1000));
            await this.streamTurn(form, '');
        },

        afterExaminerTurn() {
            if (this.state.completed) return this.finish();
            if (this.isCoach && (this.timeUpPending || this.coachRemaining === 0)) return this.sendTimeUp();
            if (this.state.phase === 'cue_card') return this.startPrep();
            this.startListening();
        },

        // ----- Listening -----------------------------------------------------

        startListening(longTurn = false) {
            this.finalText = '';
            this.interim = '';
            this.typed = '';
            this.recordSeconds = 0;
            this.answerStartedAt = Date.now();
            this.faceSamples = { total: 0, visible: 0, looking: 0, smiling: 0, tense: 0 };
            this.vad = { speaking: false, speechMs: 0, silenceMs: 0, aboveMs: 0, lastTick: 0 };
            this.status = longTurn ? 'long_turn' : 'listening';

            if (this.voiceMode === 'local') return this.startRecording();
            if (this.voiceMode === 'browser') return this.startRecognition();
        },

        startRecording() {
            if (!this.micStream) return;
            const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'].find((type) => window.MediaRecorder?.isTypeSupported?.(type));
            this.chunks = [];
            this.recorder = new MediaRecorder(this.micStream, mime ? { mimeType: mime, audioBitsPerSecond: 32000 } : undefined);
            this.recorder.ondataavailable = (event) => { if (event.data.size) this.chunks.push(event.data); };
            this.recorder.start(250);
        },

        stopRecording() {
            return new Promise((resolve) => {
                const recorder = this.recorder;
                this.recorder = null;
                if (!recorder || recorder.state === 'inactive') return resolve(null);
                recorder.onstop = () => resolve(new Blob(this.chunks, { type: recorder.mimeType || 'audio/webm' }));
                recorder.stop();
            });
        },

        startRecognition() {
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
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed' || event.error === 'network') {
                    this.voiceMode = 'typing';
                    this.errorMessage = event.error === 'network'
                        ? this.t('Pengenal suara browser butuh internet. Silakan ketik, atau aktifkan Whisper lokal.', 'Browser speech recognition needs internet. Type instead, or enable local Whisper.')
                        : this.t('Izin mikrofon ditolak. Silakan ketik jawabanmu.', 'Microphone permission denied. Please type your answer.');
                }
            };
            recognition.onend = () => {
                if (this.isListening && this.recognition === recognition) {
                    try { recognition.start(); } catch (e) { /* already started */ }
                }
            };
            this.recognition = recognition;
            try { recognition.start(); } catch (e) { /* ignore */ }
        },

        armSilenceTimer() {
            clearTimeout(this.silenceTimer);
            if (this.status !== 'listening') return;
            this.silenceTimer = setTimeout(() => {
                if (this.liveAnswer.split(/\s+/).length >= 2) this.submitAnswer();
            }, 1400);
        },

        stopListening(discard = false) {
            clearTimeout(this.silenceTimer);
            const recognition = this.recognition;
            this.recognition = null;
            if (recognition) { try { recognition.stop(); } catch (e) { /* ignore */ } }
            if (discard && this.recorder) { this.stopRecording(); }
            this.interim = '';
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

        // ----- Submitting & streaming the examiner reply ------------------

        async submitAnswer() {
            if (!this.isListening) return;
            this.clearCountdown();
            const durationSeconds = Math.min(900, Math.round((Date.now() - (this.answerStartedAt || Date.now())) / 1000));
            const form = new FormData();
            form.append('duration_seconds', durationSeconds);
            form.append('timestamp_seconds', Math.round((Date.now() - this.sessionStartedAt) / 1000));
            const facial = this.facialSummary();
            if (facial) Object.entries(facial).forEach(([key, value]) => form.append(`facial[${key}]`, value));

            let pendingText = '';
            const wasLongTurn = this.status === 'long_turn';
            if (this.voiceMode === 'local') {
                this.status = 'thinking';
                const blob = await this.stopRecording();
                if (!blob || blob.size < 2000 || !this.vad.speaking) {
                    // Nothing was said: keep listening instead of sending silence.
                    this.hint = this.t('Belum terdengar suara, silakan bicara.', 'I did not hear anything yet, go ahead.');
                    return wasLongTurn ? this.startLongTurn() : this.startListening();
                }
                form.append('audio', blob, 'answer.webm');
            } else {
                this.stopListening();
                pendingText = (this.typingMode ? this.typed : this.liveAnswer).trim();
                if (!pendingText) return;
                form.append('answer', pendingText);
                this.status = 'thinking';
                this.pushUser(pendingText);
            }

            this.hint = '';
            await this.streamTurn(form, pendingText);
        },

        pushUser(text) {
            const last = this.transcript[this.transcript.length - 1];
            if (text && !(last && last.sender === 'user' && last.message === text)) {
                this.transcript.push({ id: 'u' + Date.now(), sender: 'user', message: text, exam_part: this.state.part });
            }
        },

        async streamTurn(form, typedText) {
            const sentRequestAt = performance.now();
            const queue = [];
            let done = null;
            let finished = false;
            let wake = null;
            const notify = () => { if (wake) { wake(); wake = null; } };

            // Play sentences as they arrive, while the rest of the reply is still streaming.
            const player = (async () => {
                const token = ++this.playToken;
                const spoken = [];
                let index = 0;
                while (token === this.playToken) {
                    if (index < queue.length) {
                        const sentence = queue[index++];
                        spoken.push(sentence);
                        this.status = 'speaking';
                        this.subtitle = spoken.map((s) => s.text).join(' ');
                        if (this.firstSentenceMs === null) this.firstSentenceMs = Math.round(performance.now() - sentRequestAt);
                        await this.playOne(sentence);
                    } else if (finished) {
                        break;
                    } else {
                        await new Promise((resolve) => { wake = resolve; });
                    }
                }
                this.lastTurn = spoken;
                return token === this.playToken;
            })();

            try {
                const response = await fetch(this.config.streamUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/x-ndjson', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: form,
                });
                if (!response.ok || !response.body) throw new Error('HTTP ' + response.status);

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let aiLine = null;
                for (;;) {
                    const { value, done: streamDone } = await reader.read();
                    if (streamDone) break;
                    buffer += decoder.decode(value, { stream: true });
                    let newline;
                    while ((newline = buffer.indexOf('\n')) >= 0) {
                        const line = buffer.slice(0, newline).trim();
                        buffer = buffer.slice(newline + 1);
                        if (!line) continue;
                        const event = JSON.parse(line);
                        if (event.type === 'transcript') {
                            this.pushUser(event.text);
                        } else if (event.type === 'sentence') {
                            queue.push({ text: event.text, audio_url: event.audio_url });
                            if (aiLine === null) {
                                this.transcript.push({ id: 'a' + Date.now(), sender: 'ai', message: '', exam_part: this.state.part });
                                // Keep the reactive proxy, so appending text updates the transcript live.
                                aiLine = this.transcript[this.transcript.length - 1];
                            }
                            aiLine.message = (aiLine.message + ' ' + event.text).trim();
                            notify();
                        } else if (event.type === 'done') {
                            done = event.state;
                        } else if (event.type === 'retry') {
                            this.hint = event.message;
                        } else if (event.type === 'error') {
                            throw new Error(event.code || event.message);
                        }
                    }
                }
            } catch (e) {
                finished = true;
                notify();
                this.status = 'error';
                if (String(e.message).includes('stt_unavailable')) {
                    this.voiceMode = this.speechSupported ? 'browser' : 'typing';
                    this.errorMessage = this.t('Whisper lokal tidak aktif, beralih ke pengenal suara lain. Silakan ulangi jawabanmu.', 'Local Whisper is off, switching recogniser. Please repeat your answer.');
                    this.status = 'listening';
                    return this.startListening();
                }
                this.errorMessage = this.t('Koneksi terputus. Coba kirim ulang jawabanmu.', 'Connection lost. Please try sending again.');
                this.typed = typedText;
                this.voiceMode = 'typing';
                return;
            }

            finished = true;
            notify();

            if (!done) {
                // "retry": nothing usable was heard; listen again without changing the exam.
                return this.startListening(this.state.phase === 'cue_card');
            }

            this.state = done;
            if (typeof done.remaining_seconds === 'number') this.coachRemaining = done.remaining_seconds;
            if (done.examiner_message?.engine) this.engine = done.examiner_message.engine;
            const completedNaturally = await player;
            if (completedNaturally) this.afterExaminerTurn();
        },

        retry() {
            this.errorMessage = '';
            this.status = 'listening';
            this.answerStartedAt = Date.now();
            this.submitAnswer();
        },

        async finish() {
            if (this.status === 'finishing') return;
            this.stopListening(true);
            this.clearCountdown();
            this.stopVoice();
            this.status = 'finishing';
            try {
                const response = await fetch(this.config.finishUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ duration_seconds: Math.round((Date.now() - this.sessionStartedAt) / 1000) }),
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                this.teardown();
                window.location.href = data.redirect_url;
            } catch (e) {
                this.status = 'error';
                this.errorMessage = this.t('Gagal membuat laporan. Coba lagi.', 'Could not build the report. Try again.');
            }
        },

        // ----- Optional camera: eye contact (runs on this device) ---------

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
            this.expression = null;
        },

        async mediapipeBase() {
            // Prefer the copy downloaded by setup_models.py so the booth works offline.
            try {
                const probe = await fetch(`${LOCAL_MEDIAPIPE}/vision_bundle.mjs`, { method: 'HEAD' });
                if (probe.ok) return { base: LOCAL_MEDIAPIPE, model: `${LOCAL_MEDIAPIPE}/face_landmarker.task` };
            } catch (e) { /* use the CDN */ }
            return {
                base: `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${MEDIAPIPE_VERSION}`,
                model: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
            };
        },

        async loadFaceModel() {
            if (this.faceLandmarker) return this.startFaceLoop();
            try {
                const { base, model } = await this.mediapipeBase();
                const vision = await import(/* @vite-ignore */ `${base}/vision_bundle.mjs`);
                const fileset = await vision.FilesetResolver.forVisionTasks(`${base}/wasm`);
                this.faceLandmarker = await vision.FaceLandmarker.createFromOptions(fileset, {
                    baseOptions: { modelAssetPath: model, delegate: 'GPU' },
                    runningMode: 'VIDEO',
                    numFaces: 1,
                    outputFacialTransformationMatrixes: true,
                    outputFaceBlendshapes: true,
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
                if (!this.isListening) return;
                const result = this.faceLandmarker.detectForVideo(video, performance.now());
                this.faceSamples.total += 1;
                const matrix = result.facialTransformationMatrixes?.[0]?.data;
                if (!matrix) {
                    this.expression = 'away';
                    return;
                }
                this.faceSamples.visible += 1;
                // Column-major 4x4: derive yaw and pitch of the head in degrees.
                const yaw = Math.atan2(-matrix[2], Math.hypot(matrix[0], matrix[1])) * (180 / Math.PI);
                const pitch = Math.atan2(matrix[6], matrix[10]) * (180 / Math.PI);
                const looking = Math.abs(yaw) < 16 && Math.abs(pitch) < 16;
                if (looking) this.faceSamples.looking += 1;

                const mood = this.readExpression(result.faceBlendshapes?.[0]?.categories);
                if (mood === 'smiling') this.faceSamples.smiling += 1;
                if (mood === 'tense') this.faceSamples.tense += 1;
                this.expression = looking ? mood : 'away';
            }, 350);
        },

        /**
         * Rough expression from MediaPipe blendshapes (0..1 each): a smile, or tension from
         * lowered brows, pressed lips and a worried inner brow. A heuristic, not an emotion test.
         */
        readExpression(categories) {
            if (!categories?.length) return 'relaxed';
            const score = Object.fromEntries(categories.map((item) => [item.categoryName, item.score]));
            const pair = (name) => ((score[`${name}Left`] ?? 0) + (score[`${name}Right`] ?? 0)) / 2;
            const smile = pair('mouthSmile');
            const tension = 0.45 * pair('browDown') + 0.35 * pair('mouthPress') + 0.2 * (score.browInnerUp ?? 0);
            if (smile > 0.4) return 'smiling';
            if (tension > 0.3) return 'tense';
            return 'relaxed';
        },

        expressionLabel() {
            return {
                relaxed: this.t('Tenang', 'Relaxed'),
                smiling: this.t('Tersenyum', 'Smiling'),
                tense: this.t('Terlihat tegang', 'Looks tense'),
                away: this.t('Tidak menatap kamera', 'Not facing the camera'),
            }[this.expression] ?? '';
        },

        facialSummary() {
            const { total, visible, looking, smiling, tense } = this.faceSamples;
            if (!this.cameraOn || total < 3) return null;
            const share = (count) => (visible > 0 ? Math.round((count / visible) * 100) : 0);
            return {
                eye_contact: Math.round((looking / total) * 100),
                face_visible: Math.round((visible / total) * 100),
                smile: share(smiling),
                tension: share(tense),
                samples: total,
            };
        },

        // ----- Misc ---------------------------------------------------------

        scrollTranscript() {
            const el = this.$refs.transcript;
            if (el) el.scrollTop = el.scrollHeight;
        },

        teardown() {
            clearInterval(this.coachTimer);
            this.stopListening(true);
            this.clearCountdown();
            this.stopVoice();
            this.stopCamera();
            this.micStream?.getTracks().forEach((track) => track.stop());
            this.micStream = null;
            this.audioContext?.close().catch(() => {});
            avatar?.destroy();
            avatar = null;
            playbackContext?.close().catch(() => {});
            playbackContext = null;
        },
    };
}
