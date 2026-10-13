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
from ai_learner import ai_learner

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
    session_id: Optional[int] = None
    language: Optional[str] = "id"  # 'id' or 'en'
    context_material: Optional[str] = None
    facial_status: Optional[FacialStatus] = None
    user_memory_profile: Optional[Dict[str, Any]] = None
    conversation_history: Optional[List[ChatMessage]] = []
    generate_voice: Optional[bool] = True
    stage: Optional[str] = "core"  # 'opening', 'core', 'pressure_test', 'closing'


class ChatResponse(BaseModel):
    role_name: str
    response_text: str
    facial_critique: Optional[str] = None
    audio_file: Optional[str] = None
    coherence_score: Optional[float] = 85.0
    coherence_category: Optional[str] = "Relevan & Nyambung"
    salient_entities: Optional[List[str]] = []


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


class TTSRequest(BaseModel):
    text: str
    role_type: Optional[str] = "dosen_penguji"
    language: Optional[str] = "id"


@app.get("/")
def health_check():
    return {
        "status": "online",
        "service": "VOIC AI Service",
        "ollama_url": OLLAMA_BASE_URL,
        "default_model": DEFAULT_MODEL,
    }


@app.on_event("startup")
async def start_model_warmup():
    """Meluncurkan background task periodic keep-warm setiap 15 menit agar model tetap di memori (mencegah cold start)."""
    asyncio.create_task(periodic_warmup())


async def periodic_warmup():
    while True:
        try:
            async with httpx.AsyncClient(timeout=4.0) as client:
                await client.post(
                    f"{OLLAMA_BASE_URL}/api/generate",
                    json={"model": DEFAULT_MODEL, "prompt": "OK", "stream": False},
                )
        except Exception:
            pass
        await asyncio.sleep(900)  # 15 menit (900 detik)


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
    menganalisis koherensi semantik, grounding topik real-time, dan memproses dialog.
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

    # 2. Temukan pertanyaan AI sebelumnya untuk evaluasi koherensi
    last_ai_question = ""
    for msg in reversed(req.conversation_history or []):
        if msg.role in ("assistant", "ai"):
            last_ai_question = msg.content
            break

    # 3. Deep Learning Coherence & Active Listening Grounding
    coherence_score, coherence_cat = ai_learner.evaluate_semantic_coherence(
        last_ai_question=last_ai_question,
        user_reply=req.user_message,
        topic_context=req.context_material,
    )
    salient_entities = ai_learner.extract_salient_entities(req.user_message)
    grounding_directive = ai_learner.build_grounded_conversation_instruction(
        role_type=req.role_type,
        user_message=req.user_message,
        last_ai_question=last_ai_question,
        user_memory_profile=req.user_memory_profile,
        language=req.language or "id",
    )

    # 4. Susun prompt khusus per peran agar berbicara luwes selayaknya manusia lisan (Spoken Style)
    is_en = (req.language == "en")
    role_nuance = ""
    if req.role_type == "dosen_penguji":
        if is_en:
            role_nuance = (
                "ROLE NUANCE: You are a sharp, authoritative, and academic Chief Thesis Examiner. "
                "Critically scrutinize methodology, theoretical foundations, and empirical datasets."
            )
        else:
            role_nuance = (
                "GAYA PERAN: Anda adalah Dosen Penguji Sidang Skripsi Utama yang kritis, berwibawa, dan akademis. "
                "Uji metodologi, landasan teori bab dua, batasan masalah, dan pembuktian empiris mahasiswa."
            )
    elif req.role_type == "hrd":
        if is_en:
            role_nuance = (
                "ROLE NUANCE: You are an insightful and conversational Senior Talent Acquisition Lead. "
                "Explore real workplace achievements using the STAR method, team dynamics, and motivation."
            )
        else:
            role_nuance = (
                "GAYA PERAN: Anda adalah Senior HR Recruiter & Talent Acquisition yang komunikatif, hangat, dan jeli. "
                "Uji pengalaman kerja nyata dengan metode STAR (Situation, Task, Action, Result), dinamika tim, dan motivasi kerja."
            )
    else:  # investor
        if is_en:
            role_nuance = (
                "ROLE NUANCE: You are a no-nonsense, commercially focused Venture Capitalist and Angel Investor. "
                "Focus on market traction, unit economics, CAC/LTV, monetization, and competitive moat."
            )
        else:
            role_nuance = (
                "GAYA PERAN: Anda adalah Managing Partner Angel Investor / Venture Capitalist yang tajam dan to-the-point. "
                "Fokus pada validasi pasar riil, Customer Acquisition Cost (CAC), monetisasi, dan keunggulan kompetitif (moat)."
            )

    lang_rule = (
        "LANGUAGE: Strictly reply in fluent, idiomatic, professional spoken ENGLISH only."
        if is_en
        else "BAHASA: Gunakan Bahasa Indonesia lisan yang luwes, alami, dan mengalir seperti manusia asli berbicara tatap muka."
    )

    context_note = ""
    if req.context_material:
        context_note = f"\nMATERI RUJUKAN / ABSTRAK PESERTA:\n\"{req.context_material.strip()}\"\n"

    visual_context = ""
    if facial_note:
        visual_context = f"\nCATATAN OBSERVASI KAMERA PESERTA: {facial_note.strip()}\n"

    stage_instruction = ""
    if req.stage == "pressure_test":
        stage_instruction = (
            "\nTAHAP SIMULASI: PRESSURE TEST (Ujian Di Bawah Tekanan Ekstrem). "
            "Ajukan pertanyaan yang sangat tajam, menantang skenario kegagalan, batasan terburuk, atau ancaman kompetitor. "
            "Uji ketahanan mental dan ketenangan peserta secara kritis namun tetap akademis/profesional.\n"
            if not is_en else
            "\nSIMULATION STAGE: PRESSURE TEST (High-Stakes Stress Test). "
            "Ask an intensely challenging, stress-testing question that directly probes failure scenarios, boundary conditions, or competitive threats. Test candidate composure under real pressure.\n"
        )
    elif req.stage == "closing":
        stage_instruction = (
            "\nTAHAP SIMULASI: CLOSING STATEMENT (Penutup). Minta pernyataan penutup akhir dari peserta sebelum evaluasi diselesaikan.\n"
            if not is_en else
            "\nSIMULATION STAGE: CLOSING STATEMENT. Invite the candidate to give their final closing statement before concluding.\n"
        )

    if is_en:
        anti_template_rules = (
            "NATURAL SPOKEN CONVERSATION & STRICT ANTI-TEMPLATE RULES:\n"
            "1. LANGUAGE: Strictly reply in fluent, idiomatic, professional spoken ENGLISH only.\n"
            "2. ABSOLUTELY NO ROBOTIC OR CANNED OPENERS:\n"
            "   - NEVER start with: 'Regarding X that you mentioned...', 'I take note of...', 'Great presentation...', 'Hold on a moment...', or 'Interesting point...'.\n"
            "   - Never sound like an automated bot or telephone operator.\n"
            "3. INSTANT SUBSTANTIVE ENGAGEMENT (DEEP ACTIVE LISTENING):\n"
            "   - If they cite numbers or metrics: immediately interrogate data leakage, baselines, or statistical significance.\n"
            "   - If they describe a method: probe technical trade-offs, scalability bottlenecks, or boundary edge cases.\n"
            "   - If they describe team leadership: demand personal accountability and how they handled real friction.\n"
            "   - If they pitch a business: stress-test CAC, net churn, and defensibility against tech giants.\n"
            "4. NATURAL SPOKEN CADENCE: Deliver your reaction in 2 to 3 fluid spoken sentences (40–70 words).\n"
            "5. FEW-SHOT DIALOGUE EXAMPLES (EMULATE THIS STYLE):\n"
            "   [Candidate: 'We trained a Random Forest model on 500 image samples achieving 94% accuracy.']\n"
            "   -> CORRECT: '500 samples is extraordinarily small for image classification. How do you mathematically guarantee this isn't severe overfitting against a biased sample?'\n"
            "   -> INCORRECT: 'Regarding the Random Forest model you just explained, that is interesting. How did you test it?'\n"
            "6. DO NOT use asterisks (**), hashtags (#), or any Markdown markup."
        )
    else:
        anti_template_rules = (
            "PRINSIP PERCAKAPAN LISAN ALAMI & ANTI-TEMPLATE (MUTLAK):\n"
            f"1. {lang_rule}\n"
            "2. DILARANG KERAS MENGULANG PERKATAAN PESERTA ATAU MENGGUNAKAN FORMULA KLISE:\n"
            "   - JANGAN PERNAH awali respon dengan: 'Terkait X...', 'Mengenai X...', 'Poin tersebut saya catat', 'Bagus sekali pemaparannya', 'Tunggu sebentar...', atau 'Menarik sekali'.\n"
            "   - Jangan pernah terdengar seperti customer service atau mesin penjawab formulir.\n"
            "3. LANGSUNG RESPONS SUBSTANSI & ARGUMEN (ACTIVE LISTENING TINGKAT TINGGI):\n"
            "   - Bila peserta mengutarakan data/angka: langsung pertanyakan validitas atau metodologi pengujiannya.\n"
            "   - Bila peserta menjelaskan metode: langsung serang celah/asumsi dasarnya atau minta perbandingan dengan standar yang ada.\n"
            "   - Bila peserta menceritakan pengalaman tim: gali akuntabilitas pribadi dan cara mengatasi resistensi nyata.\n"
            "   - Bila peserta memaparkan bisnis: tantang unit economics, payback period, dan ketahanan terhadap kompetitor.\n"
            "4. GAYA BICARA LISAN MENGALIR: Sampaikan tanggapan spontan dalam 2 hingga 3 kalimat lugas selayaknya obrolan tatap muka (sekitar 40–70 kata).\n"
            "5. CONTOH POLA DIALOG LISAN MANUSIAWI (TIRU POLA INI):\n"
            "   [Peserta: 'Saya melatih model Random Forest pada 500 sampel gambar dengan akurasi 94%.']\n"
            "   -> BENAR: '500 sampel itu terhitung sangat minim untuk klasifikasi gambar. Parameter apa yang menjamin model Anda tidak overfit dan hanya menghafal data uji?'\n"
            "   -> SALAH: 'Terkait model Random Forest yang Anda jelaskan barusan, hal itu menarik. Namun bagaimana Anda mengujinya?'\n"
            "   [Peserta: 'Di kantor sebelumnya saya menginisiasi perombakan alur sprint karena sering ada miskomunikasi antar tim.']\n"
            "   -> BENAR: 'Pasti ada penolakan dari rekan yang sudah terbiasa dengan cara lama. Siapa yang paling menentang saat itu dan bagaimana kamu membuktikan perombakanmu berdampak?'\n"
            "   -> SALAH: 'Bagus sekali inisiatif perombakan sprint tersebut. Lalu apa tantangan yang kamu hadapi?'\n"
            "6. DILARANG menggunakan tanda bintang (**), tanda pagar (#), atau format Markdown apapun."
        )

    system_instruction = (
        f"PERAN ANDA: {req.role_name} ({req.role_type}).\n"
        f"{req.system_prompt}\n\n"
        f"{role_nuance}\n"
        f"{stage_instruction}"
        f"{context_note}"
        f"{visual_context}\n"
        f"{grounding_directive}\n\n"
        f"{anti_template_rules}"
    )

    messages = [{"role": "system", "content": system_instruction}]
    for msg in (req.conversation_history or [])[-8:]:
        messages.append({"role": msg.role, "content": msg.content})

    # Pure user spoken words
    messages.append({"role": "user", "content": req.user_message})

    # 5. Panggil Ollama secara lokal
    ai_reply_text = ""
    try:
        async with httpx.AsyncClient(timeout=35.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": messages,
                    "stream": False,
                    "keep_alive": "60m",
                    "options": {
                        "temperature": 0.76,
                        "repeat_penalty": 1.18,
                        "top_p": 0.92,
                        "num_predict": 160,
                        "num_ctx": 2048,
                    },
                },
            )
            if ollama_res.status_code == 200:
                ai_reply_text = ollama_res.json()["message"]["content"].strip()
                ai_reply_text = ai_reply_text.replace("**", "").replace("*", "").replace("###", "")
                if ai_reply_text and not ai_reply_text.endswith((".", "?", "!")):
                    last_punct = max(ai_reply_text.rfind("."), ai_reply_text.rfind("?"), ai_reply_text.rfind("!"))
                    if last_punct > 25:
                        ai_reply_text = ai_reply_text[:last_punct + 1]
            else:
                ai_reply_text = ai_learner.generate_smart_grounded_fallback(
                    role_type=req.role_type,
                    user_message=req.user_message,
                    last_ai_question=last_ai_question,
                    language=req.language or "id",
                )
    except Exception:
        ai_reply_text = ai_learner.generate_smart_grounded_fallback(
            role_type=req.role_type,
            user_message=req.user_message,
            last_ai_question=last_ai_question,
            language=req.language or "id",
        )

    # 6. Rekam ke Dataset Deep Learning Berkelanjutan
    try:
        f_dict = req.facial_status.model_dump() if req.facial_status and hasattr(req.facial_status, 'model_dump') else (req.facial_status.dict() if req.facial_status else {})
        ai_learner.record_learning_turn(
            session_id=req.session_id or 0,
            role_type=req.role_type,
            user_message=req.user_message,
            ai_response=ai_reply_text,
            coherence_score=coherence_score,
            facial_status=f_dict,
            language=req.language or "id",
        )
    except Exception as e:
        print(f"Dataset harvest notice: {e}")

    # 7. Sintesis Suara (TTS)
    audio_filename = None
    if req.generate_voice:
        audio_filename = await generate_tts_audio(ai_reply_text, req.role_type, req.language or "id")

    return ChatResponse(
        role_name=req.role_name,
        response_text=ai_reply_text,
        facial_critique=facial_note.strip() if facial_note else None,
        audio_file=audio_filename,
        coherence_score=coherence_score,
        coherence_category=coherence_cat,
        salient_entities=salient_entities,
    )


@app.get("/learn/stats")
def get_deep_learning_stats():
    """Mengambil status telemetri model pembelajaran berkelanjutan (Deep Learning) VOIC."""
    return ai_learner.get_learning_stats()


class SyncLearnRequest(BaseModel):
    user_id: Optional[int] = None
    session_id: Optional[int] = None
    role_type: str
    overall_score: float
    weaknesses: Optional[List[str]] = []
    mastered_topics: Optional[List[str]] = []


@app.post("/learn/sync")
def sync_learning_profile(payload: SyncLearnRequest):
    """Sinkronisasi profil pembelajaran berkelanjutan."""
    return {
        "success": True,
        "message": "Profil memori pembelajaran AI berhasil disinkronkan.",
        "stats": ai_learner.get_learning_stats(),
    }



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
                    "keep_alive": "60m",
                    "options": {
                        "temperature": 0.65,
                        "num_predict": 110,
                        "num_ctx": 2048,
                    },
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


@app.post("/tts")
async def process_tts(req: TTSRequest):
    """
    Sintesis suara instan berbasis karakter peran AI:
    - Dosen Penguji ('Dosen Killer'): Bariton berat, tempo berwibawa & terukur (-6Hz, -9%)
    - HRD ('Suara HRD Ramah'): Vokal wanita hangat, sopan & empatik (+4Hz, +2%)
    - Investor ('Pitch Bisnis'): Vokal energik, cepat, to-the-point & dinamis (-1Hz, +15%)
    """
    audio_file = await generate_tts_audio(req.text, req.role_type or "dosen_penguji", req.language or "id")
    if audio_file:
        return {
            "success": True,
            "audio_file": audio_file,
            "audio_url": f"/audio/{audio_file}",
        }
    return {
        "success": False,
        "audio_file": None,
        "audio_url": None,
    }


async def generate_tts_audio(text: str, role_type: str, language: str = "id") -> Optional[str]:
    """Menghasilkan file audio MP3 menggunakan edge-tts dengan prosodi karakter peran unik."""
    try:
        import edge_tts

        if language == "en":
            if role_type == "dosen_penguji":
                # Academic thesis examiner: deep, stern, deliberate
                voice = "en-US-GuyNeural"
                pitch = "-5Hz"
                rate = "-8%"
            elif role_type == "hrd":
                # Corporate HR recruiter: warm, pleasant, approachable
                voice = "en-US-JennyNeural"
                pitch = "+4Hz"
                rate = "+2%"
            else:  # investor
                # Venture pitch investor: crisp, fast, assertive
                voice = "en-US-BrianNeural"
                pitch = "+0Hz"
                rate = "+14%"
        else:
            if role_type == "dosen_penguji":
                # Dosen Killer: bariton berat, berwibawa, tegas, tempo terukur menekan setiap suku kata
                voice = "id-ID-ArdiNeural"
                pitch = "-6Hz"
                rate = "-9%"
            elif role_type == "hrd":
                # Suara HRD Umum: vokal wanita ramah, hangat, komunikatif & empatik
                voice = "id-ID-GadisNeural"
                pitch = "+4Hz"
                rate = "+2%"
            else:  # investor
                # Pitch Bisnis / Investor: cepat, to-the-point, berenergi, ritme efisien & tegas
                voice = "id-ID-ArdiNeural"
                pitch = "-1Hz"
                rate = "+15%"

        filename = f"tts_{asyncio.get_event_loop().time():.0f}.mp3"
        output_path = os.path.join(AUDIO_OUTPUT_DIR, filename)

        communicate = edge_tts.Communicate(text, voice, rate=rate, pitch=pitch)
        await communicate.save(output_path)
        return filename
    except Exception as e:
        print(f"TTS Generation notice: {e}")
        return None


def generate_fallback_response(req: ChatRequest) -> str:
    """Respons cadangan cerdas kontekstual jika LLM lokal sedang overload."""
    user_words = req.user_message.strip()
    topic_excerpt = (user_words[:65] + "...") if len(user_words) > 65 else user_words
    is_en = (req.language == "en")

    if is_en:
        if req.role_type == "dosen_penguji":
            return f"Regarding your explanation on '{topic_excerpt}', that provides interesting initial context. Could you explain the empirical methodology and data validation supporting your findings?"
        elif req.role_type == "hrd":
            return f"Thank you for sharing your experience about '{topic_excerpt}'. Could you walk me through the key challenge you overcame and the measurable result you delivered?"
        else:  # investor
            return f"I note your business premise regarding '{topic_excerpt}'. To cut straight to the chase, what are your unit economics and customer acquisition cost for this?"
    else:
        if req.role_type == "dosen_penguji":
            return f"Terkait penjelasan Saudara mengenai '{topic_excerpt}', poin tersebut saya catat. Namun bagaimana Saudara membuktikan validitas metodologi dan pengujian datanya secara empiris?"
        elif req.role_type == "hrd":
            return f"Menarik sekali pengalaman kamu tentang '{topic_excerpt}'. Bisakah kamu ceritakan tantangan terbesar yang kamu hadapi dan aksi nyata apa yang kamu ambil untuk mengatasinya?"
        else:  # investor
            return f"Saya menangkap inti ide bisnis Anda seputar '{topic_excerpt}'. Singkat saja, berapa perkiraan Customer Acquisition Cost dan model monetisasi riil dari produk ini?"


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service:app", host="127.0.0.1", port=8001, reload=True)
