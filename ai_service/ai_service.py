"""
VOIC Local AI Service (FastAPI + Ollama + TTS)
Jembatan antara Laravel Backend dan Otak AI Lokal di Laptop
"""

import os
import asyncio
import httpx
from typing import List, Optional, Dict, Any
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from pydantic import BaseModel

app = FastAPI(title="VOIC AI Service", version="1.0.0")

# CORS middleware agar Laravel frontend/backend bisa request tanpa halangan
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

OLLAMA_BASE_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")
DEFAULT_MODEL = os.getenv("OLLAMA_MODEL", "qwen2.5:3b")
AUDIO_OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "audio_outputs")
os.makedirs(AUDIO_OUTPUT_DIR, exist_ok=True)


class FacialStatus(BaseModel):
    emotion: Optional[str] = None
    eye_contact_ratio: Optional[float] = None
    smile_detected: Optional[bool] = None
    face_detected: Optional[bool] = None
    live_volume: Optional[float] = None


class ChatMessage(BaseModel):
    role: str  # 'user', 'assistant', 'system'
    content: str


class ChatRequest(BaseModel):
    role_name: str
    role_type: str
    system_prompt: str
    user_message: str
    facial_status: Optional[FacialStatus] = None
    conversation_history: Optional[List[ChatMessage]] = []
    generate_voice: Optional[bool] = True


class ChatResponse(BaseModel):
    role_name: str
    response_text: str
    facial_critique: Optional[str] = None
    audio_file: Optional[str] = None


class ConcludeRequest(BaseModel):
    role_name: str
    role_type: str
    system_prompt: str
    face_score: float
    voice_score: float
    overall_score: float
    duration_seconds: int
    conversation_history: Optional[List[ChatMessage]] = []
    feedback_notes: Optional[dict] = None


class ConcludeResponse(BaseModel):
    conclusion: str


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


@app.post("/chat", response_model=ChatResponse)
async def process_chat(req: ChatRequest):
    """
    Menerima pesan ucapan + status ekspresi wajah asli dari kamera browser,
    mengirim ke model LLM Ollama lokal, dan menyintesis suara balasan.
    """
    # 1. Analisis status ekspresi wajah asli dari kamera pengguna
    facial_note = ""
    if req.facial_status:
        f = req.facial_status
        if f.face_detected is False or (f.eye_contact_ratio is not None and f.eye_contact_ratio < 15):
            facial_note += " [DATA TELEMETRI KAMERA: Wajah pengguna tidak terdeteksi di kamera / pengguna tidak menatap layar]."
        else:
            if f.eye_contact_ratio is not None:
                if f.eye_contact_ratio < 65:
                    facial_note += f" [DATA TELEMETRI KAMERA: Kontak mata rendah ({f.eye_contact_ratio:.0f}%), pengguna terlihat menunduk/melihat ke arah lain]."
                elif f.eye_contact_ratio >= 85:
                    facial_note += f" [DATA TELEMETRI KAMERA: Kontak mata fokus dan mantap ({f.eye_contact_ratio:.0f}%)]."
            if f.emotion in ("tegang", "nervous"):
                facial_note += " [DATA TELEMETRI KAMERA: Otot wajah pengguna tampak tegang/kaku]."
            elif f.emotion in ("tersenyum", "smile") or f.smile_detected:
                facial_note += " [DATA TELEMETRI KAMERA: Pengguna tersenyum ramah]."
            elif f.emotion == "fokus":
                facial_note += " [DATA TELEMETRI KAMERA: Ekspresi wajah tenang dan fokus]."

    # 2. Susun prompt lengkap dengan persona karakter
    system_instruction = (
        f"{req.system_prompt}\n"
        f"Gunakan Bahasa Indonesia yang alami dan realistis sesuai karakter Anda.\n"
        f"Komentari atau tegur secara implisit jika ada catatan visual berikut: {facial_note}\n"
        f"Jaga agar jawaban singkat (maksimal 2-3 kalimat) agar interaksi percakapan terasa hidup dan tidak membosankan."
    )

    messages = [{"role": "system", "content": system_instruction}]
    for msg in req.conversation_history[-6:]:  # Ambil 6 riwayat terakhir
        messages.append({"role": msg.role, "content": msg.content})

    user_content = req.user_message
    if facial_note:
        user_content += f"\n{facial_note}"
    messages.append({"role": "user", "content": user_content})

    # 3. Panggil Ollama secara lokal
    ai_reply_text = ""
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": messages,
                    "stream": False,
                },
            )
            if ollama_res.status_code == 200:
                ai_reply_text = ollama_res.json()["message"]["content"].strip()
            else:
                ai_reply_text = generate_fallback_response(req)
    except Exception:
        # Fallback cerdas jika Ollama belum dinyalakan di laptop penguji
        ai_reply_text = generate_fallback_response(req)

    # 4. Sintesis Suara (TTS)
    audio_filename = None
    if req.generate_voice:
        audio_filename = await generate_tts_audio(ai_reply_text, req.role_type)

    return ChatResponse(
        role_name=req.role_name,
        response_text=ai_reply_text,
        facial_critique=facial_note.strip() if facial_note else None,
        audio_file=audio_filename,
    )


@app.post("/conclude", response_model=ConcludeResponse)
async def process_conclude(req: ConcludeRequest):
    """
    Menghasilkan kesimpulan akhir dan evaluasi resmi dari karakter AI
    berdasarkan data riil pengujian kamera, audio, dan percakapan.
    """
    summary_dialogue = ""
    for msg in (req.conversation_history or [])[-8:]:
        summary_dialogue += f"\n- {msg.role}: {msg.content}"

    prompt = (
        f"{req.system_prompt}\n"
        f"Simulasi latihan baru saja selesai selama {req.duration_seconds} detik.\n"
        f"DATA TELEMETRI & PENGUJIAN ASLI DARI SENSOR:\n"
        f"- Skor Optik Wajah (Kamera): {req.face_score:.1f}/100\n"
        f"- Skor Suara & Artikulasi (Mic): {req.voice_score:.1f}/100\n"
        f"- Skor Total Keseluruhan: {req.overall_score:.1f}/100\n"
        f"Transkrip Percakapan:{summary_dialogue}\n\n"
        f"Instruksi: Sebagai {req.role_name} ({req.role_type}), berikan kesimpulan resmi dan evaluasi performa akhir peserta ini (maksimal 2-3 kalimat) secara langsung dan profesional dalam Bahasa Indonesia. Sebutkan kelebihan dan catatan utama yang perlu diperbaiki sesuai data riil di atas."
    )

    conclusion_text = ""
    try:
        async with httpx.AsyncClient(timeout=20.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": [
                        {"role": "system", "content": f"Anda adalah {req.role_name}, seorang {req.role_type} yang memberikan rapor evaluasi akhir."},
                        {"role": "user", "content": prompt},
                    ],
                    "stream": False,
                },
            )
            if ollama_res.status_code == 200:
                conclusion_text = ollama_res.json()["message"]["content"].strip()
    except Exception:
        pass

    if not conclusion_text:
        conclusion_text = (
            f"Hasil Evaluasi {req.role_name}: Berdasarkan evaluasi visual kamera (skor {req.face_score:.1f}/100) "
            f"dan proyeksi suara (skor {req.voice_score:.1f}/100), latihan selesai dengan nilai keseluruhan {req.overall_score:.1f}/100."
        )

    return ConcludeResponse(conclusion=conclusion_text)


@app.get("/audio/{filename}")
def get_audio(filename: str):
    """Mengambil file audio suara balasan AI."""
    file_path = os.path.join(AUDIO_OUTPUT_DIR, filename)
    if os.path.exists(file_path):
        return FileResponse(file_path, media_type="audio/mpeg")
    raise HTTPException(status_code=404, detail="File audio tidak ditemukan")


async def generate_tts_audio(text: str, role_type: str) -> Optional[str]:
    """Menghasilkan file audio MP3 menggunakan edge-tts (lokal & cepat)."""
    try:
        import edge_tts
        voice = "id-ID-ArdiNeural" if role_type == "dosen_penguji" else ("id-ID-GadisNeural" if role_type == "hrd" else "id-ID-BudiNeural")
        filename = f"tts_{asyncio.get_event_loop().time():.0f}.mp3"
        output_path = os.path.join(AUDIO_OUTPUT_DIR, filename)

        communicate = edge_tts.Communicate(text, voice)
        await communicate.save(output_path)
        return filename
    except Exception as e:
        print(f"TTS Generation notice: {e}")
        return None


def generate_fallback_response(req: ChatRequest) -> str:
    """Respons cadangan cerdas jika server Ollama belum di-start."""
    if req.role_type == "dosen_penguji":
        return "Bagus, poin Anda dapat dimengerti. Namun tolong jelaskan lebih spesifik apa metode validasi data yang Anda gunakan pada bab 3?"
    elif req.role_type == "hrd":
        return "Terima kasih atas jawabannya. Bisakah Anda menceritakan contoh nyata saat Anda menghadapi konflik dalam tim kerja?"
    else:
        return "Saya paham idenya. Namun bagaimana strategi Anda mendapatkan 100 pengguna pertama tanpa biaya bakar uang?"


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service:app", host="127.0.0.1", port=8001, reload=True)
