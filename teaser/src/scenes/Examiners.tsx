import React from 'react';
import { AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { BODY, C, DISPLAY, EXAMINERS, ExaminerKey } from '../theme';
import { BrandBackdrop, Bubble, Chip, Headline, Icon, Mark, typed, useLayout, Wave, WordsIn } from '../ui';
import { cue, isSpeaking, useVoiceLevel } from '../voice';
import timeline from '../timeline.json';

const ORDER: ExaminerKey[] = ['hrd', 'dosen', 'investor'];
const START = timeline.scenes.examiners;

const Card: React.FC<{ who: ExaminerKey; index: number }> = ({ who, index }) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const { u, portrait } = useLayout();
    const ex = EXAMINERS[who];
    const v = cue(who);
    const local = v.at - START; // when this examiner starts talking, inside the scene
    const level = useVoiceLevel(who, START);
    const abs = frame + START;

    const speaking = isSpeaking(who, abs, 14);
    // Steps forward when it starts talking, steps back after the line ends.
    const springIn = spring({ frame: frame - local, fps, config: { damping: 16, stiffness: 120 } });
    const springOut = spring({ frame: frame - (local + v.frames + 14), fps, config: { damping: 16, stiffness: 120 } });
    const activeNow = Math.max(0, Math.min(1, springIn - springOut));
    const enter = spring({ frame: frame - index * 5, fps, config: { damping: 15, stiffness: 120 } });
    // While someone talks the others fade back; after the last line everyone is lit again.
    const lastEnd = cue('investor').at + cue('investor').frames + 14;
    const base = interpolate(abs, [cue('hrd').at, cue('hrd').at + 8, lastEnd, lastEnd + 10], [1, 0.5, 0.5, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
    const dim = base + (1 - base) * activeNow;
    const scale = 1 + 0.05 * activeNow;
    const line = typed(ex.line, frame, local, v.frames);
    const avatar = (portrait ? 230 : 260) * u;

    const stage = (
        <div style={{
            position: 'relative', overflow: 'hidden', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
            background: `linear-gradient(160deg, rgb(${ex.rgb} / 0.22), rgb(${ex.rgb} / 0.05) 60%, #fff)`,
            ...(portrait ? { width: 400 * u, alignSelf: 'stretch' } : { height: 420 * u }),
        }}>
            <div style={{ position: 'absolute', inset: 0, opacity: 0.6, backgroundImage: `repeating-linear-gradient(-45deg, rgb(${ex.rgb} / 0.1) 0 3px, transparent 3px ${18 * u}px)` }} />
            <div style={{ position: 'absolute', inset: 0, background: `radial-gradient(45% 55% at 50% 45%, rgb(${ex.rgb} / ${0.35 + level * 0.4}), transparent 72%)` }} />
            <div style={{ position: 'relative', width: avatar, height: avatar }}>
                {[0, 1].map((r) => (
                    <div key={r} style={{ position: 'absolute', inset: -(14 + r * 18 + level * 26) * u, borderRadius: '50%', border: `${3 * u}px solid rgb(${ex.rgb} / ${(0.55 - r * 0.25) * activeNow})` }} />
                ))}
                <Img src={staticFile(ex.avatar)} style={{ width: avatar, height: avatar, borderRadius: '50%', objectFit: 'cover', border: `${7 * u}px solid #fff`, boxShadow: `0 ${24 * u}px ${50 * u}px -${20 * u}px rgb(15 23 42 / 0.55)` }} />
                <div style={{ position: 'absolute', right: -4 * u, bottom: 4 * u, width: 66 * u, height: 66 * u, borderRadius: '50%', background: ex.color, border: `${6 * u}px solid #fff`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <Wave size={26 * u} level={speaking ? 0.25 + level : 0.15} seed={who} bars={4} />
                </div>
            </div>
            <div style={{ position: 'absolute', left: 18 * u, top: 18 * u, display: 'flex', alignItems: 'center', gap: 8 * u, padding: `${6 * u}px ${14 * u}px`, borderRadius: 999, background: 'rgb(255 255 255 / 0.92)', fontFamily: BODY, fontWeight: 700, fontSize: 20 * u, color: C.ink }}>
                <span style={{ width: 10 * u, height: 10 * u, borderRadius: '50%', background: C.emerald }} /> Online
            </div>
        </div>
    );

    const body = (
        <div style={{ padding: `${(portrait ? 30 : 28) * u}px ${30 * u}px`, display: 'flex', flexDirection: 'column', gap: 12 * u, flex: 1, minWidth: 0 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 * u, color: ex.color, fontFamily: BODY, fontWeight: 800, fontSize: 24 * u }}>
                <Icon name={ex.icon} size={30 * u} /> {ex.mode}
            </div>
            <div style={{ fontFamily: DISPLAY, fontWeight: 800, fontSize: 52 * u, letterSpacing: '-0.03em', color: C.ink, lineHeight: 1 }}>{ex.name}</div>
            <div style={{ display: 'flex', gap: 8 * u, flexWrap: 'wrap' }}>
                {ex.traits.map((t) => <Chip key={t} color={ex.color} u={u} style={{ fontSize: 21 * u, padding: `${5 * u}px ${14 * u}px` }}>{t}</Chip>)}
            </div>
            <Bubble u={u} style={{ marginTop: 8 * u, padding: `${16 * u}px ${20 * u}px`, fontSize: 28 * u, minHeight: 80 * u, lineHeight: 1.25, opacity: line ? 1 : 0.35, border: speaking ? `${2.5 * u}px solid ${ex.color}` : `${2.5 * u}px solid transparent` }}>
                {line ? <>“{line}{line.length < ex.line.length ? <span style={{ color: ex.color }}>▍</span> : '”'}</> : <span style={{ color: C.sub }}>…</span>}
            </Bubble>
        </div>
    );

    return (
        <div style={{
            display: 'flex', flexDirection: portrait ? 'row' : 'column', background: '#fff', borderRadius: 40 * u, overflow: 'hidden',
            boxShadow: `0 ${30 * u}px ${70 * u}px -${30 * u}px rgb(${ex.rgb} / ${0.3 + activeNow * 0.5}), 0 0 0 ${1.5 * u}px rgb(15 23 42 / 0.06)`,
            width: portrait ? 980 * u : 540 * u, height: portrait ? 460 * u : 'auto',
            transform: `translateY(${(1 - enter) * 160 * u - activeNow * 18 * u}px) scale(${scale})`, opacity: Math.min(1, enter * 1.3) * dim,
            filter: `saturate(${0.4 + 0.6 * dim})`,
        }}>
            {stage}
            {body}
        </div>
    );
};

export const Examiners: React.FC = () => {
    const { u, portrait } = useLayout();
    return (
        <AbsoluteFill>
            <BrandBackdrop />
            <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'flex-start', paddingTop: (portrait ? 150 : 70) * u, gap: (portrait ? 50 : 46) * u }}>
                <Headline size={(portrait ? 104 : 92) * u} style={{ textAlign: 'center' }}>
                    <WordsIn text="3 penguji," delay={2} />{portrait ? <br /> : ' '}
                    <Mark delay={14}><WordsIn text="3 karakter." delay={8} /></Mark>
                </Headline>
                <div style={{ display: 'flex', flexDirection: portrait ? 'column' : 'row', gap: (portrait ? 34 : 40) * u, alignItems: 'center' }}>
                    {ORDER.map((who, i) => <Card key={who} who={who} index={i} />)}
                </div>
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
