"""
VOIC Local AI Service (FastAPI + Ollama + TTS)
Jembatan antara Laravel Backend dan Otak AI Lokal di Laptop
Optimized for Qwen2.5:3B Hyper-Natural Spoken Roleplay (Grok/Rudy Style)
"""

import os
import re
import time
import uuid
import random
import asyncio
from contextlib import asynccontextmanager
import httpx
from typing import List, Optional, Dict, Any
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from pydantic import BaseModel
from ai_learner import ai_learner

OLLAMA_BASE_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")
DEFAULT_MODEL = os.getenv("OLLAMA_MODEL", "qwen2.5:3b")
AUDIO_OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "audio_outputs")
os.makedirs(AUDIO_OUTPUT_DIR, exist_ok=True)


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
    """Background task: menjaga model tetap hangat di RAM/VRAM & membersihkan file audio lama setiap 15 menit."""
    while True:
        try:
            cleanup_old_audio_files()
            async with httpx.AsyncClient(timeout=5.0) as client:
                await client.post(
                    f"{OLLAMA_BASE_URL}/api/chat",
                    json={
                        "model": DEFAULT_MODEL,
                        "messages": [{"role": "user", "content": "hi"}],
                        "stream": False,
                        "keep_alive": "60m",
                    },
                )
        except Exception:
            pass
        await asyncio.sleep(900)  # 15 menit


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Lifespan context manager modern (menggantikan @app.on_event('startup') yang deprecated)."""
    task = asyncio.create_task(periodic_warmup_and_cleanup())
    yield
    task.cancel()


app = FastAPI(title="VOIC AI Service", version="1.2.0", lifespan=lifespan)

# CORS middleware agar Laravel frontend/backend bisa request tanpa halangan
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


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


# =====================================================================
# DYNAMIC FEW-SHOT EXAMPLES (KUNCI GAYA BICARA LUWES ALA GROK UNTUK 3B)
# =====================================================================
FEW_SHOT_ID = {
    "hrd": [
        {
            "role": "user",
            "content": "Di tempat kerja sebelumnya saya selalu memimpin tim untuk menyelesaikan proyek tepat waktu.",
        },
        {
            "role": "assistant",
            "content": "Oke, paham. Tapi jujur nih, waktu tenggat waktu mepet dan ada anggota tim yang mogok kerja, gimana cara konkret kamu beresin kekacauan itu tanpa panik?",
        },
        {
            "role": "user",
            "content": "Saya tipenya orang yang cepat belajar dan mudah beradaptasi dengan lingkungan baru yang serba cepat.",
        },
        {
            "role": "assistant",
            "content": "Hmm, klaim cepat belajar itu sering banget saya dengar dari pelamar lain. Coba ceritain satu situasi nyata saat kamu dilempar ke proyek yang teknologinya asing, apa hasil konkret yang kamu berikan di minggu pertama?",
        },
    ],
    "dosen_penguji": [
        {
            "role": "user",
            "content": "Penelitian saya menggunakan metode klasifikasi dengan 500 data sampel untuk mendeteksi anomali sistem.",
        },
        {
            "role": "assistant",
            "content": "Wah, bentar deh, 500 sampel itu terlalu sedikit buat membuktikan keandalan model klasifikasi kamu. Kamu yakin hasil akurasimu ini valid secara statistik, atau jangan-jangan cuma kebetulan bagus karena overfit?",
        },
        {
            "role": "user",
            "content": "Tujuan utama aplikasi ini dibuat adalah untuk mempermudah operasional UMKM agar pencatatan keuangannya lebih rapi.",
        },
        {
            "role": "assistant",
            "content": "Tunggu dulu, jangan muter-muter di tujuan normatif yang klise begitu. Coba langsung ke substansi: apa celah metodologi dari sistem pencatatan yang ada sekarang sampai skripsimu ini merasa perlu dibuat?",
        },
    ],
    "investor": [
        {
            "role": "user",
            "content": "Pasar industri ini di Indonesia potensinya mencapai puluhan triliun rupiah dan belum banyak kompetitor besar.",
        },
        {
            "role": "assistant",
            "content": "Waduh, saya nggak butuh angka estimasi pasar makro yang bombastis begitu. Langsung ke angkanya aja: berapa biaya akuisisi per pelanggan kamu sekarang, dan butuh berapa bulan sampai duit modalnya balik?",
        },
        {
            "role": "user",
            "content": "Kami berencana mengumpulkan basis pengguna gratis sebanyak-banyaknya dulu, baru setelah itu merilis fitur premium berbayar.",
        },
        {
            "role": "assistant",
            "content": "Hmm, strategi bakar uang begitu udah basi dan bikin banyak startup bangkrut. Siapa segmen spesifik yang bersedia bayar produkmu hari ini juga, dan kenapa mereka nggak pindah ke solusi gratisan lain?",
        },
    ],
}

FEW_SHOT_EN = {
    "hrd": [
        {
            "role": "user",
            "content": "In my last role, I was always proactive in helping the team inspect product quality before shipping to clients.",
        },
        {
            "role": "assistant",
            "content": "Oke, fair enough. But let's be real, when a defective batch inevitably slipped through to an angry client, how did you personally step in and resolve the chaos?",
        },
        {
            "role": "user",
            "content": "I consider myself a fast learner who thrives under high pressure and adapts seamlessly to any new environment.",
        },
        {
            "role": "assistant",
            "content": "Hmm, look, every single candidate claims they learn fast. Walk me through one actual situation where you were thrown into an unfamiliar tech stack—what did you deliver in your first two weeks?",
        },
    ],
    "dosen_penguji": [
        {
            "role": "user",
            "content": "My research applies a deep learning classification model trained on 500 sample images to detect structural anomalies.",
        },
        {
            "role": "assistant",
            "content": "Wait a second, 500 samples is a microscopic dataset to claim near-flawless accuracy. How did you statistically prove your model didn't just memorize the training noise and overfit?",
        },
        {
            "role": "user",
            "content": "The primary objective of this system is to help small local businesses manage their daily transactions much more efficiently.",
        },
        {
            "role": "assistant",
            "content": "Hold on, cut out the generic, normative fluff and get straight to the point. What is the fundamental methodological flaw in existing solutions that your research actually solves?",
        },
    ],
    "investor": [
        {
            "role": "user",
            "content": "The addressable market in our region is projected to reach billions of dollars with almost no dominant players yet.",
        },
        {
            "role": "assistant",
            "content": "Look, I couldn't care less about macroeconomic vanity slides. Skip to the real numbers: what is your actual customer acquisition cost right now, and how many months until that customer becomes net profitable?",
        },
        {
            "role": "user",
            "content": "We plan to acquire a massive pool of free active users first, and then monetize them with premium tier features later.",
        },
        {
            "role": "assistant",
            "content": "Waduh, wait, free users don't keep the lights on or pay the cloud bills. Who is actually willing to pay real cash for this product today, and why won't they just churn tomorrow?",
        },
    ],
}


def clean_spoken_output(text: str) -> str:
    """Membersihkan simbol markdown, baris baru ganda, dan teks bocoran instruksi agar murni suara lisan."""
    if not text:
        return ""
    # Hapus tag instruksi jika model tidak sengaja mengulanginya
    text = re.sub(r"\[INGAT:.*?\]", "", text, flags=re.DOTALL | re.IGNORECASE)
    text = re.sub(r"\[REMINDER:.*?\]", "", text, flags=re.DOTALL | re.IGNORECASE)
    # Hapus bullet list atau numbering di awal baris/teks (misal: "1. ", "- ", "* ")
    text = re.sub(r"^\s*[\-\*\•\d+\.]+\s*", "", text, flags=re.MULTILINE)
    # Hapus markdown (*, #, _, `, ~)
    text = re.sub(r"[\*\#\_\`\~]+", "", text)
    # Hapus awalan nama role atau pembicara seperti "Prof. Bambang:", "Pak William:", "Nadia:", "HRD:", dll.
    text = re.sub(r"^[A-Za-z\.\s]{2,25}:\s*", "", text.strip())
    # Satukan menjadi spasi tunggal agar jadi satu paragraf lisan mengalir
    text = re.sub(r"\s+", " ", text)
    text = text.strip().strip('"').strip("'")

    # Pastikan kalimat berakhir dengan tanda baca yang utuh jika terpotong num_predict
    if text and not text.endswith((".", "?", "!")):
        last_punct = max(text.rfind("."), text.rfind("?"), text.rfind("!"))
        if last_punct > 20:
            text = text[: last_punct + 1]

    # Aturan Mutlak: Maksimal 2 kalimat pendek dalam satu paragraf
    raw_sentences = [s.strip() for s in re.split(r"(?<=[.?!])\s+", text) if s.strip()]
    if not raw_sentences:
        return ""

    # Gabungkan interjeksi/awalan pendek (<= 4 kata, misal 'Wah, bentar deh...' atau 'Hmm...') dengan kalimat berikutnya
    sentences = []
    i = 0
    while i < len(raw_sentences):
        s = raw_sentences[i]
        if i == 0 and len(s.split()) <= 4 and i + 1 < len(raw_sentences) and not s.endswith("?"):
            sentences.append(f"{s} {raw_sentences[i+1]}")
            i += 2
        else:
            sentences.append(s)
            i += 1

    if len(sentences) > 2:
        first_sentence = sentences[0]
        questions = [s for s in sentences[1:] if s.endswith("?")]
        if questions:
            last_sentence = questions[-1]
        else:
            last_sentence = sentences[-1]
            if not last_sentence.endswith("?"):
                last_sentence = last_sentence.rstrip(".!") + "?"
        text = f"{first_sentence} {last_sentence}"
    elif len(sentences) == 2:
        text = f"{sentences[0]} {sentences[1]}"
    elif len(sentences) == 1:
        text = sentences[0]

    return text.strip()


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
    menganalisis koherensi semantik, dan memproses dialog lisan ala Grok/Rudy.
    """
    # 1. Analisis status ekspresi wajah asli dari kamera pengguna
    facial_note = ""
    if req.facial_status:
        f = req.facial_status
        if f.face_detected is False or (f.eye_contact_ratio is not None and f.eye_contact_ratio < 15):
            facial_note = "Peserta tidak menatap kamera."
        elif f.eye_contact_ratio is not None and f.eye_contact_ratio < 60:
            facial_note = f"Kontak mata peserta kurang fokus ({f.eye_contact_ratio:.0f}%)."
        elif f.emotion in ("tegang", "nervous"):
            facial_note = "Wajah peserta terlihat agak tegang."

    # 2. Temukan pertanyaan AI sebelumnya untuk evaluasi koherensi
    last_ai_question = ""
    for msg in reversed(req.conversation_history or []):
        if msg.role in ("assistant", "ai"):
            last_ai_question = msg.content
            break

    # 3. Deep Learning Coherence & Salient Entities
    coherence_score, coherence_cat = ai_learner.evaluate_semantic_coherence(
        last_ai_question=last_ai_question,
        user_reply=req.user_message,
        topic_context=req.context_material,
    )
    salient_entities = ai_learner.extract_salient_entities(req.user_message)

    # 4. Racik Ultra-Compact System Prompt (Khusus Model 3B agar tidak Instruction Overload!)
    is_en = (req.language == "en")
    role_raw = (req.role_type or "hrd").lower()
    if "investor" in role_raw or "bisnis" in role_raw or "pebisnis" in role_raw:
        role_key = "investor"
    elif "dosen" in role_raw or "penguji" in role_raw:
        role_key = "dosen_penguji"
    else:
        role_key = "hrd"

    if is_en:
        persona_map = {
            "hrd": (
                f"You are {req.role_name}, a Senior HR Recruiter on a live phone call with a candidate. "
                "Personality: relaxed, fluid, candid, never rigid, but razor-sharp at uncovering real substance behind candidate claims using behavioral probes based on real experience."
            ),
            "dosen_penguji": (
                f"You are {req.role_name}, a Thesis Examiner testing a student on a live voice call. "
                "Personality: critical, to-the-point, completely impatient with academic fluff or generic theories, and immediately targeting flaws in methodology, data validity, or logical reasoning."
            ),
            "investor": (
                f"You are {req.role_name}, a seasoned Venture Capitalist evaluating a pitch on a live voice call. "
                "Personality: ultra-skeptical, anti-BS, blunt, and directly demanding hard numbers, real market traction, unit economics, and how this business actually makes money."
            ),
        }
        stage_addon = (
            "CURRENT MODE: STRESS TEST. Be fiercely skeptical, probe every weak assumption, and test their composure!"
            if req.stage == "pressure_test"
            else ("CURRENT MODE: CLOSING. React briefly and ask for their final closing statement." if req.stage == "closing" else "")
        )
        context_addon = f"Context background: {req.context_material[:250]}" if req.context_material else ""
        system_instruction = (
            f"{persona_map[role_key]} {stage_addon}\n"
            f"{context_addon}\n"
            "ABSOLUTE LIVE VOICE CALL RULES (GROK/RUDY SPOKEN STYLE):\n"
            "1. This is a real-time spoken phone call, NOT a formal text essay or chat!\n"
            "2. MANDATORY OPENER: Always start your response with a spontaneous spoken reaction (e.g., 'Hmm...', 'Wait a second,', 'Look,', 'Fair enough, but', 'Hold on,', 'Oke, got it.').\n"
            "3. STRICT LENGTH: Maximum 2 short sentences in a single paragraph! Never ramble.\n"
            "4. STRICTLY FORBIDDEN: Never use bullet points, numbered lists, asterisks, or markdown formatting.\n"
            "5. MANDATORY CLOSER: Directly address the candidate's latest sentence and always finish with exactly 1 sharp question!"
        )
    else:
        persona_map = {
            "hrd": (
                f"Kamu adalah {req.role_name}, HR Recruiter senior yang sedang menelepon langsung kandidat. "
                "Kepribadianmu: santai, luwes, ekspresif, tidak kaku, bicara blak-blakan tapi ramah, dan sangat jeli membongkar klaim kandidat lewat pertanyaan pancingan berbasis pengalaman nyata."
            ),
            "dosen_penguji": (
                f"Kamu adalah {req.role_name}, Dosen Penguji Sidang yang menguji mahasiswa lewat panggilan suara langsung. "
                "Kepribadianmu: kritis, to-the-point, blak-blakan, tidak sabar dengan basa-basi atau teori klise, dan langsung menyerang celah metodologi atau logika berpikir lawan bicara."
            ),
            "investor": (
                f"Kamu adalah {req.role_name}, Investor bisnis/Venture Capitalist kawakan pada sesi pitching langsung. "
                "Kepribadianmu: skeptis tingkat tinggi, anti omong kosong, blak-blakan, benci janji manis tanpa bukti, dan langsung menagih angka nyata, traksi pasar, serta cara menghasilkan uang."
            ),
        }
        stage_addon = (
            "MODE SAAT INI: UJI TEKANAN (STRESS TEST). Bersikaplah lebih menantang, ragukan klaim lawan bicara, dan uji mental mereka!"
            if req.stage == "pressure_test"
            else ("MODE SAAT INI: PENUTUP. Tanggapi singkat lalu minta closing statement terakhir dari peserta." if req.stage == "closing" else "")
        )
        context_addon = f"Konteks materi peserta: {req.context_material[:250]}" if req.context_material else ""
        system_instruction = (
            f"{persona_map[role_key]} {stage_addon}\n"
            f"{context_addon}\n"
            "ATURAN MUTLAK SUARA LISAN (GAYA OBROLAN LANGSUNG GROK/RUDY):\n"
            "1. Ini panggilan suara langsung, BUKAN chat teks atau artikel formal!\n"
            "2. AWALAN WAJIB: Setiap respon wajib diawali reaksi lisan spontan (seperti: 'Hmm...', 'Wah, bentar deh...', 'Waduh,', 'Oke, paham.', 'Tunggu dulu,', 'Lho,').\n"
            "3. BATASAN PANJANG: Maksimal 2 kalimat pendek dalam SATU paragraf langsung! Dilarang bertele-tele.\n"
            "4. DILARANG KERAS menggunakan bullet points, tanda strip, penomoran, atau format markdown apa pun.\n"
            "5. AKHIRAN WAJIB: Selalu tanggapi poin kalimat terakhir lawan bicara dan tutup dengan TEPAT 1 pertanyaan tajam yang nyambung!"
        )

    # 5. Bangun Array Messages: System + Few-Shot Pancingan + Sliding Window History (Maks 6 pesan)
    messages = [{"role": "system", "content": system_instruction.strip()}]

    few_shots = FEW_SHOT_EN.get(role_key, FEW_SHOT_EN["hrd"]) if is_en else FEW_SHOT_ID.get(role_key, FEW_SHOT_ID["hrd"])
    messages.extend(few_shots)

    for msg in (req.conversation_history or [])[-6:]:
        role_label = "assistant" if msg.role in ("assistant", "ai") else "user"
        messages.append({"role": role_label, "content": msg.content})

    # 6. Trik Injeksi Pengingat di Pesan Terakhir User (Anti-Ngelantur 100% Nyambung)
    camera_hint = f" (Catatan visual: {facial_note})" if facial_note and req.stage == "pressure_test" else ""
    if is_en:
        injected_user_msg = (
            f"{req.user_message}{camera_hint}\n\n"
            f"[REMINDER: Spoken call mode. Start with spontaneous reaction (Hmm/Look/Wait a second), react to the latest statement in max 2 sentences (1 paragraph, NO bullets), end with 1 sharp question!]"
        )
    else:
        injected_user_msg = (
            f"{req.user_message}{camera_hint}\n\n"
            f"[INGAT: Mode obrolan telepon langsung. Wajib awali reaksi lisan spontan (Hmm.../Wah, bentar deh.../Waduh,/Oke, paham./Tunggu dulu,), tanggapi kalimat terakhir user maks 2 kalimat pendek (1 paragraf, TANPA bullet point atau markdown), dan tutup dengan 1 pertanyaan tajam!]"
        )

    messages.append({"role": "user", "content": injected_user_msg})

    # 7. Panggil Ollama (Tanpa '\n\n' di stop token & num_predict=125 agar pertanyaan tidak terpotong!)
    ai_reply_text = ""
    try:
        async with httpx.AsyncClient(timeout=22.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": messages,
                    "stream": False,
                    "keep_alive": "60m",
                    "options": {
                        "temperature": 0.68,
                        "repeat_penalty": 1.15,
                        "top_p": 0.85,
                        "top_k": 30,
                        "num_predict": 125,  # Ruang napas ideal untuk subword Bahasa Indonesia (2 kalimat + 1 tanya)
                        "num_ctx": 4096,     # Mencegah System Prompt & History terpotong
                        "num_thread": 4,
                        "stop": ["Peserta:", "User:", "Candidate:", "Penguji:", "Kandidat:", "[INGAT:", "[REMINDER:"],
                    },
                },
            )
            if ollama_res.status_code == 200:
                raw_reply = ollama_res.json().get("message", {}).get("content", "")
                ai_reply_text = clean_spoken_output(raw_reply)
    except Exception as e:
        print(f"Ollama inference error: {e}")

    if not ai_reply_text:
        ai_reply_text = generate_natural_fallback(req)

    # 8. Rekam ke Dataset Deep Learning Berkelanjutan
    try:
        f_dict = (
            req.facial_status.model_dump()
            if req.facial_status and hasattr(req.facial_status, "model_dump")
            else (req.facial_status.dict() if req.facial_status else {})
        )
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

    # 9. Sintesis Suara (TTS) dengan Timeout 4.5 Detik
    audio_filename = None
    if req.generate_voice:
        try:
            audio_filename = await asyncio.wait_for(
                generate_tts_audio(ai_reply_text, req.role_type, req.language or "id"),
                timeout=4.5,
            )
        except (asyncio.TimeoutError, Exception):
            audio_filename = None

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
        summary_dialogue += f"\n{msg.role}: {msg.content}"

    is_en = (req.language == "en")
    lang_inst = (
        "Strictly write the final verdict in natural, spoken-style ENGLISH (2-3 sentences max)."
        if is_en
        else "Berikan kesimpulan evaluasi akhir peserta ini (maksimal 2-3 kalimat) dengan gaya bicara langsung, jujur, dan mengena dalam Bahasa Indonesia."
    )

    prompt = (
        f"Session finished in {req.duration_seconds} seconds.\n"
        f"SENSOR TELEMETRY:\n"
        f"- Webcam Eye Contact & Expression: {req.face_score:.1f}/100\n"
        f"- Mic Articulation & Confidence: {req.voice_score:.1f}/100\n"
        f"- Overall Score: {req.overall_score:.1f}/100\n"
        f"Recent Transcript:{summary_dialogue}\n\n"
        f"Instruction: As {req.role_name} ({req.role_type}), {lang_inst}"
    )

    conclusion_text = ""
    try:
        async with httpx.AsyncClient(timeout=20.0) as client:
            ollama_res = await client.post(
                f"{OLLAMA_BASE_URL}/api/chat",
                json={
                    "model": DEFAULT_MODEL,
                    "messages": [
                        {
                            "role": "system",
                            "content": f"Kamu adalah {req.role_name}. Berikan feedback penutup yang jujur, konstruktif, dan manusiawi tanpa format bullet points.",
                        },
                        {"role": "user", "content": prompt},
                    ],
                    "stream": False,
                    "keep_alive": "60m",
                    "options": {
                        "temperature": 0.65,
                        "num_predict": 130,
                        "num_ctx": 4096,
                    },
                },
            )
            if ollama_res.status_code == 200:
                conclusion_text = clean_spoken_output(ollama_res.json()["message"]["content"])
    except Exception:
        pass

    if not conclusion_text:
        if is_en:
            conclusion_text = (
                f"Alright, wrapping up—your camera presence hit {req.face_score:.0f} and vocal delivery got {req.voice_score:.0f}, "
                f"giving you an overall {req.overall_score:.0f} out of 100. Solid effort, just keep sharpening your concrete examples next time!"
            )
        else:
            conclusion_text = (
                f"Oke, sesi kita cukup sampai di sini ya—dari pantauan kamera nilaimu di {req.face_score:.0f} dan kelancaran ngomong di {req.voice_score:.0f}, "
                f"jadi total skor kamu {req.overall_score:.0f} dari 100. Secara keseluruhan udah lumayan oke, tinggal pertajam lagi bukti konkret pas ngejawab ya!"
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
    """Sintesis suara instan berbasis karakter peran AI."""
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
    """Menghasilkan file audio MP3 menggunakan edge-tts dengan prosodi karakter peran unik & nama anti-bentrok."""
    try:
        import edge_tts

        if language == "en":
            if role_type == "dosen_penguji":
                voice, pitch, rate = "en-US-GuyNeural", "-5Hz", "-5%"
            elif role_type == "hrd":
                voice, pitch, rate = "en-US-JennyNeural", "+3Hz", "+5%"
            else:
                voice, pitch, rate = "en-US-BrianNeural", "+0Hz", "+12%"
        else:
            if role_type == "dosen_penguji":
                voice, pitch, rate = "id-ID-ArdiNeural", "-4Hz", "-4%"
            elif role_type == "hrd":
                voice, pitch, rate = "id-ID-GadisNeural", "+3Hz", "+6%"
            else:
                voice, pitch, rate = "id-ID-ArdiNeural", "-1Hz", "+12%"

        # Nama unik dengan milidetik + UUID pendek untuk mencegah race condition / cache browser
        filename = f"tts_{int(time.time() * 1000)}_{uuid.uuid4().hex[:6]}.mp3"
        output_path = os.path.join(AUDIO_OUTPUT_DIR, filename)

        communicate = edge_tts.Communicate(text, voice, rate=rate, pitch=pitch)
        await communicate.save(output_path)
        return filename
    except Exception as e:
        print(f"TTS Generation notice: {e}")
        return None


def generate_natural_fallback(req: ChatRequest) -> str:
    """Respons cadangan bergaya obrolan langsung (Grok/Rudy style) jika Ollama sedang sibuk atau offline."""
    is_en = (req.language == "en")
    role_raw = (req.role_type or "hrd").lower()
    if "investor" in role_raw or "bisnis" in role_raw or "pebisnis" in role_raw:
        role_key = "investor"
    elif "dosen" in role_raw or "penguji" in role_raw:
        role_key = "dosen_penguji"
    else:
        role_key = "hrd"

    if is_en:
        fallbacks = {
            "hrd": [
                "Oke, fair enough. But can you tell me about one real situation where that completely blew up, and what specific action you took to fix it?",
                "Hmm, look, that sounds neat in theory. When you actually executed that on the ground, what was the biggest pushback you faced from your team?",
                "Right, got it. How did you personally measure the success of that effort, and what did you learn when things didn't go according to plan?",
            ],
            "dosen_penguji": [
                "Wait a second, cut out the generic theoretical talk. What is the single biggest empirical vulnerability in that methodology, and why should we trust your conclusions?",
                "Hold on, your logical premise seems shaky right there. How did you rigorously verify that this result wasn't just caused by sample selection bias?",
                "Hmm, I hear your point, but the scientific proof still sounds weak. Why didn't you benchmark your findings against a well-established alternative approach?",
            ],
            "investor": [
                "Look, skip the macro market fluff and talk real numbers. What is your actual customer acquisition cost today, and how fast does this generate real profit?",
                "Waduh, wait, free users don't pay the server bills. Who is actually paying you cold cash right now, and why won't they switch to a free alternative tomorrow?",
                "Fair enough on the concept, but let's talk unit economics. If I write you a check today, how many months until this business reaches net profitability?",
            ],
        }
    else:
        fallbacks = {
            "hrd": [
                "Oke, paham. Tapi boleh ceritain satu contoh nyata pas kamu ngadepin masalah itu langsung, dan apa tindakan spesifik yang kamu ambil?",
                "Hmm, kedengarannya meyakinkan di awal. Tapi pas situasi di lapangan nggak sesuai rencana kamu, gimana cara kamu beresin kekacauan itu tanpa panik?",
                "Wah, bentar deh, klaim kamu menarik banget. Waktu eksekusi hal itu, apa kendala paling berat yang pernah bikin kamu hampir nyerah dan gimana solusinya?",
            ],
            "dosen_penguji": [
                "Tunggu dulu, penjelasan kamu tadi masih terlalu berputar-putar di teori umum. Coba tunjukin kelemahan paling fatal dari metodologi yang kamu pilih itu apa?",
                "Wah, bentar deh, logika argumen kamu tadi kerasa ada celah di validitas datanya. Gimana cara kamu buktiin kalau hasil analisismu itu valid dan bukan bias peneliti?",
                "Hmm, saya tangkap maksud kamu, tapi jujur pembuktian empirisnya masih kerasa kurang kuat. Kenapa kamu nggak pakai pendekatan alternatif yang jauh lebih teruji?",
            ],
            "investor": [
                "Waduh, jangan muter-muter di janji manis potensi pasar dulu deh. Berapa angka pasti customer acquisition cost kamu sekarang, dan butuh berapa bulan sampai modalnya balik?",
                "Hmm, jujur saya skeptis banget kalau model bisnismu cuma mengandalkan fitur itu. Berapa banyak pelanggan berbayar yang udah komitmen hari ini, dan kenapa mereka nggak pindah ke kompetitor?",
                "Oke, paham konsepnya, tapi balik lagi ke uang tunai. Kalau saya suntik dana sekarang, kapan titik impas unit economics bisnismu bisa tercapai?",
            ],
        }

    options = fallbacks.get(role_key, fallbacks["hrd"])
    return random.choice(options)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("ai_service:app", host="127.0.0.1", port=8001, reload=False)