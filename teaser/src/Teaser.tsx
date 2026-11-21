import React from 'react';
import { AbsoluteFill, Audio, interpolate, Sequence, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { C } from './theme';
import { Call } from './scenes/Call';
import { Cta } from './scenes/Cta';
import { Examiners } from './scenes/Examiners';
import { Hook } from './scenes/Hook';
import { Report, RING_DONE } from './scenes/Report';
import { Reveal } from './scenes/Reveal';
import timeline from './timeline.json';
import { VoiceKey } from './voice';

const S = timeline.scenes;
const VOICES = Object.entries(timeline.voices) as [VoiceKey, { at: number; frames: number }][];
const WIPE = 18; // frames of the colour band that hides each cut

/** A diagonal band in the three examiner colours sweeping across the cut. */
const Wipe: React.FC<{ colors: string[] }> = ({ colors }) => {
    const frame = useCurrentFrame();
    const { width } = useVideoConfig();
    const x = interpolate(frame, [0, WIPE], [width * 1.05, -width * 1.75]);
    return (
        <AbsoluteFill style={{ pointerEvents: 'none', overflow: 'hidden', zIndex: 50 }}>
            <div style={{ position: 'absolute', top: '-20%', bottom: '-20%', left: x, width: width * 1.7, transform: 'skewX(-16deg)', display: 'flex' }}>
                {colors.map((c) => <div key={c} style={{ flex: 1, background: c }} />)}
            </div>
        </AbsoluteFill>
    );
};

/** Music ducks under every voice line, so the words stay clear. */
const musicVolume = (f: number) => {
    let distance = Infinity;
    for (const [, v] of VOICES) {
        if (f >= v.at && f <= v.at + v.frames) return 0.2;
        distance = Math.min(distance, f < v.at ? v.at - f : f - (v.at + v.frames));
    }
    const fadeIn = interpolate(f, [0, 10], [0, 1], { extrapolateRight: 'clamp' });
    return fadeIn * interpolate(distance, [0, 10], [0.2, 0.55], { extrapolateRight: 'clamp' });
};

export const Teaser: React.FC = () => {
    const scenes: [keyof typeof S, React.FC][] = [['hook', Hook], ['reveal', Reveal], ['examiners', Examiners], ['call', Call], ['report', Report], ['cta', Cta]];
    const cuts: { at: number; colors: string[] }[] = [
        { at: S.examiners, colors: [C.amber, C.indigo, C.violet] },
        { at: S.call, colors: [C.indigo, '#0ea5e9', C.ink] },
        { at: S.report, colors: [C.violet, C.indigo, C.amber] },
        { at: S.cta, colors: [C.amber, C.emerald, C.ink] },
    ];
    return (
        <AbsoluteFill style={{ background: C.paper }}>
            {scenes.map(([key, Scene], i) => {
                const next = i < scenes.length - 1 ? S[scenes[i + 1][0]] : S.end;
                return (
                    // isolation: a z-index inside a scene (e.g. highlighter marks) must never rise above the wipe.
                    <Sequence key={key} from={S[key]} durationInFrames={next - S[key]} name={key} style={{ isolation: 'isolate' }}>
                        <Scene />
                    </Sequence>
                );
            })}
            {cuts.map((cut) => (
                <Sequence key={cut.at} from={cut.at - Math.round(WIPE * 0.45)} durationInFrames={WIPE} name="wipe">
                    <Wipe colors={cut.colors} />
                </Sequence>
            ))}

            {/* Sound */}
            <Audio src={staticFile('audio/music.wav')} volume={musicVolume} />
            {VOICES.map(([key, v]) => (
                <Sequence key={key} from={v.at} name={`voice ${key}`}>
                    <Audio src={staticFile(`audio/${key}.wav`)} volume={1} />
                </Sequence>
            ))}
            {[S.reveal - 12, ...cuts.map((c) => c.at - 10)].map((at) => (
                <Sequence key={`whoosh${at}`} from={Math.max(0, at)} name="whoosh">
                    <Audio src={staticFile('audio/whoosh.wav')} volume={0.55} />
                </Sequence>
            ))}
            {[timeline.voices.hrd.at, timeline.voices.dosen.at, timeline.voices.investor.at, timeline.voices.call_hmm.at].map((at) => (
                <Sequence key={`pop${at}`} from={at - 3} name="pop">
                    <Audio src={staticFile('audio/pop.wav')} volume={0.45} />
                </Sequence>
            ))}
            <Sequence from={S.report + RING_DONE} name="ding">
                <Audio src={staticFile('audio/ding.wav')} volume={0.6} />
            </Sequence>
        </AbsoluteFill>
    );
};
