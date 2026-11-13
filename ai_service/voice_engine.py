"""
VOIC Voice Engine: speech-to-text dan text-to-speech yang berjalan 100% di laptop.

- STT: faster-whisper (model Whisper versi CTranslate2). Default "small" (cukup akurat
  untuk Bahasa Indonesia di CPU). Pakai "medium" jika laptop punya GPU NVIDIA.
- TTS: Piper (suara neural ringan, ~0,1-0,3 detik per kalimat di CPU) dengan suara
  Bahasa Indonesia `id_ID-news_tts-medium`.

Keduanya opsional: jika paket/model belum terpasang, service tetap jalan dan memakai
cadangan (edge-tts online / suara browser untuk TTS, pengenalan suara browser untuk STT).
Jalankan `python setup_models.py` sekali saat ada internet untuk mengunduh semua model.
"""

from __future__ import annotations

import asyncio
import os
import tempfile
import threading
import time
import uuid
import wave
from typing import Dict, Optional

BASE_DIR = os.path.dirname(__file__)
AUDIO_OUTPUT_DIR = os.path.join(BASE_DIR, "audio_outputs")
os.makedirs(AUDIO_OUTPUT_DIR, exist_ok=True)
MODELS_DIR = os.getenv("VOIC_MODELS_DIR", os.path.join(BASE_DIR, "models"))

STT_MODEL = os.getenv("VOIC_STT_MODEL", "small")
STT_DEVICE = os.getenv("VOIC_STT_DEVICE", "auto")  # auto | cpu | cuda
STT_COMPUTE = os.getenv("VOIC_STT_COMPUTE", "int8")

PIPER_VOICES = {
    # Satu suara Indonesia tersedia di Piper; pitch/rate dibedakan per persona.
    "id": os.getenv("VOIC_PIPER_VOICE_ID", os.path.join(MODELS_DIR, "piper", "id_ID-news_tts-medium.onnx")),
    "en": os.getenv("VOIC_PIPER_VOICE_EN", os.path.join(MODELS_DIR, "piper", "en_US-lessac-medium.onnx")),
}

# Kecepatan bicara per persona (length_scale Piper: < 1 lebih cepat, > 1 lebih lambat).
PERSONA_PACE = {"hrd": 1.0, "dosen_penguji": 1.08, "investor": 0.92}

_stt_model = None
_stt_lock = threading.Lock()
_stt_error: Optional[str] = None
_piper_voices: Dict[str, object] = {}
_piper_lock = threading.Lock()


# ---------------------------------------------------------------------------
# Speech-to-text
# ---------------------------------------------------------------------------

def stt_model_dir() -> str:
    return os.path.join(MODELS_DIR, "whisper", STT_MODEL)


def stt_available() -> bool:
    """True jika paket terpasang DAN model sudah ada di disk (agar tidak mencoba unduh saat offline)."""
    try:
        import faster_whisper  # noqa: F401
    except ImportError:
        return False
    model_ready = _stt_model is not None or os.path.exists(os.path.join(stt_model_dir(), "model.bin"))
    return model_ready and _stt_error is None


def _load_stt():
    global _stt_model, _stt_error
    if _stt_model is not None:
        return _stt_model
    with _stt_lock:
        if _stt_model is None:
            from faster_whisper import WhisperModel

            if not os.path.exists(os.path.join(stt_model_dir(), "model.bin")):
                raise RuntimeError(f"Model whisper '{STT_MODEL}' belum diunduh. Jalankan: python setup_models.py")
            try:
                _stt_model = WhisperModel(stt_model_dir(), device=STT_DEVICE, compute_type=STT_COMPUTE)
            except Exception as exc:  # model belum diunduh dan tidak ada internet
                _stt_error = str(exc)
                raise
    return _stt_model


def warmup_stt() -> None:
    """Muat model di awal agar giliran pertama peserta tidak lambat."""
    if not stt_available():
        return
    try:
        _load_stt()
    except Exception as exc:
        print(f"STT warmup notice: {exc}")


# Kata-kata yang sering "dihalusinasikan" Whisper saat audio hening/berisik.
_HALLUCINATIONS = {
    "terima kasih.", "terima kasih", "terima kasih telah menonton.", "sampai jumpa.",
    "thank you.", "thanks for watching.", "you", ".", "...",
}


def decode_audio(path: str, sampling_rate: int = 16000):
    """
    Baca file audio (webm/opus dari browser, wav, mp3, ...) menjadi array float32 mono 16 kHz.
    Sengaja tidak memakai faster_whisper.decode_audio: versi 1.2 memanggil av.open(metadata_errors=...),
    parameter yang sudah dihapus di PyAV 19, sehingga SEMUA rekaman gagal dengan
    "open() got an unexpected keyword argument 'metadata_errors'" pada instalasi baru.
    """
    import av
    import numpy as np

    resampler = av.audio.resampler.AudioResampler(format="s16", layout="mono", rate=sampling_rate)
    chunks = []
    with av.open(path, mode="r") as container:
        for frame in container.decode(audio=0):
            for resampled in resampler.resample(frame):
                chunks.append(resampled.to_ndarray().reshape(-1))
        for resampled in resampler.resample(None):  # keluarkan sisa sampel di dalam resampler
            chunks.append(resampled.to_ndarray().reshape(-1))
    if not chunks:
        return np.zeros(0, dtype=np.float32)
    return np.concatenate(chunks).astype(np.float32) / 32768.0


def transcribe(audio_bytes: bytes, language: str = "id", prompt: Optional[str] = None) -> Dict:
    """Ubah rekaman jawaban menjadi teks. `prompt` berisi istilah topik agar ejaan lebih tepat."""
    model = _load_stt()
    suffix = ".webm"
    started = time.time()
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as handle:
        handle.write(audio_bytes)
        path = handle.name
    try:
        segments, info = model.transcribe(
            decode_audio(path),
            language=language if language in ("id", "en") else None,
            beam_size=1,
            vad_filter=True,
            vad_parameters={"min_silence_duration_ms": 400},
            condition_on_previous_text=False,
            initial_prompt=prompt[:200] if prompt else None,
        )
        text = " ".join(segment.text.strip() for segment in segments).strip()
    finally:
        try:
            os.unlink(path)
        except OSError:
            pass

    if text.lower() in _HALLUCINATIONS:
        text = ""

    return {
        "text": text,
        "duration": round(float(getattr(info, "duration", 0.0) or 0.0), 2),
        "latency_ms": int((time.time() - started) * 1000),
        "engine": f"faster-whisper:{STT_MODEL}",
    }


# ---------------------------------------------------------------------------
# Text-to-speech
# ---------------------------------------------------------------------------

def piper_voice_path(language: str) -> Optional[str]:
    path = PIPER_VOICES.get("en" if language == "en" else "id")
    return path if path and os.path.exists(path) else None


def tts_engine(language: str = "id") -> str:
    """Nama mesin TTS yang akan dipakai: piper (lokal), edge (online), atau none."""
    try:
        import piper  # noqa: F401

        if piper_voice_path(language):
            return "piper"
    except ImportError:
        pass
    try:
        import edge_tts  # noqa: F401

        return "edge"
    except ImportError:
        return "none"


def _load_piper(path: str):
    voice = _piper_voices.get(path)
    if voice is None:
        with _piper_lock:
            voice = _piper_voices.get(path)
            if voice is None:
                from piper import PiperVoice

                voice = PiperVoice.load(path)
                _piper_voices[path] = voice
    return voice


def synthesize_piper(text: str, role_type: str, language: str, output_dir: str) -> Optional[str]:
    """Sintesis WAV dengan Piper. Mengembalikan nama file, atau None jika Piper tidak tersedia."""
    path = piper_voice_path(language)
    if not path or not text.strip():
        return None
    try:
        voice = _load_piper(path)
    except Exception as exc:
        print(f"Piper load notice: {exc}")
        return None

    filename = f"tts_{int(time.time() * 1000)}_{uuid.uuid4().hex[:6]}.wav"
    output_path = os.path.join(output_dir, filename)
    pace = PERSONA_PACE.get(role_type, 1.0)

    try:
        with wave.open(output_path, "wb") as wav_file:
            if hasattr(voice, "synthesize_wav"):  # piper-tts >= 1.3
                try:
                    from piper import SynthesisConfig

                    voice.synthesize_wav(text, wav_file, syn_config=SynthesisConfig(length_scale=pace))
                except ImportError:
                    voice.synthesize_wav(text, wav_file)
            else:  # piper-tts 1.2
                voice.synthesize(text, wav_file, length_scale=pace)
    except Exception as exc:
        print(f"Piper synth notice: {exc}")
        try:
            os.unlink(output_path)
        except OSError:
            pass
        return None

    return filename


# Suara online (edge-tts, suara neural Microsoft). Jauh lebih alami daripada Piper: dipakai lebih dulu
# jika aplikasi meminta engine="edge" (mode cepat online), selain itu hanya cadangan jika Piper tidak ada.
EDGE_VOICES = {
    "id": {"dosen_penguji": ("id-ID-ArdiNeural", "-4Hz", "-4%"), "hrd": ("id-ID-GadisNeural", "+0Hz", "+0%"), "investor": ("id-ID-ArdiNeural", "-1Hz", "+10%")},
    "en": {"dosen_penguji": ("en-US-GuyNeural", "-5Hz", "-5%"), "hrd": ("en-US-JennyNeural", "+0Hz", "-4%"), "investor": ("en-US-BrianNeural", "+0Hz", "+10%")},
}


async def synthesize_edge(text: str, role_type: str, language: str, output_dir: str) -> Optional[str]:
    try:
        import edge_tts
    except ImportError:
        return None
    voice, pitch, rate = EDGE_VOICES["en" if language == "en" else "id"].get(role_type, EDGE_VOICES["id"]["hrd"])
    filename = f"tts_{int(time.time() * 1000)}_{uuid.uuid4().hex[:6]}.mp3"
    try:
        await edge_tts.Communicate(text, voice, rate=rate, pitch=pitch).save(os.path.join(output_dir, filename))
        return filename
    except Exception as exc:
        print(f"edge-tts notice: {exc}")
        return None


async def synthesize(text: str, role_type: str = "hrd", language: str = "id", output_dir: str = AUDIO_OUTPUT_DIR,
                     prefer: Optional[str] = None) -> Optional[str]:
    """TTS: Piper (offline) lalu edge-tts (online); prefer="edge" membalik urutannya. None = biarkan browser bicara."""
    if prefer == "edge":
        filename = await synthesize_edge(text, role_type, language, output_dir)
        if filename:
            return filename
        return await asyncio.to_thread(synthesize_piper, text, role_type, language, output_dir)
    filename = await asyncio.to_thread(synthesize_piper, text, role_type, language, output_dir)
    if filename:
        return filename
    return await synthesize_edge(text, role_type, language, output_dir)


def warmup_tts() -> None:
    for language in ("id", "en"):
        path = piper_voice_path(language)
        if path:
            try:
                _load_piper(path)
            except Exception as exc:
                print(f"Piper warmup notice: {exc}")


def status() -> Dict:
    return {
        "stt": {
            "available": stt_available(),
            "engine": f"faster-whisper:{STT_MODEL}" if stt_available() else None,
            "loaded": _stt_model is not None,
            "error": _stt_error,
            "local": True,
        },
        "tts": {
            "engine": tts_engine("id"),
            "local": tts_engine("id") == "piper",
        },
    }
