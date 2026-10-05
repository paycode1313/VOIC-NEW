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

    # 2. Susun prompt khusus per peran agar berbicara luwes selayaknya manusia lisan (Spoken Style)
    role_nuance = ""
    if req.role_type == "dosen_penguji":
        role_nuance = (
            "GAYA PERAN (DOSEN PENGUJI SKRIPSI UTAMA): Anda adalah Dr. Ir. Hartono, M.T. "
            "Bersikap kritis, berwibawa, akademis, dan langsung menguliti metodologi atau dasar teori. "
            "Panggil mahasiswa dengan 'Saudara' atau 'Anda'. "
            "Awali respon dengan reaksi lisan khas dosen penguji sidang (seperti: 'Hmm, oke...', 'Sebentar Saudara...', 'Secara konseptual menarik, tapi...'). "
            "Fokus: Uji batasan masalah, validitas data, landasan teori bab 2, atau kebaruan (novelty) penelitian."
        )
    elif req.role_type == "hrd":
        role_nuance = (
            "GAYA PERAN (TALENT ACQUISITION & HR LEAD): Anda adalah Nadia Putri, S.Psi. "
            "Bersikap ramah, hangat, komunikatif, namun sangat jeli membaca kepribadian dan kedewasaan emosional kandidat. "
            "Panggil kandidat dengan 'kamu' atau 'Anda'. "
            "Awali respon dengan apresiasi lisan manusiawi yang tulus (seperti: 'Wah, menarik banget ceritanya...', 'Oke baik, saya bisa bayangkan situasinya...', 'Keren ya inisiatifnya...'). "
            "Fokus: Uji pengalaman kerja nyata dengan metode STAR (Situation, Task, Action, Result), cara mengatasi konflik tim, dan motivasi kerja."
        )
    else:  # investor
        role_nuance = (
            "GAYA PERAN (MANAGING PARTNER ANGEL INVESTOR): Anda adalah David Wijaya. "
            "Bersikap cepat, to-the-point, praktisi bisnis, energik, dan anti basa-basi teoritis. "
            "Gunakan gaya bicara founder/investor kasual profesional (seperti: 'Oke dapet poinnya, tapi...', 'Gini lho...', 'Singkat aja ya...'). "
            "Fokus: Validasi pasar riil, Customer Acquisition Cost (CAC), monetisasi, dan apa keunggulan kompetitif (moat) dari kompetitor bermodal besar."
        )

    system_instruction = (
        f"{req.system_prompt}\n\n"
        f"{role_nuance}\n\n"
        f"PEDOMAN WAJIB PERCAKAPAN LISAN MANUSIAWI (SANGAT PENTING):\n"
        f"1. JANGAN KAKU: Gunakan Bahasa Indonesia lisan yang luwes, alami, dan mengalir seperti manusia asli berbicara tatap muka. JANGAN PERNAH berbicara seperti robot, buku teks, atau asisten AI umum (jangan pakai pembuka klise seperti 'Tentu, saya memahami...').\n"
        f"2. MAKSIMAL 2 SAMPAI 3 KALIMAT: Terapkan format ping-pong: (1) Reaksi spontan lisan, (2) Tanggapan atau kritik tajam, (3) Satu pertanyaan lanjutan yang menantang. Jangan berikan ceramah panjang.\n"
        f"3. DILARANG FORMAT TEKS / MARKDOWN: Jangan gunakan bullet points (- atau nomor 1, 2, 3), jangan gunakan tanda bintang (**teks tebal**), jangan gunakan tanda pagar (###). Jawaban ini akan langsung dibacakan oleh mesin suara TTS.\n"
        f"4. INTEGRASI KAMERA: Jika ada catatan telemetri visual berikut: '{facial_note}', selipkan teguran santun atau dorongan spontan secara alami sesuai karakter Anda."
    )

    messages = [{"role": "system", "content": system_instruction}]
    for msg in req.conversation_history[-6:]:  # Ambil 6 riwayat terakhir
        messages.append({"role": msg.role, "content": msg.content})

    user_content = req.user_message
    if facial_note:
        user_content += f"\n{facial_note}"
    messages.append({"role": "user", "content": user_content})

    # 3. Panggil Ollama secara lokal dengan opsi kreatifitas alami
    ai_reply_text = ""
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": messages,
                    "stream": False,
                    "options": {
                        "temperature": 0.8,
                        "repeat_penalty": 1.15,
                        "top_p": 0.9,
                    },
                },
            )
            if ollama_res.status_code == 200:
                ai_reply_text = ollama_res.json()["message"]["content"].strip()
                # Bersihkan sisa format markdown jika ada agar suara TTS natural
                ai_reply_text = ai_reply_text.replace("**", "").replace("*", "").replace("###", "")
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
    import random

    pools = {
        "dosen_penguji": [
            "Hmm, oke poin pengantar Saudara saya catat. Tapi tolong jelaskan secara konseptual, apa dasar teori utama yang mendukung validitas algoritma ini pada bab dua?",
            "Sebentar Saudara, metodologi yang Anda sebutkan tadi perlu pembuktian empiris. Bagaimana Anda memastikan dataset yang digunakan bebas dari bias sampling?",
            "Secara konseptual menarik. Namun coba buktikan kepada dewan penguji, apa novelty atau kebaruan nyata penelitian ini dibandingkan jurnal rujukan terdahulu?",
            "Pemaparan Anda cukup runut, tapi batasan masalahnya masih mengambang. Mengapa Anda tidak menguji skenario data ekstrem pada sistem ini?",
            "Baik. Sekarang coba tunjukkan apa metrik evaluasi utama yang Anda pakai untuk menyatakan sistem ini berhasil?",
        ],
        "hrd": [
            "Wah, menarik sekali ceritanya. Bisakah kamu berikan satu contoh situasi kerja nyata di mana inisiatif mandiri kamu berhasil menyelamatkan target tim?",
            "Oke baik, saya bisa bayangkan situasinya. Nah, jika kamu berada dalam kondisi rekan satu tim menolak solusi yang kamu tawarkan, bagaimana pendekatan komunikasimu?",
            "Keren ya pengalamannya. Lalu bagaimana caramu mengelola prioritas saat dihadapkan pada beberapa deadline mendesak yang datang bersamaan?",
            "Saya suka antusiasmemu menceritakan hal itu. Bisakah kamu ceritakan kegagalan terbesar dalam pekerjaanmu dan apa pelajaran terpenting yang kamu petik?",
            "Menarik sekali. Menurutmu, lingkungan kerja seperti apa yang paling bisa memicu potensimu berkembang maksimal?",
        ],
        "investor": [
            "Oke, problem pasarnya dapet. Tapi singkat aja ya, berapa perkiraan Customer Acquisition Cost kamu dan bagaimana kamu menjaga retensi pengguna tetap tinggi?",
            "Gini lho, solusinya masuk akal. Tapi apa moat atau benteng pertahananmu kalau kompetitor besar dengan modal melimpah bikin fitur serupa bulan depan?",
            "Idenya berani, saya suka. Tapi tolong jelaskan unit economics-nya: butuh berapa lama sampai startup kamu mencapai titik impas atau profit?",
            "Pasarnya memang besar, tapi eksekusi itu kuncinya. Milestone operasional konkret apa yang ingin kamu capai dalam enam bulan ke depan?",
            "Bagus energi pitching-nya! Tapi sebutkan satu alasan paling kuat kenapa kami harus berinvestasi di tim kamu sekarang?",
        ],
    }

    role_pool = pools.get(req.role_type, pools["dosen_penguji"])
    return random.choice(role_pool)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service:app", host="127.0.0.1", port=8001, reload=True)
