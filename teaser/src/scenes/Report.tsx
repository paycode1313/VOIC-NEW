import React from 'react';
import { AbsoluteFill, Easing, Img, interpolate, random, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { BODY, C, DISPLAY, EXAMINERS } from '../theme';
import { BrandBackdrop, Chip, Headline, Icon, Mark, useLayout, WordsIn } from '../ui';

export const RING_DONE = 46; // scene frame where the ring finishes (the "ding")

const CRITERIA = [
    { label: 'Kelancaran', band: 8.0, color: C.amber },
    { label: 'Kosakata', band: 7.0, color: '#0ea5e9' },
    { label: 'Isi & Struktur', band: 7.5, color: C.indigo },
    { label: 'Delivery', band: 7.0, color: C.violet },
];

/** An illustrative QR (finder squares + seeded modules): the real one in the app links to the report. */
const FakeQr: React.FC<{ size: number }> = ({ size }) => {
    const n = 25;
    const cell = size / n;
    const finder = (x: number, y: number) => x < 7 && y < 7 || x >= n - 7 && y < 7 || x < 7 && y >= n - 7;
    const cells: React.ReactNode[] = [];
    for (let y = 0; y < n; y++) {
        for (let x = 0; x < n; x++) {
            if (finder(x, y)) continue;
            if (random(`qr-${x}-${y}`) > 0.52) cells.push(<rect key={`${x}-${y}`} x={x * cell} y={y * cell} width={cell} height={cell} rx={cell * 0.2} fill={C.ink} />);
        }
    }
    const square = (x: number, y: number) => (
        <g key={`f${x}${y}`}>
            <rect x={x * cell} y={y * cell} width={7 * cell} height={7 * cell} rx={cell * 1.4} fill={C.ink} />
            <rect x={(x + 1) * cell} y={(y + 1) * cell} width={5 * cell} height={5 * cell} rx={cell} fill="#fff" />
            <rect x={(x + 2) * cell} y={(y + 2) * cell} width={3 * cell} height={3 * cell} rx={cell * 0.6} fill={C.ink} />
        </g>
    );
    return <svg width={size} height={size}>{cells}{square(0, 0)}{square(n - 7, 0)}{square(0, n - 7)}</svg>;
};

export const Report: React.FC = () => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const { u, portrait } = useLayout();
    const card = spring({ frame, fps, config: { damping: 15, stiffness: 110 } });
    const ringP = interpolate(frame, [8, RING_DONE], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.bezier(0.65, 0, 0.35, 1) });
    const band = 7.5 * ringP;
    const done = spring({ frame: frame - RING_DONE, fps, config: { damping: 8, stiffness: 220 } });
    const qr = spring({ frame: frame - (RING_DONE + 22), fps, config: { damping: 12, stiffness: 150 } });
    const R = 118 * u;
    const circ = 2 * Math.PI * R;
    const ex = EXAMINERS.dosen;

    const reportCard = (
        <div style={{ width: portrait ? 960 * u : 840 * u, background: '#fff', borderRadius: 44 * u, padding: 40 * u, boxShadow: `0 ${50 * u}px ${100 * u}px -${40 * u}px rgb(49 46 129 / 0.4), 0 0 0 ${1.5 * u}px rgb(15 23 42 / 0.06)`, transform: `translateY(${(1 - card) * 220 * u}px) rotate(${(1 - card) * 4}deg)`, opacity: card, position: 'relative', overflow: 'hidden' }}>
            <div style={{ position: 'absolute', inset: 0, background: `radial-gradient(60% 70% at 12% 30%, rgb(${ex.rgb} / 0.22), transparent 70%)` }} />
            <div style={{ position: 'relative', display: 'flex', alignItems: 'center', gap: 40 * u }}>
                <div style={{ position: 'relative', width: R * 2 + 30 * u, height: R * 2 + 30 * u, flexShrink: 0, transform: `scale(${1 + done * 0.06 - Math.max(0, done - 1) * 0.06})` }}>
                    <svg width={R * 2 + 30 * u} height={R * 2 + 30 * u} style={{ transform: 'rotate(-90deg)' }}>
                        <circle cx={R + 15 * u} cy={R + 15 * u} r={R} fill="none" stroke="#e2e8f0" strokeWidth={22 * u} />
                        <circle cx={R + 15 * u} cy={R + 15 * u} r={R} fill="none" stroke={ex.color} strokeWidth={22 * u} strokeLinecap="round" strokeDasharray={`${circ} ${circ}`} strokeDashoffset={circ * (1 - (band / 9))} />
                    </svg>
                    <Img src={staticFile(ex.avatar)} style={{ position: 'absolute', left: '50%', top: '50%', width: R * 1.45, height: R * 1.45, transform: 'translate(-50%, -50%)', borderRadius: '50%', objectFit: 'cover', opacity: 0.16 }} />
                    <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
                        <div style={{ fontFamily: DISPLAY, fontWeight: 800, fontSize: 96 * u, letterSpacing: '-0.04em', color: C.ink, lineHeight: 1 }}>{band.toFixed(1)}</div>
                        <div style={{ fontFamily: BODY, fontWeight: 800, fontSize: 22 * u, letterSpacing: '0.18em', color: C.sub }}>BAND</div>
                    </div>
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 10 * u, padding: `${6 * u}px ${16 * u}px`, borderRadius: 999, background: 'rgb(15 23 42 / 0.06)', fontFamily: BODY, fontWeight: 700, fontSize: 22 * u, color: C.ink }}>
                        <span style={{ width: 10 * u, height: 10 * u, borderRadius: '50%', background: C.rose }} /> Panggilan selesai · 07:58
                    </div>
                    <div style={{ fontFamily: DISPLAY, fontWeight: 800, fontSize: 74 * u, letterSpacing: '-0.035em', color: C.ink, marginTop: 14 * u, transform: `scale(${0.6 + 0.4 * Math.min(1, done)})`, transformOrigin: 'left center', opacity: Math.min(1, done * 1.5) }}>Bagus!</div>
                    <div style={{ fontFamily: BODY, fontSize: 26 * u, color: C.sub, marginTop: 4 * u }}>Sidang Skripsi · 4 jawaban</div>
                </div>
            </div>
            <div style={{ position: 'relative', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: `${18 * u}px ${30 * u}px`, marginTop: 34 * u }}>
                {CRITERIA.map((c, i) => {
                    const grow = interpolate(frame, [14 + i * 6, 40 + i * 6], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.out(Easing.cubic) });
                    return (
                        <div key={c.label}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: BODY, fontWeight: 700, fontSize: 25 * u, color: C.ink }}>
                                <span>{c.label}</span><span style={{ fontFamily: DISPLAY, fontWeight: 800 }}>{(c.band * grow).toFixed(1)}</span>
                            </div>
                            <div style={{ marginTop: 8 * u, height: 14 * u, borderRadius: 99, background: '#eef2f7', overflow: 'hidden' }}>
                                <div style={{ height: '100%', width: `${(c.band / 9) * 100 * grow}%`, borderRadius: 99, background: c.color }} />
                            </div>
                        </div>
                    );
                })}
            </div>
            <div style={{ position: 'relative', marginTop: 30 * u, display: 'flex', alignItems: 'center', gap: 16 * u, padding: `${16 * u}px ${20 * u}px`, borderRadius: 22 * u, background: '#eef2ff', fontFamily: BODY, fontSize: 25 * u, color: C.ink, opacity: interpolate(frame, [RING_DONE + 6, RING_DONE + 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) }}>
                <span style={{ width: 46 * u, height: 46 * u, borderRadius: '50%', background: C.indigo, color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: DISPLAY, fontWeight: 800, flexShrink: 0 }}>1</span>
                <span><b>Fokus berikutnya:</b> sebut angka di setiap contoh.</span>
            </div>
        </div>
    );

    const qrCard = (
        <div style={{ display: 'flex', alignItems: 'center', gap: 26 * u, background: C.ink, color: '#fff', borderRadius: 36 * u, padding: `${24 * u}px ${30 * u}px ${24 * u}px ${24 * u}px`, transform: `scale(${qr}) rotate(${(1 - qr) * 12 + (portrait ? 0 : -3)}deg)`, boxShadow: `0 ${30 * u}px ${60 * u}px -${24 * u}px rgb(15 23 42 / 0.6)` }}>
            <div style={{ background: '#fff', padding: 12 * u, borderRadius: 18 * u }}><FakeQr size={150 * u} /></div>
            <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 * u, fontFamily: DISPLAY, fontWeight: 800, fontSize: 38 * u, letterSpacing: '-0.02em' }}><Icon name="qr" size={36 * u} /> Bawa pulang</div>
                <div style={{ fontFamily: BODY, fontSize: 25 * u, opacity: 0.75, marginTop: 6 * u, maxWidth: 300 * u }}>Pindai pakai kamera HP, tanpa akun.</div>
            </div>
        </div>
    );

    return (
        <AbsoluteFill>
            <BrandBackdrop />
            {portrait ? (
                <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', gap: 64 * u }}>
                    <Headline size={104 * u} style={{ textAlign: 'center' }}>
                        <WordsIn text="Rapor" delay={2} /> <Mark delay={14}><WordsIn text="langsung" delay={6} /></Mark><br /><WordsIn text="keluar." delay={10} />
                    </Headline>
                    {reportCard}
                    {qrCard}
                </AbsoluteFill>
            ) : (
                <AbsoluteFill style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 80 * u, padding: `0 ${90 * u}px` }}>
                    <div style={{ width: 760 * u, display: 'flex', flexDirection: 'column', gap: 40 * u }}>
                        <Headline size={118 * u}>
                            <WordsIn text="Rapor" delay={2} /> <Mark delay={14}><WordsIn text="langsung" delay={6} /></Mark><br /><WordsIn text="keluar." delay={10} />
                        </Headline>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 14 * u, opacity: interpolate(frame, [18, 28], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }) }}>
                            <Chip color={C.indigo} u={u}>Band score 0–9</Chip>
                            <Chip color={C.amber} u={u}>Saran tiap jawaban</Chip>
                            <Chip color={C.violet} u={u}>Versi jawaban lebih kuat</Chip>
                        </div>
                        <div style={{ alignSelf: 'flex-start' }}>{qrCard}</div>
                    </div>
                    {reportCard}
                </AbsoluteFill>
            )}
        </AbsoluteFill>
    );
};
