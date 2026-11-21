import { getWaveformPortion, useAudioData } from '@remotion/media-utils';
import { staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import timeline from './timeline.json';

export type VoiceKey = keyof typeof timeline.voices;

export const cue = (key: VoiceKey) => timeline.voices[key];

/**
 * How loud a voice clip is right now (0..1), so rings and sound waves move with the real voice.
 * `sceneStart` converts the frame inside a <Sequence> back to the absolute frame of the cue.
 */
export const useVoiceLevel = (key: VoiceKey, sceneStart: number) => {
    const frame = useCurrentFrame();
    const { fps } = useVideoConfig();
    const data = useAudioData(staticFile(`audio/${key}.wav`));
    const v = timeline.voices[key];
    const local = frame + sceneStart - v.at;
    if (!data || local < 0 || local > v.frames) {
        return 0;
    }
    const [bar] = getWaveformPortion({ audioData: data, startTimeInSeconds: local / fps, durationInSeconds: 2 / fps, numberOfSamples: 1, normalize: false });
    return Math.min(1, (bar?.amplitude ?? 0) * 4);
};

/** True while the clip is playing (with a small tail so bubbles do not vanish on the last syllable). */
export const isSpeaking = (key: VoiceKey, absoluteFrame: number, tail = 8) => {
    const v = timeline.voices[key];
    return absoluteFrame >= v.at && absoluteFrame <= v.at + v.frames + tail;
};
