import React from 'react';
import { AbsoluteFill, Easing, interpolate, random, spring, useCurrentFrame, useVideoConfig } from 'remotion';
import { BODY, C, DISPLAY, ICONS, IconName } from './theme';
import timeline from './timeline.json';

export const BEAT = 15; // frames per beat at 120 BPM and 30 fps: the music and the motion share one pulse

/** Layout helpers: everything is designed at 1080 px on the short side, so both formats share one scale. */
export const useLayout = () => {
    const { width, height } = useVideoConfig();
    const portrait = height > width;
    return { width, height, portrait, u: Math.min(width, height) / 1080 };
};

/** A spring that starts `delay` frames into the current sequence. */
export const useEnter = (delay = 0, config: { damping?: number; stiffness?: number; mass?: number } = {}) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    return spring({ frame: frame - delay, fps, config: { damping: 14, stiffness: 140, mass: 0.8, ...config } });
};

/** 0 → 1 → 0 pulse on every beat of the music (strongest on the downbeat). */
export const useBeat = (offset = 0) => {
    const frame = useCurrentFrame() + offset;
    const phase = (frame % BEAT) / BEAT;
    return Math.exp(-phase * 5);
};

export const voice = timeline.voices;

export const Icon: React.FC<{ name: IconName; size: number; color?: string; stroke?: number }> = ({ name, size, color = 'currentColor', stroke = 1.8 }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke={color} strokeWidth={stroke} style={{ display: 'block', color }}
        dangerouslySetInnerHTML={{ __html: ICONS[name] }} />
);

/** Film grain + dot grid: the same "studio" texture as the website. */
export const Grain: React.FC<{ opacity?: number }> = ({ opacity = 0.09 }) => {
    const frame = useCurrentFrame();
    const seed = Math.floor(frame / 2) % 6; // grain shimmers like film
    const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='220' height='220'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' seed='${seed}' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 .6 0'/></filter><rect width='100%' height='100%' filter='url(#n)'/></svg>`;
    return (
        <AbsoluteFill style={{ pointerEvents: 'none', opacity, mixBlendMode: 'multiply', backgroundImage: `url("data:image/svg+xml;utf8,${encodeURIComponent(svg)}")` }} />
    );
};

/** Light brand backdrop with slow-drifting colour blobs (amber, indigo, violet = the three examiners). */
export const BrandBackdrop: React.FC<{ tint?: string }> = ({ tint }) => {
    const frame = useCurrentFrame();
    const { width, height } = useVideoConfig();
    const blob = (x: number, y: number, r: number, color: string, speed: number, phase: number) => {
        const dx = Math.sin(frame / speed + phase) * r * 0.18;
        const dy = Math.cos(frame / (speed * 1.3) + phase) * r * 0.14;
        return (
            <div style={{ position: 'absolute', left: x - r + dx, top: y - r + dy, width: r * 2, height: r * 2, borderRadius: '50%', background: color, filter: `blur(${r * 0.35}px)`, opacity: 0.55 }} />
        );
    };
    const m = Math.max(width, height);
    return (
        <AbsoluteFill style={{ background: C.paper, overflow: 'hidden' }}>
            {blob(width * 0.08, height * 0.12, m * 0.32, 'rgb(165 180 252 / 0.75)', 70, 0)}
            {blob(width * 0.92, height * 0.45, m * 0.28, 'rgb(186 230 253 / 0.8)', 80, 2)}
            {blob(width * 0.45, height * 1.0, m * 0.3, 'rgb(253 230 138 / 0.65)', 90, 4)}
            {tint && <AbsoluteFill style={{ background: tint }} />}
            <AbsoluteFill style={{ opacity: 0.45, backgroundImage: 'radial-gradient(rgb(15 23 42 / 0.09) 1.5px, transparent 1.5px)', backgroundSize: '30px 30px', maskImage: 'linear-gradient(to bottom, black, transparent 75%)' }} />
            <Grain />
        </AbsoluteFill>
    );
};

/** Little live sound wave (5 bars). `level` 0..1 scales it; at 0 the bars rest. */
export const Wave: React.FC<{ size: number; color?: string; level?: number; bars?: number; seed?: string }> = ({ size, color = '#fff', level = 1, bars = 5, seed = 'w' }) => {
    const frame = useCurrentFrame();
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: size * 0.12, height: size }}>
            {Array.from({ length: bars }).map((_, i) => {
                const wobble = 0.5 + 0.5 * Math.sin(frame / (2.2 + random(`${seed}${i}`) * 2.5) + i * 1.7);
                const h = size * (0.22 + 0.78 * wobble * level);
                return <div key={i} style={{ width: size * 0.16, height: h, borderRadius: size, background: color }} />;
            })}
        </div>
    );
};

/** Words rise in one after another (kinetic typography). */
export const WordsIn: React.FC<{ text: string; delay?: number; stagger?: number; style?: React.CSSProperties; wordStyle?: (i: number) => React.CSSProperties }> = ({ text, delay = 0, stagger = 3, style, wordStyle }) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    return (
        <span style={{ display: 'inline', ...style }}>
            {text.split(' ').map((word, i) => {
                const s = spring({ frame: frame - delay - i * stagger, fps, config: { damping: 13, stiffness: 170, mass: 0.7 } });
                return (
                    <span key={i} style={{ display: 'inline-block', whiteSpace: 'pre', transform: `translateY(${(1 - s) * 0.55}em) scale(${0.85 + 0.15 * s})`, opacity: Math.min(1, s * 1.4), ...wordStyle?.(i) }}>
                        {word}{i < text.split(' ').length - 1 ? ' ' : ''}
                    </span>
                );
            })}
        </span>
    );
};

/** Text that types out over `frames` frames (used in sync with a voice clip). */
export const typed = (text: string, frame: number, start: number, frames: number) => {
    const p = interpolate(frame, [start, start + frames], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    return text.slice(0, Math.round(text.length * p));
};

/** A hand-drawn highlighter stroke behind a word, drawn in at `delay`. */
export const Mark: React.FC<{ children: React.ReactNode; delay?: number; color?: string }> = ({ children, delay = 0, color = C.marker }) => {
    const frame = useCurrentFrame();
    const p = interpolate(frame, [delay, delay + 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.bezier(0.65, 0, 0.35, 1) });
    return (
        <span style={{ position: 'relative', display: 'inline-block', whiteSpace: 'nowrap' }}>
            <span style={{ position: 'absolute', left: '-0.1em', right: '-0.1em', top: '52%', bottom: '2%', background: color, borderRadius: '0.3em 0.6em 0.25em 0.5em', transform: `rotate(-1.5deg) scaleX(${p})`, transformOrigin: 'left center', zIndex: 0 }} />
            <span style={{ position: 'relative', zIndex: 1 }}>{children}</span>
        </span>
    );
};

export const GradientText: React.FC<{ children: React.ReactNode; style?: React.CSSProperties }> = ({ children, style }) => (
    <span style={{ backgroundImage: `linear-gradient(90deg, ${C.amber}, ${C.indigo} 55%, ${C.violet})`, WebkitBackgroundClip: 'text', backgroundClip: 'text', color: 'transparent', ...style }}>{children}</span>
);

/**
 * Gradient for animated words: background-clip:text does not reach transformed children,
 * so each word carries its own slice of the amber → indigo → violet gradient.
 */
export const gradientWord = (i: number): React.CSSProperties => ({
    backgroundImage: [`linear-gradient(90deg, ${C.amber}, ${C.indigo})`, `linear-gradient(90deg, ${C.indigo}, ${C.violet})`, `linear-gradient(90deg, ${C.violet}, ${C.violet})`][Math.min(i, 2)],
    WebkitBackgroundClip: 'text',
    backgroundClip: 'text',
    color: 'transparent',
    paddingBottom: '0.08em',
});

export const Bubble: React.FC<{ children: React.ReactNode; u: number; style?: React.CSSProperties; tail?: 'left' | 'none' }> = ({ children, u, style, tail = 'left' }) => (
    <div style={{
        background: '#fff', color: C.ink, fontFamily: BODY, fontWeight: 700, borderRadius: tail === 'left' ? `${26 * u}px ${26 * u}px ${26 * u}px ${7 * u}px` : 26 * u,
        boxShadow: `0 ${18 * u}px ${40 * u}px -${18 * u}px rgb(15 23 42 / 0.35), 0 0 0 ${1.5 * u}px rgb(15 23 42 / 0.06)`, ...style,
    }}>{children}</div>
);

export const Chip: React.FC<{ children: React.ReactNode; color: string; u: number; solid?: boolean; style?: React.CSSProperties }> = ({ children, color, u, solid, style }) => (
    <span style={{
        display: 'inline-flex', alignItems: 'center', gap: 10 * u, borderRadius: 999, padding: `${8 * u}px ${18 * u}px`, fontFamily: BODY, fontWeight: 800, fontSize: 24 * u,
        background: solid ? color : `color-mix(in srgb, ${color} 14%, white)`, color: solid ? '#fff' : color, ...style,
    }}>{children}</span>
);

export const Headline: React.FC<{ children: React.ReactNode; size: number; style?: React.CSSProperties }> = ({ children, size, style }) => (
    <div style={{ fontFamily: DISPLAY, fontWeight: 800, fontSize: size, lineHeight: 1.0, letterSpacing: '-0.035em', color: C.ink, ...style }}>{children}</div>
);
