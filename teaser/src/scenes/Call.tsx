import React from 'react';
import { AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { BODY, C, DISPLAY, EXAMINERS } from '../theme';
import { BrandBackdrop, Bubble, Headline, Icon, Mark, typed, useLayout, Wave, WordsIn } from '../ui';
import { cue, isSpeaking, useVoiceLevel } from '../voice';
import timeline from '../timeline.json';

const START = timeline.scenes.call;
const ANSWER = 'Akurasi model saya 87 persen, Pak.';

/** Floating callout that pops in at `at` (scene frame). */
const Callout: React.FC<{ at: number; icon: Parameters<typeof Icon>[0]['name']; color: string; children: React.ReactNode; style: React.CSSProperties }> = ({ at, icon, color, children, style }) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const { u } = useLayout();
    const s = spring({ frame: frame - at, fps, config: { damping: 11, stiffness: 170 } });
    return (
        <div style={{ position: 'absolute', zIndex: 5, transform: `scale(${s}) rotate(${(1 - s) * -10}deg)`, ...style }}>
            <Bubble u={u} tail="none" style={{ display: 'flex', alignItems: 'center', gap: 16 * u, padding: `${16 * u}px ${26 * u}px ${16 * u}px ${16 * u}px`, fontSize: 30 * u }}>
                <span style={{ width: 58 * u, height: 58 * u, borderRadius: '50%', background: color, color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <Icon name={icon} size={32 * u} stroke={2.2} />
                </span>
                {children}
            </Bubble>
        </div>
    );
};

export const Call: React.FC = () => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const { u, portrait } = useLayout();
    const abs = frame + START;
    const dosen = EXAMINERS.dosen;
    const nCall = cue('n_call');
    const hmm = cue('call_hmm');
    const question = cue('call_question');
    const at = (c: { at: number }) => c.at - START;

    const examinerLevel = Math.max(useVoiceLevel('call_hmm', START), useVoiceLevel('call_question', START));
    const examinerTalking = isSpeaking('call_hmm', abs, 4) || isSpeaking('call_question', abs, 10);
    // The participant "answers" while the narrator explains (no voice, shown as a live caption + mic).
    const userTalking = abs >= nCall.at + 6 && abs < hmm.at - 4;
    const userLevel = userTalking ? 0.45 + 0.4 * Math.abs(Math.sin(frame / 3.1)) * Math.abs(Math.sin(frame / 5.3)) : 0;

    const win = spring({ frame, fps, config: { damping: 15, stiffness: 110 } });
    const winW = portrait ? 960 * u : 1440 * u;
    const winH = portrait ? 1080 * u : 760 * u;
    const avatar = (portrait ? 330 : 280) * u;
    const clock = 402 + Math.floor(frame / fps);
    const mm = String(Math.floor(clock / 60)).padStart(2, '0');
    const ss = String(clock % 60).padStart(2, '0');

    let caption: React.ReactNode = null;
    if (userTalking || (abs >= hmm.at - 4 && abs < hmm.at + 6)) {
        caption = <><b style={{ color: '#0284c7' }}>Kamu: </b>{typed(ANSWER, frame, at(nCall) + 6, Math.round(nCall.frames * 0.85))}</>;
    } else if (abs >= hmm.at && abs < question.at) {
        caption = <><b style={{ color: dosen.color }}>{dosen.name}: </b>{typed(hmm.text, frame, at(hmm), hmm.frames)}</>;
    } else if (abs >= question.at) {
        caption = <><b style={{ color: dosen.color }}>{dosen.name}: </b>{typed(question.text, frame, at(question), question.frames)}</>;
    }
    const hmmPop = spring({ frame: frame - at(hmm), fps, config: { damping: 9, stiffness: 200 } });
    const hmmOut = interpolate(abs, [question.at + 10, question.at + 20], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

    return (
        <AbsoluteFill>
            <BrandBackdrop />
            <AbsoluteFill style={{ alignItems: 'center', paddingTop: (portrait ? 120 : 48) * u }}>
                <Headline size={(portrait ? 96 : 76) * u} style={{ textAlign: 'center', marginBottom: (portrait ? 48 : 30) * u }}>
                    <WordsIn text="Ngobrol kayak" delay={2} />{portrait ? <br /> : ' '}
                    <Mark delay={16}><WordsIn text="ditelepon beneran." delay={8} /></Mark>
                </Headline>

                {/* The call window (built from the real exam room) */}
                <div style={{ position: 'relative', width: winW, height: winH, transform: `translateY(${(1 - win) * 200 * u}px) scale(${0.92 + 0.08 * win})`, opacity: win }}>
                    <div style={{ position: 'absolute', inset: 0, borderRadius: 44 * u, background: '#eef1f8', padding: 20 * u, boxShadow: `0 ${50 * u}px ${100 * u}px -${40 * u}px rgb(49 46 129 / 0.45), 0 0 0 ${2 * u}px rgb(15 23 42 / 0.08)`, display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: `${4 * u}px ${10 * u}px ${18 * u}px`, fontFamily: BODY, fontWeight: 700, fontSize: 24 * u, color: C.ink }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 14 * u }}>
                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 * u, padding: `${4 * u}px ${12 * u}px`, borderRadius: 8 * u, background: 'rgb(244 63 94 / 0.15)', color: C.rose, fontWeight: 800, letterSpacing: '0.08em' }}>
                                    <span style={{ width: 11 * u, height: 11 * u, borderRadius: '50%', background: C.rose, opacity: 0.4 + 0.6 * Math.abs(Math.sin(frame / 8)) }} />LIVE
                                </span>
                                {dosen.name}<span style={{ fontFamily: 'monospace', color: C.sub }}>{mm}:{ss}</span>
                            </div>
                            {!portrait && <span style={{ padding: `${6 * u}px ${16 * u}px`, borderRadius: 999, background: '#fff' }}>Bagian 3 · Tanya Jawab</span>}
                        </div>
                        <div style={{ position: 'relative', flex: 1, borderRadius: 30 * u, overflow: 'hidden', background: '#fff' }}>
                            <div style={{ position: 'absolute', inset: 0, background: `radial-gradient(55% 60% at 50% 42%, rgb(${dosen.rgb} / ${0.35 + examinerLevel * 0.45}), transparent 72%)` }} />
                            <div style={{ position: 'absolute', left: '50%', top: portrait ? '30%' : '44%', transform: 'translate(-50%, -50%)', width: avatar, height: avatar }}>
                                {[0, 1, 2].map((r) => (
                                    <div key={r} style={{ position: 'absolute', inset: -(12 + r * 22 + examinerLevel * (30 + r * 14)) * u, borderRadius: '50%', border: `${3 * u}px solid rgb(${dosen.rgb} / ${examinerTalking ? 0.55 - r * 0.16 : 0.12})` }} />
                                ))}
                                <Img src={staticFile(dosen.avatar)} style={{ width: avatar, height: avatar, borderRadius: '50%', objectFit: 'cover', border: `${6 * u}px solid #fff`, boxShadow: `0 ${24 * u}px ${50 * u}px -${20 * u}px rgb(15 23 42 / 0.5)` }} />
                                {/* Instant reaction while the answer is still being thought about */}
                                <div style={{ position: 'absolute', right: -170 * u, top: -30 * u, transform: `scale(${hmmPop * hmmOut})`, transformOrigin: 'left bottom' }}>
                                    <Bubble u={u} style={{ padding: `${12 * u}px ${24 * u}px`, fontSize: 36 * u, fontFamily: DISPLAY }}>Hmm…</Bubble>
                                </div>
                            </div>
                            <div style={{ position: 'absolute', left: 18 * u, top: 18 * u, display: 'flex', alignItems: 'center', gap: 10 * u, padding: `${6 * u}px ${14 * u}px`, borderRadius: 10 * u, background: 'rgb(255 255 255 / 0.92)', fontFamily: BODY, fontWeight: 700, fontSize: 22 * u, color: C.ink }}>
                                {examinerTalking ? <><Wave size={20 * u} color={C.ink} level={0.3 + examinerLevel} bars={3} seed="tag" /> Sedang bicara</> : <><Icon name="user" size={22 * u} /> Mendengarkan</>}
                            </div>
                            {/* Participant tile */}
                            <div style={{ position: 'absolute', right: 18 * u, top: 18 * u, width: (portrait ? 230 : 250) * u, height: (portrait ? 300 : 150) * u, borderRadius: 18 * u, background: 'linear-gradient(160deg, #e0f2fe, #f1f5f9)', border: `${3 * u}px solid ${userTalking ? '#38bdf8' : 'rgb(56 189 248 / 0.35)'}`, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 10 * u }}>
                                <div style={{ width: 70 * u, height: 70 * u, borderRadius: '50%', background: '#bae6fd', color: '#0369a1', display: 'flex', alignItems: 'center', justifyContent: 'center' }}><Icon name="user" size={42 * u} /></div>
                                <span style={{ fontFamily: BODY, fontWeight: 800, fontSize: 22 * u, color: C.ink, display: 'flex', alignItems: 'center', gap: 8 * u }}>Kamu {userTalking && <Wave size={18 * u} color="#0284c7" level={userLevel} bars={3} seed="me" />}</span>
                            </div>
                            {/* Live captions */}
                            {caption && (
                                <div style={{ position: 'absolute', left: '50%', bottom: (portrait ? 40 : 26) * u, transform: 'translateX(-50%)', width: portrait ? '88%' : '72%', textAlign: 'center', padding: `${14 * u}px ${24 * u}px`, borderRadius: 18 * u, background: 'rgb(255 255 255 / 0.95)', boxShadow: `0 ${10 * u}px ${30 * u}px -${14 * u}px rgb(15 23 42 / 0.3)`, fontFamily: BODY, fontWeight: 600, fontSize: (portrait ? 36 : 32) * u, color: C.ink, lineHeight: 1.3 }}>
                                    {caption}
                                </div>
                            )}
                        </div>
                        {/* Controls */}
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 22 * u, paddingTop: 18 * u }}>
                            <div style={{ position: 'relative', width: 84 * u, height: 84 * u, borderRadius: '50%', background: userTalking ? '#0ea5e9' : C.ink, color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', transform: `scale(${userTalking ? 1.08 : 1})` }}>
                                {userTalking && <div style={{ position: 'absolute', inset: -(8 + userLevel * 16) * u, borderRadius: '50%', background: 'rgb(56 189 248 / 0.3)' }} />}
                                <span style={{ position: 'relative' }}><Icon name="mic" size={42 * u} stroke={2.2} /></span>
                            </div>
                            {['', 'CC', ''].map((t, i) => <div key={i} style={{ width: 62 * u, height: 62 * u, borderRadius: '50%', background: t ? '#fff' : 'rgb(15 23 42 / 0.07)', fontFamily: BODY, fontWeight: 900, fontSize: 22 * u, display: 'flex', alignItems: 'center', justifyContent: 'center', color: C.ink }}>{t}</div>)}
                            <div style={{ height: 62 * u, padding: `0 ${26 * u}px`, borderRadius: 999, background: C.rose, color: '#fff', fontFamily: BODY, fontWeight: 800, fontSize: 24 * u, display: 'flex', alignItems: 'center' }}>Akhiri</div>
                        </div>
                    </div>

                    <Callout at={at(nCall) + 4} icon="mic" color="#0ea5e9" style={portrait ? { left: -10 * u, bottom: -100 * u } : { left: -140 * u, top: 150 * u }}>Jawab pakai suara</Callout>
                    <Callout at={at(hmm)} icon="bolt" color={C.amber} style={portrait ? { right: -10 * u, bottom: -205 * u } : { right: -150 * u, top: 300 * u }}>Reaksi instan</Callout>
                    <Callout at={at(question) + 8} icon="chat" color={dosen.color} style={portrait ? { left: 40 * u, bottom: -310 * u } : { left: -110 * u, top: 330 * u }}>Nanya balik sesuai jawabanmu</Callout>
                </div>
            </AbsoluteFill>
        </AbsoluteFill>
    );
};
