"""
Unduh semua model AI lokal VOIC sekali (butuh internet), setelah itu pameran bisa 100% offline.

    python setup_models.py                  # laptop CPU biasa
    python setup_models.py --gpu            # laptop dengan GPU NVIDIA (model lebih besar & akurat)
    python setup_models.py --check          # hanya cek apa yang sudah siap

Yang diunduh:
  1. Otak AI (Ollama)  : qwen2.5:3b (CPU) atau qwen2.5:7b (GPU), lalu dibuat model "voic-coach"
  2. Telinga (STT)     : faster-whisper "small" (CPU) atau "large-v3-turbo" (GPU)
  3. Suara (TTS)       : Piper id_ID-news_tts-medium (+ en_US-lessac-medium untuk mode Inggris)
  4. Mata (kamera)     : MediaPipe Face Landmarker, disalin ke public/vendor/mediapipe
"""

from __future__ import annotations

import argparse
import os
import shutil
import subprocess
import sys
from pathlib import Path

BASE = Path(__file__).resolve().parent
MODELS = BASE / "models"
PIPER_VOICES = ["id_ID-news_tts-medium", "en_US-lessac-medium"]
PUBLIC_MEDIAPIPE = BASE.parent / "public" / "vendor" / "mediapipe"
MEDIAPIPE_VERSION = "0.10.14"
MEDIAPIPE_FILES = {
    "vision_bundle.mjs": f"https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@{MEDIAPIPE_VERSION}/vision_bundle.mjs",
    "wasm/vision_wasm_internal.js": f"https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@{MEDIAPIPE_VERSION}/wasm/vision_wasm_internal.js",
    "wasm/vision_wasm_internal.wasm": f"https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@{MEDIAPIPE_VERSION}/wasm/vision_wasm_internal.wasm",
    "wasm/vision_wasm_nosimd_internal.js": f"https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@{MEDIAPIPE_VERSION}/wasm/vision_wasm_nosimd_internal.js",
    "wasm/vision_wasm_nosimd_internal.wasm": f"https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@{MEDIAPIPE_VERSION}/wasm/vision_wasm_nosimd_internal.wasm",
    "face_landmarker.task": "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
}


def step(title: str) -> None:
    print(f"\n=== {title} ===")


def setup_ollama(gpu: bool, check: bool) -> bool:
    step("Otak AI (Ollama)")
    if shutil.which("ollama") is None:
        print("✗ Ollama belum terpasang. Unduh dari https://ollama.com/download lalu jalankan ulang skrip ini.")
        return False
    base = "qwen2.5:7b" if gpu else "qwen2.5:3b"
    listed = subprocess.run(["ollama", "list"], capture_output=True, text=True).stdout
    if check:
        ok = "voic-coach" in listed
        print(("✓" if ok else "✗") + " model voic-coach " + ("siap" if ok else "belum dibuat"))
        return ok
    if base not in listed:
        print(f"Mengunduh {base} (beberapa GB, sekali saja)…")
        subprocess.run(["ollama", "pull", base], check=True)
    modelfile = (BASE / "Modelfile").read_text(encoding="utf-8")
    tuned = BASE / "Modelfile.local"
    tuned.write_text(modelfile.replace("FROM qwen2.5:3b", f"FROM {base}"), encoding="utf-8")
    subprocess.run(["ollama", "create", "voic-coach", "-f", str(tuned)], check=True)
    print(f"✓ voic-coach dibuat dari {base}")
    return True


def setup_whisper(gpu: bool, check: bool) -> bool:
    step("Telinga (speech-to-text, faster-whisper)")
    size = "large-v3-turbo" if gpu else "small"
    target = MODELS / "whisper" / size
    if check:
        ok = (target / "model.bin").exists()
        print(("✓" if ok else "✗") + f" whisper {size} " + ("siap" if ok else "belum diunduh"))
        return ok
    try:
        from faster_whisper import download_model
    except ImportError:
        print("✗ Paket faster-whisper belum terpasang: pip install -r requirements.txt")
        return False
    print(f"Mengunduh whisper {size}…")
    download_model(size, output_dir=str(target))
    print(f"✓ whisper {size} → {target}")
    if gpu:
        print("  Set di .env/terminal: VOIC_STT_MODEL=large-v3-turbo VOIC_STT_DEVICE=cuda VOIC_STT_COMPUTE=float16")
    return True


def setup_piper(check: bool) -> bool:
    step("Suara (text-to-speech, Piper)")
    target = MODELS / "piper"
    target.mkdir(parents=True, exist_ok=True)
    ok = True
    for voice in PIPER_VOICES:
        onnx = target / f"{voice}.onnx"
        if check:
            ready = onnx.exists() and (target / f"{voice}.onnx.json").exists()
            print(("✓" if ready else "✗") + f" {voice}")
            ok = ok and ready
            continue
        if onnx.exists():
            print(f"✓ {voice} sudah ada")
            continue
        try:
            from piper.download_voices import download_voice
        except ImportError:
            print("✗ Paket piper-tts belum terpasang: pip install -r requirements.txt")
            return False
        try:
            download_voice(voice, target)
            print(f"✓ {voice}")
        except Exception as exc:
            ok = False
            print(f"✗ Gagal mengunduh {voice}: {exc}")
            print("  Daftar suara: https://huggingface.co/rhasspy/piper-voices (pilih folder id/id_ID)")
    return ok


def setup_mediapipe(check: bool) -> bool:
    step("Mata (kontak mata, MediaPipe di browser)")
    missing = [name for name in MEDIAPIPE_FILES if not (PUBLIC_MEDIAPIPE / name).exists()]
    if check or not missing:
        print(("✓ siap di public/vendor/mediapipe" if not missing else f"✗ belum lengkap: {', '.join(missing)}"))
        return not missing
    from urllib.request import urlopen

    for name in missing:
        target = PUBLIC_MEDIAPIPE / name
        target.parent.mkdir(parents=True, exist_ok=True)
        try:
            with urlopen(MEDIAPIPE_FILES[name], timeout=60) as response:
                target.write_bytes(response.read())
            print(f"✓ {name}")
        except Exception as exc:
            print(f"✗ {name}: {exc}")
            return False
    return True


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--gpu", action="store_true", help="Pakai model lebih besar untuk laptop dengan GPU NVIDIA")
    parser.add_argument("--check", action="store_true", help="Hanya cek kesiapan, tanpa mengunduh")
    parser.add_argument("--skip-ollama", action="store_true")
    args = parser.parse_args()

    results = {
        "Otak AI": True if args.skip_ollama else setup_ollama(args.gpu, args.check),
        "Telinga (STT)": setup_whisper(args.gpu, args.check),
        "Suara (TTS)": setup_piper(args.check),
        "Mata (kamera)": setup_mediapipe(args.check),
    }

    step("Ringkasan")
    for name, ok in results.items():
        print(f"{'✓' if ok else '✗'} {name}")
    if all(results.values()):
        print("\nSemua siap. Jalankan: python ai_service.py  (lalu cabut internet pun tetap jalan)")
    else:
        sys.exit(1)


if __name__ == "__main__":
    main()
