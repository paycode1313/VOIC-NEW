"""
Teaser voices with OmniVoice (k2-fsa/OmniVoice, the default engine of VoiceStudio), all in native Indonesian.

Each persona in make_audio.PERSONAS is cloned from a short reference: a native Indonesian edge-tts voice
(Gadis or Ardi) saying a line in character, shifted by a few semitones so the four voices sound like four
different people. Cloning from an Indonesian reference keeps the Indonesian pronunciation; OmniVoice adds
its own, more natural intonation. (Voice design without a reference was tried and rejected: it is trained
on English and Chinese only, and Whisper heard "Grogi, pas interview?" as English.)

Needs its own Python environment (PyTorch, ~3.3 GB model download on first run), not the app's:
    python -m venv %USERPROFILE%\\omnivoice-env
    %USERPROFILE%\\omnivoice-env\\Scripts\\python -m pip install torch torchaudio --index-url https://download.pytorch.org/whl/cpu
    %USERPROFILE%\\omnivoice-env\\Scripts\\python -m pip install omnivoice edge-tts numpy==<same as the system Python>
    (Windows Smart App Control blocks the DLLs of a numpy release it has not seen yet; the system's version passes.)

usage (from teaser/):
    %USERPROFILE%\\omnivoice-env\\Scripts\\python scripts/make_voices_omnivoice.py            # missing clips only
    %USERPROFILE%\\omnivoice-env\\Scripts\\python scripts/make_voices_omnivoice.py hrd n_cta  # redo these keys
    %USERPROFILE%\\omnivoice-env\\Scripts\\python scripts/make_voices_omnivoice.py --seeds 2,3 hrd  # other takes, kept in voices/_takes/
then: npm run audio  (picks up voices/*.wav and rebuilds the timeline and music)

CPU only takes ~2-6 minutes per line on a laptop.
"""

import asyncio
import os
import sys
import tempfile
import time

import numpy as np
import soundfile as sf
import torch

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from make_audio import LINES, PERSONAS, VOICES_DIR, speech_text, spoken  # noqa: E402

SR = 24000  # OmniVoice output rate


def shift_semitones(audio, semitones):
    """Resample so pitch and timbre move together (a different-sounding speaker, not a pitch effect)."""
    if not semitones:
        return audio
    factor = 2 ** (semitones / 12)
    idx = np.arange(0, len(audio) - 1, factor)
    return np.interp(idx, np.arange(len(audio)), audio).astype(np.float32)


async def reference(persona):
    import edge_tts

    voice, rate, pitch, text, semitones, _ = PERSONAS[persona]
    with tempfile.TemporaryDirectory() as tmp:
        path = os.path.join(tmp, "ref.mp3")
        for _ in range(6):
            try:
                await asyncio.wait_for(edge_tts.Communicate(text, voice, rate=rate, pitch=pitch).save(path), 20)
                break
            except Exception:
                await asyncio.sleep(2)
        else:
            raise SystemExit(f"Reference for {persona} failed; check the internet connection.")
        audio, sr = sf.read(path, dtype="float32")
    if audio.ndim > 1:
        audio = audio.mean(axis=1)
    if sr != SR:
        audio = np.interp(np.arange(0, len(audio), sr / SR), np.arange(len(audio)), audio).astype(np.float32)
    return shift_semitones(audio, semitones), text


def main():
    args = sys.argv[1:]
    seeds = [0]
    if "--seeds" in args:
        i = args.index("--seeds")
        seeds = [int(x) for x in args[i + 1].split(",")]
        del args[i:i + 2]
    os.makedirs(VOICES_DIR, exist_ok=True)
    keys = args or [k for k in LINES if not os.path.exists(os.path.join(VOICES_DIR, f"{k}.wav"))]
    unknown = [k for k in keys if k not in LINES]
    if unknown:
        raise SystemExit(f"Unknown keys: {unknown}. Known: {list(LINES)}")
    if not keys:
        print("All clips exist; pass keys to redo them.")
        return

    from omnivoice import OmniVoice

    torch.set_num_threads(os.cpu_count() or 4)
    t0 = time.time()
    model = OmniVoice.from_pretrained("k2-fsa/OmniVoice", device_map="cpu", dtype=torch.float32)
    print(f"model loaded in {time.time() - t0:.0f}s", flush=True)

    prompts = {}
    for key in keys:
        persona = LINES[key][0]
        if persona not in prompts:
            audio, text = asyncio.run(reference(persona))
            sf.write(os.path.join(VOICES_DIR, f"_ref_{persona}.wav"), audio, SR)
            prompts[persona] = model.create_voice_clone_prompt(ref_audio=(torch.from_numpy(audio).unsqueeze(0), SR), ref_text=text)
        text = speech_text(spoken(key), "id")  # app lexicon: "AI" -> "ei ai", "interview" -> "interviu"
        for seed in seeds:
            torch.manual_seed(seed * 1000 + list(LINES).index(key))
            t = time.time()
            out = model.generate(text=text, language="id", voice_clone_prompt=prompts[persona],
                                 speed=PERSONAS[persona][5], num_step=32)
            os.makedirs(os.path.join(VOICES_DIR, "_takes"), exist_ok=True)
            sf.write(os.path.join(VOICES_DIR, "_takes", f"{key}_s{seed}.wav"), out[0], SR)
            sf.write(os.path.join(VOICES_DIR, f"{key}.wav"), out[0], SR)
            print(f"{key} ({persona}, seed {seed}): {len(out[0]) / SR:.1f}s audio in {time.time() - t:.0f}s", flush=True)


if __name__ == "__main__":
    main()
