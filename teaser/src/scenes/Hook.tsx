import React from 'react';
import { AbsoluteFill, interpolate, random, Sequence, useCurrentFrame } from 'remotion';
import { BODY, DISPLAY, EXAMINERS, ExaminerKey } from '../theme';
import { Grain, Icon, useLayout, WordsIn } from '../ui';
import { cue, VoiceKey } from '../voice';
import timeline from '../timeline.json';

// Three fears, three examiners: each question floods the screen in that examiner's colour.
const QUESTIONS: { key: VoiceKey; who: ExaminerKey; lead: string; hot: string }[] = [
    { key: 'n_hook1', who: 'dosen', lead: 'Deg-degan mau', hot: 'sidang?' },
    { key: 'n_hook2', who: 'hrd', lead: 'Grogi pas', hot: 'interview?' },
    { key: 'n_hook3', who: 'investor', lead: 'Ditanya investor,', hot: 'blank?' },
];

/** A heartbeat line racing across the bottom: the nerves before an oral exam. */
const Heartbeat: React.FC<{ u: number; width: number; y: number }> = ({ u, width, y }) => {
    const frame = useCurrentFrame();
    const unit = 260 * u;
    let d = `M0 0`;
    for (let x = 0; x < width + unit; x += unit) {
        d += ` L${x + unit * 0.35} 0 L${x + unit * 0.42} ${-18 * u} L${x + unit * 0.5} ${70 * u} L${x + unit * 0.57} ${-120 * u} L${x + unit * 0.64} ${30 * u} L${x + unit * 0.7} 0 L${x + unit} 0`;
    }
    const shift = (frame * 9 * u) % unit;
    return (
        <svg width={width} height={300 * u} style={{ position: 'absolute', left: 0, top: y - 150 * u, opacity: 0.35 }}>
            <path d={d} transform={`translate(${-shift} ${150 * u})`} fill="none" stroke="#fff" strokeWidth={6 * u} strokeLinejoin="round" strokeLinecap="round" />
        </svg>
    );
};

const Question: React.FC<{ q: (typeof QUESTIONS)[number]; index: number }> = ({ q, index }) => {
    const frame = useCurrentFrame();
    const { u, width, height, portrait } = useLayout();
    const ex = EXAMINERS[q.who];
    // The colour floods in from the centre like a ripple.
    const radius = interpolate(frame, [0, 11], [0, 150], { extrapolateRight: 'clamp' });
    // Nervous jitter on the text, calming slightly over the shot.
    const jitter = interpolate(frame, [0, 40], [5, 1.5], { extrapolateRight: 'clamp' }) * u;
    const jx = (random(`x${index}-${Math.floor(frame / 2)}`) - 0.5) * jitter;
    const jy = (random(`y${index}-${Math.floor(frame / 2)}`) - 0.5) * jitter;
    const size = (portrait ? 150 : 168) * u;
    const qRotate = interpolate(frame, [0, 60], [-12, 6]);
    return (
        <AbsoluteFill style={{ clipPath: `circle(${radius}% at 50% 50%)`, background: `radial-gradient(120% 90% at 50% 40%, ${ex.color}, color-mix(in srgb, ${ex.color} 55%, #0f172a))`, overflow: 'hidden' }}>
            {/* Giant outlined "?" drifting behind the words */}
            <div style={{ position: 'absolute', right: portrait ? -120 * u : 60 * u, top: portrait ? 120 * u : -140 * u, fontFamily: DISPLAY, fontWeight: 800, fontSize: 900 * u, lineHeight: 1, color: 'transparent', WebkitTextStroke: `${4 * u}px rgb(255 255 255 / 0.22)`, transform: `rotate(${qRotate}deg)` }}>?</div>
            <Heartbeat u={u} width={width} y={height * (portrait ? 0.78 : 0.82)} />
            <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', padding: `0 ${80 * u}px` }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 16 * u, padding: `${12 * u}px ${26 * u}px`, borderRadius: 999, background: 'rgb(255 255 255 / 0.16)', color: '#fff', fontFamily: BODY, fontWeight: 800, fontSize: 30 * u, marginBottom: 36 * u, opacity: interpolate(frame, [4, 10], [0, 1], { extrapolateRight: 'clamp' }) }}>
                    <Icon name={ex.icon} size={36 * u} />{ex.mode}
                </div>
                <div style={{ fontFamily: DISPLAY, fontWeight: 800, fontSize: size, lineHeight: 0.98, letterSpacing: '-0.04em', color: '#fff', textAlign: 'center', transform: `translate(${jx}px, ${jy}px)`, textShadow: `0 ${10 * u}px ${40 * u}px rgb(0 0 0 / 0.25)` }}>
                    <WordsIn text={q.lead} delay={3} />
                    {portrait ? <br /> : ' '}
                    <span style={{
                        display: 'inline-block', marginTop: 10 * u, padding: `0 ${0.16 * size}px ${0.06 * size}px`, borderRadius: 0.14 * size, background: '#fff', color: '#0f172a',
                        opacity: interpolate(frame, [9, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }),
                        transform: `rotate(-2.5deg) scale(${interpolate(frame, [9, 15, 21], [0.55, 1.12, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' })})`,
                        boxShadow: `0 ${16 * u}px ${40 * u}px -${10 * u}px rgb(0 0 0 / 0.45)`,
                    }}>
                        {q.hot}
                    </span>
                </div>
            </AbsoluteFill>
            <Grain opacity={0.12} />
        </AbsoluteFill>
    );
};

export const Hook: React.FC = () => {
    const end = timeline.scenes.reveal;
    return (
        <AbsoluteFill style={{ background: '#0f172a' }}>
            {QUESTIONS.map((q, i) => {
                const from = i === 0 ? 0 : cue(q.key).at - 6;
                const to = i < QUESTIONS.length - 1 ? cue(QUESTIONS[i + 1].key).at - 6 + 12 : end;
                return (
                    <Sequence key={q.key} from={from} durationInFrames={to - from} name={q.hot}>
                        <Question q={q} index={i} />
                    </Sequence>
                );
            })}
        </AbsoluteFill>
    );
};
