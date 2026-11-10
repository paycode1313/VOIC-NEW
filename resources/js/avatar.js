/**
 * Live examiner avatar, drawn on a <canvas> from the examiner's portrait.
 *
 * Everything runs in the browser, no extra model or network:
 *   - the jaw opens with the loudness of the examiner's voice (Web Audio analyser),
 *     and the mouth is wider for bright sounds (i/e) and rounder for dark ones (o/u);
 *   - the eyes blink at natural, irregular intervals;
 *   - the head breathes, sways while talking, nods while the candidate speaks
 *     and tilts while "thinking".
 *
 * A rig gives the face landmarks as fractions of the portrait (0..1), so the same
 * code works for every examiner photo:
 *   { eyes: [[x, y], [x, y]], eyeW, eyeH, mouth: [x, y], mouthW, chin }
 */

const FRAME_MS = 1000 / 30;

export default class TalkingAvatar {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {string} imageUrl
     * @param {{eyes: number[][], eyeW: number, eyeH: number, mouth: number[], mouthW: number, chin: number}} rig
     */
    constructor(canvas, imageUrl, rig) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.rig = rig;
        this.reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

        this.mood = 'idle'; // idle | speaking | listening | thinking
        this.userTalking = false;
        this.analyser = null;
        this.simulated = false;

        this.open = 0; // smoothed jaw opening 0..1
        this.round = 0; // 0 = wide (i/e), 1 = round (o/u)
        this.blink = 0;
        this.nextBlinkAt = performance.now() + 1500;
        this.blinkStartedAt = 0;
        this.doubleBlink = false;
        this.lastFrame = 0;
        this.startedAt = performance.now();

        this.image = new Image();
        this.image.decoding = 'async';
        this.image.onload = () => this.prepare();
        this.image.src = imageUrl;
    }

    /**
     * Cache the crop, skin tone and a softened copy of the lower face once the photo is loaded.
     */
    prepare() {
        const { image, rig } = this;
        const W = image.naturalWidth;
        const H = image.naturalHeight;
        const eyeY = (rig.eyes[0][1] + rig.eyes[1][1]) / 2;

        // Square crop around the face: same face size for every examiner.
        const size = Math.min(1, (rig.chin - eyeY) * 2.7);
        const cx = rig.mouth[0];
        const cy = (eyeY + rig.chin) / 2;
        this.crop = {
            x: Math.max(0, Math.min(1 - size, cx - size / 2)) * W,
            y: Math.max(0, Math.min(1 - size, cy - size / 2)) * H,
            size: size * W,
        };

        this.resize();
        // Eyelids take the colour of the skin just under the eye (above it there may be brows or a frame).
        this.lidShade = this.sampleColor(rig.eyes[0][0], rig.eyes[0][1] + rig.eyeH * 1.3, 0.005);
        this.ready = true;
        this.loop = this.loop.bind(this);
        this.frame = requestAnimationFrame(this.loop);
    }

    resize() {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        const css = this.canvas.clientWidth || 240;
        this.canvas.width = Math.round(css * ratio);
        this.canvas.height = Math.round(css * ratio);
        this.scale = this.canvas.width / this.crop.size;
    }

    /**
     * Average colour of a small patch of the portrait (fractions of the image).
     */
    sampleColor(fx, fy, fr) {
        const W = this.image.naturalWidth;
        const r = Math.max(2, Math.round(fr * W));
        const probe = document.createElement('canvas');
        probe.width = r * 2;
        probe.height = r * 2;
        const pctx = probe.getContext('2d', { willReadFrequently: true });
        pctx.drawImage(this.image, fx * W - r, fy * this.image.naturalHeight - r, r * 2, r * 2, 0, 0, r * 2, r * 2);
        const data = pctx.getImageData(0, 0, r * 2, r * 2).data;
        let red = 0;
        let green = 0;
        let blue = 0;
        for (let i = 0; i < data.length; i += 4) {
            red += data[i];
            green += data[i + 1];
            blue += data[i + 2];
        }
        const n = data.length / 4;
        return [Math.round(red / n), Math.round(green / n), Math.round(blue / n)];
    }

    // ----- Inputs ------------------------------------------------------------

    /** Drive the mouth from a Web Audio analyser connected to the examiner's voice. */
    useAnalyser(analyser) {
        this.analyser = analyser;
        this.levels = new Uint8Array(analyser.fftSize);
        this.spectrum = new Uint8Array(analyser.frequencyBinCount);
    }

    /** Fake lip movement for the browser voice, which exposes no audio signal. */
    simulate(on) {
        this.simulated = on;
    }

    setMood(mood) {
        this.mood = mood;
    }

    setUserTalking(on) {
        this.userTalking = on;
    }

    destroy() {
        cancelAnimationFrame(this.frame);
        this.ready = false;
    }

    // ----- Animation --------------------------------------------------------

    loop(now) {
        this.frame = requestAnimationFrame(this.loop);
        if (now - this.lastFrame < FRAME_MS || document.hidden) return;
        const dt = Math.min(100, now - (this.lastFrame || now));
        this.lastFrame = now;
        if (this.canvas.clientWidth && Math.abs(this.canvas.width - this.canvas.clientWidth * Math.min(window.devicePixelRatio || 1, 2)) > 2) this.resize();

        this.updateMouth(now, dt);
        this.updateBlink(now);
        this.draw(now);
    }

    updateMouth(now, dt) {
        let target = 0;
        let roundTarget = this.round;

        if (this.mood === 'speaking' && this.analyser) {
            this.analyser.getByteTimeDomainData(this.levels);
            let sum = 0;
            for (let i = 0; i < this.levels.length; i++) {
                const v = (this.levels[i] - 128) / 128;
                sum += v * v;
            }
            const rms = Math.sqrt(sum / this.levels.length);
            target = Math.min(1, Math.max(0, (rms - 0.015) * 5.5));

            // Spectral centroid: bright vowels (i, e) spread the lips, dark ones (o, u) round them.
            this.analyser.getByteFrequencyData(this.spectrum);
            let weighted = 0;
            let total = 0;
            const bins = Math.min(this.spectrum.length, 96);
            for (let i = 2; i < bins; i++) {
                weighted += i * this.spectrum[i];
                total += this.spectrum[i];
            }
            if (total > 400) roundTarget = Math.min(1, Math.max(0, 1 - (weighted / total - 10) / 22));
        } else if (this.mood === 'speaking' && this.simulated) {
            // Syllable-like rhythm (~4-5 per second) with natural irregularity.
            const t = (now - this.startedAt) / 1000;
            const syllable = Math.max(0, Math.sin(t * 9.3) * 0.6 + Math.sin(t * 13.7 + 1.3) * 0.4);
            target = Math.min(1, syllable * (0.55 + 0.35 * Math.sin(t * 1.7) ** 2));
            roundTarget = 0.5 + 0.5 * Math.sin(t * 2.3);
        }

        // Jaws open fast and close a little slower, like a real mouth.
        const speed = target > this.open ? 0.55 : 0.3;
        this.open += (target - this.open) * Math.min(1, speed * (dt / FRAME_MS));
        this.round += (roundTarget - this.round) * 0.2;
    }

    updateBlink(now) {
        if (!this.blinkStartedAt && now >= this.nextBlinkAt) {
            this.blinkStartedAt = now;
        }
        if (this.blinkStartedAt) {
            const t = (now - this.blinkStartedAt) / 160; // one blink ≈ 160 ms
            this.blink = t < 0.5 ? t * 2 : Math.max(0, 2 - t * 2);
            if (t >= 1) {
                this.blink = 0;
                this.blinkStartedAt = 0;
                if (!this.doubleBlink && Math.random() < 0.18) {
                    this.doubleBlink = true;
                    this.nextBlinkAt = now + 120;
                } else {
                    this.doubleBlink = false;
                    // People blink less while concentrating (thinking) and a little more while talking.
                    const base = this.mood === 'thinking' ? 4200 : this.mood === 'speaking' ? 2600 : 3400;
                    this.nextBlinkAt = now + base * (0.5 + Math.random());
                }
            }
        }
    }

    /**
     * Head pose in degrees / fraction of the crop, from the current mood.
     */
    headPose(now) {
        if (this.reducedMotion) return { tilt: 0, dx: 0, dy: 0, zoom: 1 };
        const t = (now - this.startedAt) / 1000;
        const breathe = Math.sin(t * 1.6) * 0.004;
        let tilt = Math.sin(t * 0.45) * 0.6;
        let dx = Math.sin(t * 0.3) * 0.003;
        let dy = breathe;
        let zoom = 1;

        if (this.mood === 'speaking') {
            tilt += Math.sin(t * 1.9) * 1.1 + this.open * 0.6;
            dy += this.open * 0.004;
        } else if (this.mood === 'listening') {
            zoom = 1.02; // leaning in
            if (this.userTalking) dy += Math.max(0, Math.sin(t * 3.1)) ** 3 * 0.012; // small nods
        } else if (this.mood === 'thinking') {
            tilt += 4;
            dx += 0.006;
        }

        return { tilt, dx, dy, zoom };
    }

    draw(now) {
        if (!this.ready) return;
        const { ctx, canvas, crop, scale, rig, image } = this;
        const W = image.naturalWidth;
        const H = image.naturalHeight;
        const pose = this.headPose(now);

        // Image fraction -> canvas pixel.
        const px = (fx) => (fx * W - crop.x) * scale;
        const py = (fy) => (fy * H - crop.y) * scale;

        ctx.save();
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Head pose: rotate around the neck.
        const pivotX = px(rig.mouth[0]);
        const pivotY = py(rig.chin + (rig.chin - rig.mouth[1]) * 0.8);
        ctx.translate(pivotX + pose.dx * canvas.width, pivotY + pose.dy * canvas.height);
        ctx.rotate((pose.tilt * Math.PI) / 180);
        ctx.scale(pose.zoom, pose.zoom);
        ctx.translate(-pivotX, -pivotY);

        // Slight overscan so the edges never show while the head moves.
        const over = crop.size * 0.04;
        ctx.drawImage(image, crop.x - over, crop.y - over, crop.size + over * 2, crop.size + over * 2,
            -over * scale, -over * scale, canvas.width + over * 2 * scale, canvas.height + over * 2 * scale);

        this.drawMouth(px, py);
        this.drawEyelids(px, py);

        ctx.restore();
    }

    /**
     * Open the jaw: paint the inside of the mouth, then redraw the lower face shifted down
     * with soft edges so it blends into the cheeks.
     */
    drawMouth(px, py) {
        if (this.open < 0.02) return;
        const { ctx, rig, image, crop, scale } = this;
        const W = image.naturalWidth;
        const H = image.naturalHeight;

        const mouthX = px(rig.mouth[0]);
        const mouthY = py(rig.mouth[1]);
        const jawLength = (rig.chin - rig.mouth[1]) * H * scale;
        const drop = this.open * jawLength * 0.2;
        const halfWidth = (rig.mouthW * W * scale) / 2;
        const lipsHalf = halfWidth * (0.82 - this.round * 0.22);

        // Inside of the mouth.
        ctx.save();
        ctx.beginPath();
        ctx.ellipse(mouthX, mouthY + drop * 0.45, lipsHalf, drop * 0.62 + 0.5, 0, 0, Math.PI * 2);
        ctx.clip();
        const inside = ctx.createLinearGradient(0, mouthY - 2, 0, mouthY + drop);
        inside.addColorStop(0, '#2a1012');
        inside.addColorStop(1, '#4a1c20');
        ctx.fillStyle = inside;
        ctx.fillRect(mouthX - lipsHalf, mouthY - drop, lipsHalf * 2, drop * 3);
        if (this.open > 0.3) {
            // Upper teeth.
            ctx.fillStyle = 'rgba(236, 230, 222, 0.85)';
            ctx.fillRect(mouthX - lipsHalf * 0.62, mouthY - 1, lipsHalf * 1.24, Math.max(1.5, drop * 0.22));
        }
        ctx.restore();

        // Lower face (lower lip down to below the chin), shifted down with feathered edges.
        if (!this.jawLayer) this.jawLayer = document.createElement('canvas');
        const layer = this.jawLayer;
        const regionW = halfWidth * 2 * 2.1;
        const regionH = jawLength * 1.35;
        layer.width = Math.max(1, Math.round(regionW));
        layer.height = Math.max(1, Math.round(regionH));
        const lctx = layer.getContext('2d');
        lctx.clearRect(0, 0, layer.width, layer.height);
        const srcX = crop.x + (mouthX - regionW / 2) / scale;
        const srcY = rig.mouth[1] * H;
        lctx.drawImage(image, srcX, srcY, regionW / scale, regionH / scale, 0, 0, layer.width, layer.height);

        // Feather: solid in the middle, transparent towards the sides and the bottom.
        lctx.globalCompositeOperation = 'destination-in';
        const sides = lctx.createLinearGradient(0, 0, layer.width, 0);
        sides.addColorStop(0, 'rgba(0,0,0,0)');
        sides.addColorStop(0.28, 'rgba(0,0,0,1)');
        sides.addColorStop(0.72, 'rgba(0,0,0,1)');
        sides.addColorStop(1, 'rgba(0,0,0,0)');
        lctx.fillStyle = sides;
        lctx.fillRect(0, 0, layer.width, layer.height);
        const bottom = lctx.createLinearGradient(0, 0, 0, layer.height);
        bottom.addColorStop(0, 'rgba(0,0,0,1)');
        bottom.addColorStop(0.7, 'rgba(0,0,0,1)');
        bottom.addColorStop(1, 'rgba(0,0,0,0)');
        lctx.fillStyle = bottom;
        lctx.fillRect(0, 0, layer.width, layer.height);
        lctx.globalCompositeOperation = 'source-over';

        ctx.drawImage(layer, mouthX - regionW / 2, mouthY + drop);
    }

    drawEyelids(px, py) {
        if (this.blink < 0.05) return;
        const { ctx, rig, image, scale } = this;
        const eyeW = rig.eyeW * image.naturalWidth * scale;
        const eyeH = rig.eyeH * image.naturalHeight * scale;
        const [r, g, b] = this.lidShade;

        for (const [fx, fy] of rig.eyes) {
            const x = px(fx);
            const y = py(fy);
            const rx = eyeW * 0.52;
            const ry = eyeH * 0.62;
            // The upper lid slides down over the eye opening and stops on the lower lash line.
            const edge = y - ry + ry * 2 * this.blink;
            ctx.save();
            ctx.beginPath();
            ctx.ellipse(x, y, rx, ry, 0, 0, Math.PI * 2);
            ctx.clip();
            ctx.filter = 'blur(0.6px)';
            const lid = ctx.createLinearGradient(0, y - ry, 0, edge);
            // A closed lid is a little darker than the cheek and shaded towards the brow.
            lid.addColorStop(0, `rgb(${Math.round(r * 0.78)}, ${Math.round(g * 0.72)}, ${Math.round(b * 0.7)})`);
            lid.addColorStop(1, `rgb(${Math.round(r * 0.92)}, ${Math.round(g * 0.88)}, ${Math.round(b * 0.86)})`);
            ctx.fillStyle = lid;
            ctx.beginPath();
            ctx.moveTo(x - rx * 1.2, y - ry * 1.2);
            ctx.lineTo(x + rx * 1.2, y - ry * 1.2);
            ctx.lineTo(x + rx * 1.2, edge - ry * 0.25);
            ctx.quadraticCurveTo(x, edge + ry * 0.35, x - rx * 1.2, edge - ry * 0.25);
            ctx.closePath();
            ctx.fill();
            ctx.filter = 'none';
            // Lash line along the edge of the lid.
            ctx.strokeStyle = `rgba(30, 18, 14, ${0.35 + 0.5 * this.blink})`;
            ctx.lineWidth = Math.max(1, eyeH * 0.1);
            ctx.beginPath();
            ctx.moveTo(x - rx, edge - ry * 0.25);
            ctx.quadraticCurveTo(x, edge + ry * 0.35, x + rx, edge - ry * 0.25);
            ctx.stroke();
            ctx.restore();
        }
    }
}
