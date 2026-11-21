"""
Builds every sound in the teaser (run once, needs internet for the voices):

  - narrator and examiner voices with edge-tts, in the same voices and persona settings the app uses,
    passed through the app's own pronunciation lexicon (backend/ai_service/pronunciation.json)
  - an original 120 BPM music bed and sound effects, synthesised here (no third-party music, no licence issues)
  - src/timeline.json: scene starts and voice cues computed from the real length of every voice clip,
    and the music is written to that timeline (drop on the logo reveal, final hit on the call to action)

usage (from teaser/): python scripts/make_audio.py  (or: npm run audio)
"""

import asyncio
import json
import os
import sys
import wave

import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
OUT = os.path.join(ROOT, "public", "audio")
sys.path.insert(0, os.path.join(ROOT, "..", "backend", "ai_service"))
from pronunciation import speech_text  # noqa: E402  (app lexicon: "interview" -> "interviu", ...)

SR = 44100
BPM = 120
BEAT = 60 / BPM
FPS = 30
# TOTAL and DROP are set by build_timeline() from the real length of the voice clips.
TOTAL = 0.0
DROP = 0.0

# Every voice speaks Indonesian natively. The multilingual en-US voices (Emma, Ava, Brian, Andrew) guess the
# language per sentence and read short Indonesian lines with a foreign accent: Whisper heard "Grogi, pas
# interview?" as French and "latihan dulu aja" as Tagalog. So:
#   1. scripts/make_voices_omnivoice.py (OmniVoice, the engine behind VoiceStudio) clones each persona from a
#      short Indonesian reference spoken in character, and writes voices/<key>.wav. This is the voice used.
#   2. If a clip is missing there, this script falls back to the persona's native Indonesian edge-tts voice.
# Personas: (base Indonesian voice, edge-tts rate, edge-tts pitch, reference line spoken in character,
#            semitone shift of the reference so the four voices sound like four different people, speed).
PERSONAS = {
    "narator": ("id-ID-GadisNeural", "+0%", "+0Hz",
                "Halo semuanya! Hari ini aku mau cerita sedikit soal latihan sidang kemarin.", 0.0, 1.0),
    "hrd": ("id-ID-GadisNeural", "+8%", "+12Hz",
            "Wah, seru banget! Aku suka banget sama cerita kamu, ayo lanjut lagi dong.", 2.5, 1.05),
    "dosen": ("id-ID-ArdiNeural", "-10%", "-6Hz",
              "Baik, Saudara. Coba jelaskan dulu metodologi penelitian Anda, singkat dan jelas.", -2.5, 0.92),
    "investor": ("id-ID-ArdiNeural", "+12%", "+4Hz",
                 "Oke, langsung aja ya. Pasar kamu seberapa besar, terus kapan mulai untung?", 1.5, 1.1),
}

# key: (persona, on-screen text, spoken text if it differs, e.g. a pause "..." or a casual particle).
LINES = {
    "n_hook1": ("narator", "Deg-degan mau sidang?", "Deg-degan... mau sidang?"),
    "n_hook2": ("narator", "Grogi pas interview?", "Grogi, pas interview?"),
    "n_hook3": ("narator", "Ditanya investor, blank?", "Ditanya investor... blengk?"),
    "n_reveal": ("narator", "Tenang. Latihan dulu, bareng penguji kami.", "Tenang, latihan dulu aja bareng penguji kami."),
    "hrd": ("hrd", "Wah, seru banget! Ceritain dong."),
    "dosen": ("dosen", "Datanya. Datanya dulu yang saya tanya."),
    "investor": ("investor", "Oke, oke. Tapi cuannya mana?"),
    "n_call": ("narator", "Jawab pakai suara, penguji nanya balik.", "Kamu jawab pakai suara, pengujinya nanya balik."),
    "call_hmm": ("dosen", "Hmm, begitu ya."),  # "Hmm, baik." is heard as "bike" even in a native voice
    "call_question": ("dosen", "Delapan puluh tujuh persen. Dibanding apa?"),
    "n_report": ("narator", "Selesai, rapor langsung keluar.", "Selesai? Rapornya langsung keluar."),
    "n_cta": ("narator", "Berani ditelepon penguji kami? Coba gratis di booth Vois.",
              "Berani ditelepon penguji kami? Yuk, cobain gratis di booth Vois!"),
}
VOICES_DIR = os.path.join(ROOT, "voices")  # OmniVoice clips, one per LINES key


def spoken(key):
    line = LINES[key]
    return line[2] if len(line) > 2 else line[1]


async def make_voices():
    import edge_tts

    for key, (persona, *_) in LINES.items():
        if os.path.exists(os.path.join(VOICES_DIR, f"{key}.wav")):
            print("voice", key, "(OmniVoice)")
            continue
        voice, rate, pitch = PERSONAS[persona][:3]
        path = os.path.join(OUT, f"{key}.mp3")
        for attempt in range(4):  # the online voice server stalls now and then
            try:
                await asyncio.wait_for(edge_tts.Communicate(speech_text(spoken(key), "id"), voice, rate=rate, pitch=pitch).save(path), 20)
                break
            except Exception as exc:
                print(f"  retry {key} ({type(exc).__name__})")
                await asyncio.sleep(1.5)
        else:
            raise SystemExit(f"Voice {key} failed four times; check the internet connection and run again.")
        print("voice", key, "(edge-tts fallback)")


def duration(path):
    import av

    with av.open(path) as container:
        return round(float(container.duration) / 1_000_000, 3)


# --------------------------------------------------------------------------------------------
# Synthesis helpers
# --------------------------------------------------------------------------------------------

def t_axis(seconds):
    return np.arange(int(seconds * SR)) / SR


def env(n, attack=0.005, decay=0.2):
    t = np.arange(n) / SR
    a = np.clip(t / max(attack, 1e-4), 0, 1)
    return a * np.exp(-t / decay)


def lowpass(x, cutoff):
    alpha = 1 - np.exp(-2 * np.pi * cutoff / SR)
    y = np.empty_like(x)
    acc = 0.0
    for i, v in enumerate(x):  # one-pole filter; fine for these short buffers
        acc += alpha * (v - acc)
        y[i] = acc
    return y


def lowpass_fast(x, cutoff):
    # Same one-pole filter, vectorised via scipy-free recursion in chunks (numpy only).
    alpha = 1 - np.exp(-2 * np.pi * cutoff / SR)
    b = alpha
    y = np.zeros_like(x)
    prev = 0.0
    chunk = 4096
    decay = (1 - alpha) ** np.arange(1, chunk + 1)
    for start in range(0, len(x), chunk):
        seg = x[start:start + chunk] * b
        # y[n] = sum_k seg[k] (1-a)^(n-k)  + prev (1-a)^(n+1)
        conv = np.convolve(seg, (1 - alpha) ** np.arange(len(seg)))[: len(seg)]
        out = conv + prev * decay[: len(seg)]
        y[start:start + chunk] = out
        prev = out[-1]
    return y


def highpass(x, cutoff):
    return x - lowpass_fast(x, cutoff)


def note(freq_name):
    names = {"C": -9, "C#": -8, "D": -7, "D#": -6, "E": -5, "F": -4, "F#": -3, "G": -2, "G#": -1, "A": 0, "A#": 1, "B": 2}
    name, octave = freq_name[:-1], int(freq_name[-1])
    return 440.0 * 2 ** ((names[name] + (octave - 4) * 12) / 12)


def place(track, sound, at, gain=1.0):
    start = int(at * SR)
    end = min(len(track), start + len(sound))
    if start < len(track):
        track[start:end] += sound[: end - start] * gain


def kick():
    t = t_axis(0.35)
    freq = 45 + 110 * np.exp(-t / 0.045)
    phase = 2 * np.pi * np.cumsum(freq) / SR
    return np.sin(phase) * env(len(t), 0.002, 0.16) * 0.95


def clap(rng):
    n = int(0.22 * SR)
    noise = rng.standard_normal(n)
    body = highpass(lowpass_fast(noise, 2600), 700)
    e = env(n, 0.002, 0.07)
    for d in (0.0, 0.011, 0.022):  # three quick hits, like a real clap
        shift = int(d * SR)
        e[shift:] += env(n - shift, 0.001, 0.012) * 0.6
    return body * e * 0.5


def hat(rng, length=0.05):
    n = int(length * SR)
    return highpass(rng.standard_normal(n), 7000) * env(n, 0.001, length / 3) * 0.22


def pad_chord(freqs, seconds):
    t = t_axis(seconds)
    out = np.zeros_like(t)
    for f in freqs:
        for detune in (-0.12, 0.0, 0.12):  # detuned saws -> wide, soft pad
            ff = f * 2 ** (detune / 12)
            out += 2 * ((t * ff) % 1.0) - 1
    out = lowpass_fast(out / (len(freqs) * 3), 1400)
    fade = np.minimum(1, np.minimum(t / 0.25, (seconds - t) / 0.3))
    return out * np.clip(fade, 0, 1) * 0.32


def bass_note(freq, seconds):
    t = t_axis(seconds)
    s = np.sin(2 * np.pi * freq * t) + 0.35 * np.sin(4 * np.pi * freq * t)
    s = np.tanh(1.6 * s)
    return s * env(len(t), 0.004, seconds * 0.9) * 0.42


def riser(rng, seconds):
    n = int(seconds * SR)
    t = np.arange(n) / SR
    noise = rng.standard_normal(n)
    out = np.zeros(n)
    steps = 24
    for i in range(steps):  # sweep the band upward in steps
        a, b = i * n // steps, (i + 1) * n // steps
        cutoff = 400 + 9000 * (i / steps) ** 2
        seg = lowpass_fast(noise[a:b], cutoff)
        out[a:b] = seg
    return out * (t / seconds) ** 2 * 0.35


def impact(rng):
    n = int(1.6 * SR)
    noise = lowpass_fast(rng.standard_normal(n), 5000) * env(n, 0.001, 0.45) * 0.35
    boom = kick() * 1.2
    noise[: len(boom)] += boom
    return noise


def whoosh(rng):
    n = int(0.55 * SR)
    t = np.arange(n) / SR
    noise = rng.standard_normal(n)
    out = np.zeros(n)
    steps = 12
    for i in range(steps):
        a, b = i * n // steps, (i + 1) * n // steps
        mid = 1 - abs(i / steps - 0.6) / 0.6
        out[a:b] = lowpass_fast(noise[a:b], 600 + 5000 * max(mid, 0))
    shape = np.sin(np.pi * np.clip(t / (n / SR), 0, 1)) ** 2
    return out * shape * 0.5


def pop():
    t = t_axis(0.12)
    freq = 900 * np.exp(-t / 0.05) + 380
    return np.sin(2 * np.pi * np.cumsum(freq) / SR) * env(len(t), 0.001, 0.035) * 0.5


def ding():
    t = t_axis(1.2)
    tone = sum(np.sin(2 * np.pi * f * t) * g for f, g in ((note("E6"), 1), (note("B6"), 0.5), (note("E7"), 0.25)))
    return tone * env(len(t), 0.002, 0.35) * 0.28


def write_wav(path, data, stereo_width=0.0):
    data = np.asarray(data, dtype=np.float64)
    peak = np.max(np.abs(data)) or 1.0
    data = data / peak * 0.89
    if stereo_width:
        delay = int(0.012 * SR)
        right = np.concatenate([np.zeros(delay), data[:-delay]])
        left = data
        frames = np.stack([left, right * (1 - stereo_width) + left * stereo_width], axis=1)
    else:
        frames = np.stack([data, data], axis=1)
    pcm = (np.clip(frames, -1, 1) * 32767).astype("<i2")
    with wave.open(path, "wb") as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(SR)
        w.writeframes(pcm.tobytes())


def make_music(end_beat):
    rng = np.random.default_rng(2026)
    total = np.zeros(int(TOTAL * SR) + SR)
    drums = np.zeros_like(total)
    # A minor feel: Am - F - C - G, one chord per bar (2 s).
    progression = [("A3", "C4", "E4"), ("F3", "A3", "C4"), ("C4", "E4", "G4"), ("G3", "B3", "D4")]
    roots = ["A1", "F1", "C2", "G1"]
    bar = 4 * BEAT

    # Intro (hook): pad + ticking hats, then a riser into the drop.
    for b in range(int(DROP // bar) + 1):
        place(total, pad_chord([note(n) for n in progression[b % 4]], bar), b * bar, 0.8)
    for i in range(int(DROP / (BEAT / 2))):
        place(drums, hat(rng, 0.03), i * BEAT / 2, 0.55 if i % 2 else 0.3)
    place(total, riser(rng, 2.0), DROP - 2.0, 1.0)

    # Main section: drop to the final hit (end_beat, just after the CTA starts).
    place(total, impact(rng), DROP, 1.0)
    b = 0
    t = DROP
    while t < end_beat:
        chord = progression[b % 4]
        place(total, pad_chord([note(n) for n in chord], bar), t, 1.0)
        for e in range(8):  # eighth-note bass pulse
            place(total, bass_note(note(roots[b % 4]), BEAT / 2 * 0.95), t + e * BEAT / 2, 0.9 if e % 2 == 0 else 0.6)
        for k in range(4):
            beat_t = t + k * BEAT
            if beat_t >= end_beat:
                break
            place(drums, kick(), beat_t, 1.0)
            if k in (1, 3):
                place(drums, clap(rng), beat_t, 0.9)
            place(drums, hat(rng), beat_t + BEAT / 2, 0.8)
            place(drums, hat(rng, 0.025), beat_t + BEAT / 4, 0.35)
            place(drums, hat(rng, 0.025), beat_t + 3 * BEAT / 4, 0.35)
        t += bar
        b += 1

    # Outro: last chord rings out on a final hit.
    place(total, impact(rng), end_beat, 0.9)
    place(total, pad_chord([note(n) for n in ("A3", "C4", "E4", "A4")], 2.0), end_beat, 1.1)

    # Sidechain: duck the pad and bass under every kick for the pumping feel.
    duck = np.ones_like(total)
    t = DROP
    while t < end_beat:
        s = int(t * SR)
        n = int(0.28 * SR)
        curve = 1 - 0.55 * np.exp(-np.arange(n) / SR / 0.09)
        duck[s:s + n] = np.minimum(duck[s:s + n], curve[: len(duck[s:s + n])])
        t += BEAT
    mix = total * duck + drums
    mix = np.tanh(mix * 1.1)  # gentle glue
    # Fade the tail.
    fade_n = int(1.0 * SR)
    mix[int(TOTAL * SR) - fade_n:int(TOTAL * SR)] *= np.linspace(1, 0, fade_n)
    mix = mix[: int(TOTAL * SR)]
    write_wav(os.path.join(OUT, "music.wav"), mix, stereo_width=0.35)
    print("music", round(len(mix) / SR, 2), "s")


def trim_to_wav(key):
    """Decode a voice clip, cut the silence edge-tts adds around it, save it as WAV; returns seconds."""
    import av

    omni = os.path.join(VOICES_DIR, f"{key}.wav")
    src = omni if os.path.exists(omni) else os.path.join(OUT, f"{key}.mp3")
    chunks = []
    with av.open(src) as container:
        resampler = av.audio.resampler.AudioResampler(format="flt", layout="mono", rate=SR)
        for frame in container.decode(audio=0):
            for out in resampler.resample(frame):
                chunks.append(out.to_ndarray().reshape(-1))
        for out in resampler.resample(None):
            chunks.append(out.to_ndarray().reshape(-1))
    data = np.concatenate(chunks).astype(np.float64)
    loud = np.where(np.abs(data) > 0.02)[0]
    start = max(0, loud[0] - int(0.04 * SR)) if len(loud) else 0
    end = min(len(data), loud[-1] + int(0.12 * SR)) if len(loud) else len(data)
    data = data[start:end]
    write_wav(os.path.join(OUT, f"{key}.wav"), data)
    if src != omni:
        os.unlink(src)
    return round(len(data) / SR, 3)


def q(seconds, step=0.5):
    """Round up to the beat grid, so scene cuts land on the music."""
    return float(np.ceil(seconds / step - 1e-9) * step)


def build_timeline(d):
    """Scene starts and voice cues in seconds, from the measured voice lengths `d`."""
    cues = {}
    t = 0.35
    for key in ("n_hook1", "n_hook2", "n_hook3"):
        cues[key] = t
        t += max(d[key] + 0.3, 1.5)
    reveal = q(t + 0.1)
    cues["n_reveal"] = reveal + 0.5
    examiners = q(cues["n_reveal"] + d["n_reveal"] + 0.4)
    t = examiners + 0.9
    for key in ("hrd", "dosen", "investor"):
        cues[key] = t
        t += max(d[key] + 0.35, 2.3)
    call = q(t + 0.1)
    cues["n_call"] = call + 0.35
    cues["call_hmm"] = cues["n_call"] + d["n_call"] + 0.25
    cues["call_question"] = cues["call_hmm"] + d["call_hmm"] + 0.1
    report = q(cues["call_question"] + d["call_question"] + 0.5)
    cues["n_report"] = report + 0.35
    cta = q(max(cues["n_report"] + d["n_report"] + 1.2, report + 4.5))
    cues["n_cta"] = cta + 0.6
    total = q(cues["n_cta"] + d["n_cta"] + 1.8)
    scenes = {"hook": 0.0, "reveal": reveal, "examiners": examiners, "call": call, "report": report, "cta": cta, "end": total}
    return scenes, cues


def main():
    global TOTAL, DROP
    os.makedirs(OUT, exist_ok=True)
    asyncio.run(make_voices())
    lengths = {key: trim_to_wav(key) for key in LINES}
    scenes, cues = build_timeline(lengths)
    TOTAL, DROP = scenes["end"], scenes["reveal"]
    rng = np.random.default_rng(7)
    write_wav(os.path.join(OUT, "whoosh.wav"), whoosh(rng), 0.5)
    write_wav(os.path.join(OUT, "pop.wav"), pop())
    write_wav(os.path.join(OUT, "ding.wav"), ding(), 0.4)
    make_music(scenes["cta"] + 2.0)
    timeline = {
        "fps": FPS,
        "durationInFrames": int(round(TOTAL * FPS)),
        "scenes": {k: int(round(v * FPS)) for k, v in scenes.items()},
        "voices": {k: {"at": int(round(cues[k] * FPS)), "frames": int(np.ceil(lengths[k] * FPS)), "text": LINES[k][1]} for k in LINES},
    }
    with open(os.path.join(ROOT, "src", "timeline.json"), "w", encoding="utf-8") as f:
        json.dump(timeline, f, indent=2, ensure_ascii=False)
    print(json.dumps({"scenes_s": scenes, "lengths_s": lengths}, indent=2))


if __name__ == "__main__":
    main()
