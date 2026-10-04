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
DEFAULT_MODEL = os.getenv("OLLAMA_MODEL", "llama3.2")
AUDIO_OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "audio_outputs")
os.makedirs(AUDIO_OUTPUT_DIR, exist_ok=True)


class FacialStatus(BaseModel):
    emotion: Optional[str] = "neutral"
    eye_contact_ratio: Optional[float] = 80.0
    smile_detected: Optional[bool] = False


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
    Menerima pesan ucapan + status ekspresi wajah dari Laravel,
    mengirim ke Ollama lokal, dan menyintesis suara balasan.
    """
    # 1. Analisis status ekspresi wajah pengguna
    facial_note = ""
    if req.facial_status:
        f = req.facial_status
        if f.eye_contact_ratio < 70:
            facial_note += " [CATATAN VISUAL AI: Pengguna terlihat tidak menatap kamera / menunduk]."
        if f.emotion == "nervous":
            facial_note += " [CATATAN VISUAL AI: Ekspresi pengguna terlihat tegang]."
        elif f.smile_detected:
            facial_note += " [CATATAN VISUAL AI: Pengguna tersenyum ramah]."

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
