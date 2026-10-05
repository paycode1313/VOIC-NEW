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
    language: Optional[str] = "id"  # 'id' or 'en'
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
    language: Optional[str] = "id"  # 'id' or 'en'
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
    is_en = (req.language == "en")
    role_nuance = ""
    if req.role_type == "dosen_penguji":
        if is_en:
            role_nuance = (
                "ROLE NUANCE (CHIEF THESIS EXAMINER): You are VOIC-Dosen Penguji. "
                "Be sharp, authoritative, academic, and critically scrutinize the student's methodology, theoretical framework, and datasets. "
                "Address the candidate as 'Candidate' or directly. "
                "Open with realistic spoken reactions (e.g., 'Right, I see...', 'Hold on a moment...', 'Conceptually interesting, but...'). "
                "Focus: Test scope limitations, dataset validity, chapter two literature review, and genuine research novelty."
            )
        else:
            role_nuance = (
                "GAYA PERAN (DOSEN PENGUJI SKRIPSI UTAMA): Anda adalah VOIC-Dosen Penguji. "
                "Bersikap kritis, berwibawa, akademis, dan langsung menguliti metodologi atau dasar teori. "
                "Panggil mahasiswa dengan 'Saudara' atau 'Anda'. "
                "Awali respon dengan reaksi lisan khas dosen penguji sidang (seperti: 'Hmm, oke...', 'Sebentar Saudara...', 'Secara konseptual menarik, tapi...'). "
                "Fokus: Uji batasan masalah, validitas data, landasan teori bab 2, atau kebaruan (novelty) penelitian."
            )
    elif req.role_type == "hrd":
        if is_en:
            role_nuance = (
                "ROLE NUANCE (TALENT ACQUISITION LEAD): You are VOIC-HRD. "
                "Be friendly, warm, empathetic, yet highly perceptive in reading candidate maturity and soft skills. "
                "Address the candidate naturally. "
                "Open with genuine conversational appreciation (e.g., 'That sounds really compelling...', 'I see, thanks for sharing...', 'Great initiative...'). "
                "Focus: Probe real behavioral scenarios with the STAR method (Situation, Task, Action, Result), team conflict resolution, and career drive."
            )
        else:
            role_nuance = (
                "GAYA PERAN (TALENT ACQUISITION & HR LEAD): Anda adalah VOIC-HRD. "
                "Bersikap ramah, hangat, komunikatif, namun sangat jeli membaca kepribadian dan kedewasaan emosional kandidat. "
                "Panggil kandidat dengan 'kamu' atau 'Anda'. "
                "Awali respon dengan apresiasi lisan manusiawi yang tulus (seperti: 'Wah, menarik banget ceritanya...', 'Oke baik, saya bisa bayangkan situasinya...', 'Keren ya inisiatifnya...'). "
                "Fokus: Uji pengalaman kerja nyata dengan metode STAR (Situation, Task, Action, Result), cara mengatasi konflik tim, dan motivasi kerja."
            )
    else:  # investor
        if is_en:
            role_nuance = (
                "ROLE NUANCE (MANAGING PARTNER VENTURE CAPITALIST): You are VOIC-Investor. "
                "Be quick, direct, punchy, commercially focused, and no-nonsense. "
                "Use casual yet sharp venture capitalist vernacular (e.g., 'Got the premise, but...', 'Look...', 'Cut to the chase...'). "
                "Focus: Market validation, CAC/LTV, unit economics, monetization speed, and defensible moat against well-funded incumbents."
            )
        else:
            role_nuance = (
                "GAYA PERAN (MANAGING PARTNER ANGEL INVESTOR): Anda adalah VOIC-Investor. "
                "Bersikap cepat, to-the-point, praktisi bisnis, energik, dan anti basa-basi teoritis. "
                "Gunakan gaya bicara founder/investor kasual profesional (seperti: 'Oke dapet poinnya, tapi...', 'Gini lho...', 'Singkat aja ya...'). "
                "Fokus: Validasi pasar riil, Customer Acquisition Cost (CAC), monetisasi, dan apa keunggulan kompetitif (moat) dari kompetitor bermodal besar."
            )

    lang_rule = (
        "LANGUAGE: Strictly reply in fluent, idiomatic, professional spoken ENGLISH only."
        if is_en
        else "BAHASA: Gunakan Bahasa Indonesia lisan yang luwes, alami, dan mengalir seperti manusia asli berbicara tatap muka."
    )

    system_instruction = (
        f"{req.system_prompt}\n\n"
        f"{role_nuance}\n\n"
        f"MANDATORY SPOKEN CONVERSATIONAL RULES (VITAL):\n"
        f"1. {lang_rule}\n"
        f"2. MAXIMUM 2 TO 3 SENTENCES ONLY: Follow the natural ping-pong structure: (1) Spontaneous reaction, (2) Sharp critique or feedback, (3) One challenging follow-up question. Never give a monologue.\n"
        f"3. ZERO MARKDOWN OR BULLETS: Do not use bullet points or asterisks (**bold**). The output will be synthesized by Text-to-Speech.\n"
        f"4. CAMERA INTEGRATION: If there is a visual telemetry note: '{facial_note}', naturally weave a gentle critique or encouraging remark matching your persona."
    )

    messages = [{"role": "system", "content": system_instruction}]
    for msg in req.conversation_history[-6:]:
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
                    "options": {
                        "temperature": 0.8,
                        "repeat_penalty": 1.15,
                        "top_p": 0.9,
                    },
                },
            )
            if ollama_res.status_code == 200:
                ai_reply_text = ollama_res.json()["message"]["content"].strip()
                ai_reply_text = ai_reply_text.replace("**", "").replace("*", "").replace("###", "")
            else:
                ai_reply_text = generate_fallback_response(req)
    except Exception:
        ai_reply_text = generate_fallback_response(req)

    # 4. Sintesis Suara (TTS)
    audio_filename = None
    if req.generate_voice:
        audio_filename = await generate_tts_audio(ai_reply_text, req.role_type, req.language or "id")

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

    is_en = (req.language == "en")
    lang_inst = (
        "Strictly write the final verdict in professional, articulate ENGLISH (2-3 sentences max)."
        if is_en
        else "Berikan kesimpulan resmi dan evaluasi performa akhir peserta ini (maksimal 2-3 kalimat) secara langsung dan profesional dalam Bahasa Indonesia."
    )

    prompt = (
        f"{req.system_prompt}\n"
        f"Session finished in {req.duration_seconds} seconds.\n"
        f"REAL HARDWARE & SENSOR TELEMETRY DATA:\n"
        f"- Face Optics Score (Webcam): {req.face_score:.1f}/100\n"
        f"- Voice Articulation Score (Mic): {req.voice_score:.1f}/100\n"
        f"- Overall Score: {req.overall_score:.1f}/100\n"
        f"Conversation Transcript:{summary_dialogue}\n\n"
        f"Instruction: As {req.role_name} ({req.role_type}), {lang_inst} Mention real strengths and key areas for improvement based on the sensor telemetry above."
    )

    conclusion_text = ""
    try:
        async with httpx.AsyncClient(timeout=20.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": [
                        {"role": "system", "content": f"You are {req.role_name}, evaluating the session in {'English' if is_en else 'Indonesian'}."},
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
        if is_en:
            conclusion_text = (
                f"Evaluation Verdict from {req.role_name}: Based on facial optics ({req.face_score:.1f}/100) "
                f"and vocal clarity ({req.voice_score:.1f}/100), the session concluded with an overall score of {req.overall_score:.1f}/100."
            )
        else:
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


async def generate_tts_audio(text: str, role_type: str, language: str = "id") -> Optional[str]:
    """Menghasilkan file audio MP3 menggunakan edge-tts (lokal & cepat)."""
    try:
        import edge_tts
        if language == "en":
            voice = "en-US-GuyNeural" if role_type == "dosen_penguji" else ("en-US-JennyNeural" if role_type == "hrd" else "en-US-BrianNeural")
        else:
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

    if req.language == "en":
        pools = {
            "dosen_penguji": [
                "Right, I note your introductory point. But could you explain the theoretical foundation in chapter two that justifies your chosen methodology?",
                "Hold on, your methodology needs empirical backing. How did you verify that your dataset is completely free from sampling bias?",
                "Conceptually interesting. However, demonstrate to the examination committee: what is the genuine novelty of this research compared to past state-of-the-art papers?",
                "Your delivery is structured, but your problem boundaries are too broad. Why didn't you evaluate edge-case boundary scenarios on this model?",
                "Alright. Now tell us: what primary evaluation metrics did you benchmark to prove this solution is truly superior?",
            ],
            "hrd": [
                "That is really fascinating. Could you provide a specific work example where your individual initiative directly saved a critical project milestone?",
                "Great, I can picture that scenario. When a senior team member strongly pushes back on your recommendation, what communication strategy do you use?",
                "Impressive background. How do you maintain composure and prioritize tasks when several urgent deadlines land at once?",
                "I appreciate your enthusiasm. Could you share your greatest professional setback and the pivotal takeaway you learned from it?",
                "Wonderful. What kind of engineering culture and team environment allows your potential to thrive best?",
            ],
            "investor": [
                "Got the problem statement. To keep it crisp: what is your estimated Customer Acquisition Cost and how do you retain your users?",
                "The solution makes sense. But what is your defensible moat if a well-funded incumbent copies your feature set next month?",
                "Bold vision. Can you break down your unit economics and how long until your startup reaches operational break-even?",
                "Huge addressable market, but execution is king. What concrete operational milestones are you hitting in the next six months?",
                "Great pitching energy! Give me the single most compelling reason why we should write you a check right now?",
            ],
        }
    else:
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
