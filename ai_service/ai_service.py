"""
VOIC Local AI Service: otak (Ollama), telinga (faster-whisper), dan suara (Piper) di laptop.

Endpoint ujian ada di exam_engine.py; mesin suara di voice_engine.py.
"""

import os
import time
import asyncio
from contextlib import asynccontextmanager
import httpx
from typing import Optional
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from pydantic import BaseModel
from exam_engine import router as exam_router
import voice_engine

OLLAMA_BASE_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")
DEFAULT_MODEL = os.getenv("OLLAMA_MODEL", "voic-coach")
AUDIO_OUTPUT_DIR = voice_engine.AUDIO_OUTPUT_DIR


def cleanup_old_audio_files(max_age_seconds: int = 7200):
    """Menghapus file MP3 di audio_outputs yang umurnya lebih dari 2 jam (7200 detik) agar disk tidak penuh."""
    now = time.time()
    try:
        for fname in os.listdir(AUDIO_OUTPUT_DIR):
            if fname.endswith(".mp3"):
                fpath = os.path.join(AUDIO_OUTPUT_DIR, fname)
                if os.path.isfile(fpath) and (now - os.path.getmtime(fpath)) > max_age_seconds:
                    os.remove(fpath)
    except Exception as e:
        print(f"Audio cleanup notice: {e}")


async def periodic_warmup_and_cleanup():
    """Muat model ujian saat start lalu jaga tetap hangat; bersihkan audio lama setiap 15 menit.

    Memakai exam_engine.warmup() agar opsi (num_ctx, keep_alive) sama persis dengan permintaan ujian;
    opsi yang berbeda membuat Ollama memuat ulang model dan peserta menunggu lama.
    """
    from exam_engine import warmup

    while True:
        try:
            cleanup_old_audio_files()
            result = await warmup()
            if result.get("ok"):
                print(f"[VOIC] Model {result['model']} siap ({result['seconds']} dtk).")
            else:
                print(f"[VOIC] Warmup model gagal: {result.get('error')}")
        except Exception as exc:
            print(f"[VOIC] Warmup notice: {exc}")
        await asyncio.sleep(900)  # 15 menit


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan context manager modern (menggantikan @app.on_event('startup') yang deprecated)."""
    task = asyncio.create_task(periodic_warmup_and_cleanup())
    # Muat Whisper & Piper di latar belakang agar giliran pertama peserta tidak lambat.
    asyncio.get_running_loop().run_in_executor(None, voice_engine.warmup_stt)
    asyncio.get_running_loop().run_in_executor(None, voice_engine.warmup_tts)
    yield
    task.cancel()


app = FastAPI(title="VOIC AI Service", version="1.3.0", lifespan=lifespan)
app.include_router(exam_router)

# CORS middleware agar Laravel frontend/backend bisa request tanpa halangan
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class TTSRequest(BaseModel):
    text: str
    role_type: Optional[str] = "dosen_penguji"
    language: Optional[str] = "id"
    inline: Optional[bool] = False


# =====================================================================
# DYNAMIC FEW-SHOT EXAMPLES (GAYA BICARA PENGUJI LISAN ALA GOPREP.GG)
# Active Listening -> Conversational Bridging -> Targeted Probing
# =====================================================================
@app.get("/")
def health_check():
    return {
        "status": "online",
        "service": "VOIC AI Service",
        "ollama_url": OLLAMA_BASE_URL,
        "default_model": DEFAULT_MODEL,
    }


@app.get("/health/ollama")
async def check_ollama():
    """Memeriksa apakah Ollama aktif di laptop."""
    try:
        async with httpx.AsyncClient(timeout=3.0) as client:
            res = await client.get(f"{OLLAMA_BASE_URL}/api/tags")
            if res.status_code == 200:
                models = res.json().get("models", [])
                return {"ollama_active": True, "available_models": [m["name"] for m in models]}
            return {"ollama_active": False, "detail": "Ollama merespons status non-200"}
    except Exception as e:
        return {"ollama_active": False, "detail": str(e), "hint": "Pastikan aplikasi Ollama sudah dibuka di laptop"}


@app.get("/audio/{filename}")
def get_audio(filename: str):
    """Mengambil file audio suara balasan AI (MP3 dari edge-tts atau WAV dari Piper)."""
    safe_name = os.path.basename(filename)
    file_path = os.path.join(AUDIO_OUTPUT_DIR, safe_name)
    if safe_name == filename and os.path.exists(file_path):
        media_type = "audio/wav" if safe_name.endswith(".wav") else "audio/mpeg"
        return FileResponse(file_path, media_type=media_type)
    raise HTTPException(status_code=404, detail="File audio tidak ditemukan")


# python-multipart dibutuhkan FastAPI untuk upload file. Jika belum terpasang, service TETAP jalan
# (otak AI tetap aktif); hanya STT lokal yang nonaktif. Dulu ini membuat service crash saat start.
try:
    import python_multipart  # noqa: F401
    HAS_MULTIPART = True
except ImportError:
    try:
        import multipart  # noqa: F401
        HAS_MULTIPART = True
    except ImportError:
        HAS_MULTIPART = False

if HAS_MULTIPART:

    @app.post("/stt")
    async def speech_to_text(audio: UploadFile = File(...), language: str = Form("id"), prompt: Optional[str] = Form(None)):
        """Speech-to-text lokal (faster-whisper). Dipakai Laravel saat peserta menjawab dengan suara."""
        if not voice_engine.stt_available():
            raise HTTPException(status_code=503, detail="STT lokal belum terpasang. Jalankan: python setup_models.py")
        data = await audio.read()
        if len(data) < 1000:
            return {"text": "", "duration": 0, "latency_ms": 0, "engine": "faster-whisper"}
        try:
            return await asyncio.to_thread(voice_engine.transcribe, data, language, prompt)
        except Exception as exc:
            raise HTTPException(status_code=503, detail=f"STT gagal: {exc}")

else:
    print("[VOIC] PERINGATAN: paket python-multipart belum terpasang, STT lokal nonaktif. "
          "Jalankan: pip install -r requirements.txt")

    @app.post("/stt")
    async def speech_to_text_unavailable():
        raise HTTPException(status_code=503, detail="python-multipart belum terpasang: pip install -r requirements.txt")


@app.get("/voice/status")
async def voice_status():
    """Status mesin suara & otak AI, untuk badge di ruang ujian."""
    ollama_ok, models = False, []
    try:
        async with httpx.AsyncClient(timeout=1.5) as client:
            res = await client.get(f"{OLLAMA_BASE_URL}/api/tags")
            if res.status_code == 200:
                models = [m["name"] for m in res.json().get("models", [])]
                ollama_ok = True
    except Exception:
        pass
    from exam_engine import EXAM_MODEL

    voice = voice_engine.status()
    if not HAS_MULTIPART:
        voice["stt"]["available"] = False
        voice["stt"]["error"] = "python-multipart belum terpasang"
    exam_model_ready = any(m == EXAM_MODEL or m.startswith(EXAM_MODEL + ":") for m in models)
    return {
        "llm": {
            "available": ollama_ok,
            "models": models,
            "exam_model": EXAM_MODEL,
            "exam_model_ready": exam_model_ready,
            "local": True,
        },
        **voice,
    }


@app.post("/tts")
async def process_tts(req: TTSRequest):
    """Sintesis suara (Piper lokal, cadangan edge-tts). `inline=true` mengembalikan audio base64."""
    from exam_engine import audio_payload

    audio_file = await generate_tts_audio(req.text, req.role_type or "dosen_penguji", req.language or "id")
    if audio_file:
        payload = audio_payload(audio_file, bool(req.inline))
        return {
            "success": True,
            **payload,
            "audio_url": f"/audio/{audio_file}" if payload.get("audio_file") else None,
        }
    return {
        "success": False,
        "audio_file": None,
        "audio_url": None,
    }


async def generate_tts_audio(text: str, role_type: str, language: str = "id") -> Optional[str]:
    """TTS untuk semua endpoint: Piper lokal lebih dulu, edge-tts online sebagai cadangan."""
    return await voice_engine.synthesize(text, role_type or "dosen_penguji", language or "id", AUDIO_OUTPUT_DIR)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service:app", host="127.0.0.1", port=8001, reload=False)