import React from 'react';
import { AbsoluteFill, Img, interpolate, staticFile, useCurrentFrame } from 'remotion';
import { BODY, C } from '../theme';
import { BrandBackdrop, gradientWord, Headline, Mark, useBeat, useEnter, useLayout, Wave, WordsIn } from '../ui';
import { cue } from '../voice';
import timeline from '../timeline.json';

/** Three rings in the examiners' colours, spinning around the logo. */
const Rings: React.FC<{ size: number; u: number }> = ({ size, u }) => {
    const frame = useCurrentFrame();
    const rings = [C.amber, C.indigo, C.violet];
    return (
        <>
            {rings.map((color, i) => {
                const r = size / 2 + (34 + i * 30) * u;
                const enter = interpolate(frame, [4 + i * 3, 18 + i * 3], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
                const circ = 2 * Math.PI * r;
                return (
                    <svg key={color} width={r * 2 + 10} height={r * 2 + 10} style={{ position: 'absolute', left: '50%', top: '50%', transform: `translate(-50%, -50%) rotate(${frame * (i % 2 ? -2.2 : 1.6) + i * 40}deg)` }}>
                        <circle cx={r + 5} cy={r + 5} r={r} fill="none" stroke={color} strokeWidth={(7 - i * 1.5) * u} strokeLinecap="round"
                            strokeDasharray={`${circ * 0.62 * enter} ${circ}`} opacity={0.9 - i * 0.18} />
                    </svg>
                );
            })}
        </>
    );
};

export const Reveal: React.FC = () => {
    const frame = useCurrentFrame();
    const { u, portrait } = useLayout();
    const beat = useBeat();
    const logo = useEnter(2, { damping: 11, stiffness: 160 });
    const v = cue('n_reveal');
    const vStart = v.at - timeline.scenes.reveal;
    const flash = interpolate(frame, [0, 9], [1, 0], { extrapolateRight: 'clamp' });
    const logoSize = (portrait ? 230 : 210) * u;
    const head = (portrait ? 128 : 122) * u;
    return (
        <AbsoluteFill>
            <BrandBackdrop />
            <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', flexDirection: 'column', gap: 120 * u }}>
                <div style={{ position: 'relative', width: logoSize, height: logoSize, transform: `scale(${logo * (1 + beat * 0.035)}) rotate(${(1 - logo) * -25}deg)` }}>
                    <Rings size={logoSize} u={u} />
                    <Img src={staticFile('img/logo-mark.svg')} style={{ position: 'relative', width: logoSize, height: logoSize, filter: `drop-shadow(0 ${20 * u}px ${30 * u}px rgb(30 58 138 / 0.35))` }} />
                </div>
                <div style={{ textAlign: 'center' }}>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 14 * u, padding: `${10 * u}px ${24 * u}px`, borderRadius: 999, background: '#fff', boxShadow: `0 ${10 * u}px ${24 * u}px -${12 * u}px rgb(15 23 42 / 0.4)`, fontFamily: BODY, fontWeight: 800, fontSize: 32 * u, color: C.emerald, transform: `scale(${useEnter(vStart)})`, marginBottom: 26 * u }}>
                        <Wave size={26 * u} color={C.emerald} seed="calm" level={0.6} /> Tenang.
                    </div>
                    <Headline size={head} style={{ textAlign: 'center' }}>
                        <WordsIn text="Latihan" delay={vStart + Math.round(v.frames * 0.22)} />{' '}
                        <Mark delay={vStart + Math.round(v.frames * 0.32)}><WordsIn text="dulu," delay={vStart + Math.round(v.frames * 0.28)} /></Mark>
                        <br />
                        <WordsIn text="bareng" delay={vStart + Math.round(v.frames * 0.5)} />{portrait ? <br /> : ' '}
                        <WordsIn text="penguji kami." delay={vStart + Math.round(v.frames * 0.62)} stagger={4} wordStyle={gradientWord} />
                    </Headline>
                </div>
            </AbsoluteFill>
            <AbsoluteFill style={{ background: '#fff', opacity: flash }} />
        </AbsoluteFill>
    );
};
