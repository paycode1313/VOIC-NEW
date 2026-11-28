/**
 * Optional camera for the exam room: eye contact, face visibility and a rough expression,
 * measured on this device with MediaPipe (no video leaves the browser). Mixed into the
 * examRoom() Alpine component, so `this` is the room: it reads isListening, t() and $refs.video
 * and writes cameraOn, faceSamples and expression.
 */

const MEDIAPIPE_VERSION = '0.10.14';
const LOCAL_MEDIAPIPE = '/vendor/mediapipe';

// Kept outside Alpine's reactive state: through Alpine's proxy MediaPipe throws "Task is not initialized
// with video mode" on every frame, so eye contact was never measured. One exam room per page.
let faceLandmarker = null;

export const cameraMethods = {
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
        if (faceLandmarker) return this.startFaceLoop();
        try {
            const { base, model } = await this.mediapipeBase();
            const vision = await import(/* @vite-ignore */ `${base}/vision_bundle.mjs`);
            const fileset = await vision.FilesetResolver.forVisionTasks(`${base}/wasm`);
            faceLandmarker = await vision.FaceLandmarker.createFromOptions(fileset, {
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
            if (!faceLandmarker || !video || video.readyState < 2) return;
            if (!this.isListening) return;
            const result = faceLandmarker.detectForVideo(video, performance.now());
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
};
