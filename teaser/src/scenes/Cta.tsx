import React from 'react';
import { AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { BODY, C, EXAMINERS, ExaminerKey } from '../theme';
import { BrandBackdrop, Chip, gradientWord, Headline, Icon, useBeat, useLayout, WordsIn } from '../ui';
import { cue } from '../voice';
import timeline from '../timeline.json';

const START = timeline.scenes.cta;

export const Cta: React.FC = () => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const { u, portrait } = useLayout();
    const beat = useBeat();
    const v = cue('n_cta');
    const vStart = v.at - START;
    const pill = spring({ frame: frame - (vStart + Math.round(v.frames * 0.45)), fps, config: { damping: 10, stiffness: 160 } });
    const sticker = spring({ frame: frame - (vStart + Math.round(v.frames * 0.7)), fps, config: { damping: 9, stiffness: 180 } });
    const chips = interpolate(frame, [vStart + v.frames - 10, vStart + v.frames + 4], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const logo = spring({ frame: frame - (vStart + v.frames), fps, config: { damping: 14, stiffness: 120 } });
    const avatar = (portrait ? 170 : 150) * u;

    return (
        <AbsoluteFill>
            <BrandBackdrop />
            <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', flexDirection: 'column', gap: (portrait ? 56 : 44) * u, padding: `0 ${60 * u}px` }}>
                {/* The three examiners "ringing", like the incoming call on the booth screen */}
                <div style={{ display: 'flex', gap: (portrait ? 34 : 40) * u }}>
                    {(['hrd', 'dosen', 'investor'] as ExaminerKey[]).map((who, i) => {
                        const ex = EXAMINERS[who];
                        const enter = spring({ frame: frame - i * 4, fps, config: { damping: 11, stiffness: 160 } });
                        const ring = Math.sin((frame + i * 5) / 2.2) * 6 * Math.max(0, Math.sin((frame + i * 9) / 14));
                        return (
                            <div key={who} style={{ position: 'relative', width: avatar, height: avatar, transform: `scale(${enter}) rotate(${ring}deg)` }}>
                                <div style={{ position: 'absolute', inset: -(10 + beat * 12) * u, borderRadius: '50%', border: `${4 * u}px solid rgb(${ex.rgb} / ${0.25 + beat * 0.45})` }} />
                                <Img src={staticFile(ex.avatar)} style={{ width: avatar, height: avatar, borderRadius: '50%', objectFit: 'cover', border: `${6 * u}px solid #fff`, boxShadow: `0 ${20 * u}px ${40 * u}px -${16 * u}px rgb(${ex.rgb} / 0.7)` }} />
                            </div>
                        );
                    })}
                </div>

                <Headline size={(portrait ? 116 : 138) * u} style={{ textAlign: 'center' }}>
                    <WordsIn text="Berani ditelepon" delay={vStart} stagger={4} />
                    <br />
                    <WordsIn text="penguji kami?" delay={vStart + Math.round(v.frames * 0.2)} stagger={5} wordStyle={gradientWord} />
                </Headline>

                <div style={{ display: 'flex', flexDirection: portrait ? 'column' : 'row', alignItems: 'center', gap: 30 * u }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 18 * u, padding: `${22 * u}px ${40 * u}px ${22 * u}px ${24 * u}px`, borderRadius: 999, background: C.ink, color: '#fff', fontFamily: BODY, fontWeight: 800, fontSize: 44 * u, transform: `scale(${pill * (1 + beat * 0.03)})`, boxShadow: `0 ${24 * u}px ${50 * u}px -${20 * u}px rgb(15 23 42 / 0.7)` }}>
                        <span style={{ width: 74 * u, height: 74 * u, borderRadius: '50%', background: C.emerald, display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: `0 0 0 ${(6 + beat * 14) * u}px rgb(16 185 129 / ${0.35 - beat * 0.25})` }}>
                            <svg width={38 * u} height={38 * u} viewBox="0 0 24 24" fill="#fff"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.6a1 1 0 01-.25 1z" /></svg>
                        </span>
                        Coba gratis di booth VOIC
                    </div>
                    <div style={{ padding: `${16 * u}px ${30 * u}px`, borderRadius: 18 * u, background: C.amber, color: C.ink, fontFamily: BODY, fontWeight: 900, fontSize: 38 * u, letterSpacing: '0.04em', transform: `rotate(${-6 + (1 - sticker) * 25}deg) scale(${sticker})`, boxShadow: `0 ${16 * u}px ${30 * u}px -${12 * u}px rgb(180 83 9 / 0.6)` }}>
                        INNOFEST 2026
                    </div>
                </div>

                {/* Phones are watched small: the chips get bigger there. */}
                <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: 14 * u, maxWidth: (portrait ? 860 : 1400) * u, opacity: chips, transform: `translateY(${(1 - chips) * 20 * u}px)` }}>
                    {([['gift', C.emerald, 'Gratis'], ['user', C.indigo, 'Tanpa daftar akun'], ['wifi-off', C.violet, 'Bisa tanpa internet']] as const).map(([icon, color, label]) => (
                        <Chip key={label} color={color} u={u} style={{ fontSize: (portrait ? 34 : 26) * u, padding: `${(portrait ? 12 : 9) * u}px ${(portrait ? 24 : 20) * u}px` }}>
                            <Icon name={icon} size={(portrait ? 36 : 28) * u} />{label}
                        </Chip>
                    ))}
                </div>

                <Img src={staticFile('img/logo-horizontal.svg')} style={{ height: (portrait ? 92 : 80) * u, marginTop: 10 * u, opacity: logo, transform: `translateY(${(1 - logo) * 30 * u}px)` }} />
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
