"""
VOIC Exam Engine: endpoint untuk alur ujian 3 bagian (/exam/respond, /exam/improve).

Prinsip desain:
- Laravel (ExaminerService::buildMessages) adalah SATU-SATUNYA sumber format prompt.
  Service ini hanya menjalankan model, membersihkan output, dan menjaga kualitas.
  Dengan begitu data training (diekspor oleh `php artisan voic:export-training-data`)
  memakai format yang persis sama dengan saat inferensi.
- Model menulis seluruh giliran dengan kata-katanya sendiri: menanggapi jawaban, lalu
  menggali lebih dalam (follow_up), membawa topik berikutnya dari rencana ujian (next),
  atau menjelaskan ulang pertanyaan yang belum dipahami (clarify). Rencana ujian hanya
  panduan topik; naskah Laravel dipakai sebagai cadangan jika model gagal.
- Setiap balasan dicek oleh quality gate. Balasan yang lolos dipanen ke
  datasets/harvest/exam_turns.jsonl sebagai kandidat data latih (perlu direview).
"""

from __future__ import annotations

import asyncio
import json
import os
import re
import time
from typing import Dict, List, Optional

import httpx
from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from pydantic import BaseModel, Field

OLLAMA_BASE_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")
EXAM_MODEL = os.getenv("VOIC_EXAM_MODEL", os.getenv("OLLAMA_MODEL", "voic-coach"))
HARVEST_FILE = os.path.join(os.path.dirname(__file__), "datasets", "harvest", "exam_turns.jsonl")

# Laptop CPU bisa butuh 10-30 detik hanya untuk MEMUAT model pertama kali; jangan menyerah terlalu cepat.
LLM_TIMEOUT = float(os.getenv("VOIC_LLM_TIMEOUT", "90"))
# 4096 token: cukup untuk persona, ringkasan seluruh sesi, dan ±12 giliran terakhir tanpa memotong
# instruksi sistem (jika konteks penuh, Ollama membuang bagian AWAL prompt = persona hilang).
# Ollama menyimpan cache awalan prompt antargiliran, jadi di CPU yang dihitung ulang hanya bagian baru.
NUM_CTX = int(os.getenv("VOIC_NUM_CTX", "4096"))
# Qwen3 punya mode "berpikir" yang menulis penalaran panjang dulu. Untuk percakapan suara itu terlalu
# lambat, jadi dimatikan. Model lain mengabaikan opsi ini.
THINK = os.getenv("VOIC_THINK", "0") == "1"
# Model cloud lewat Ollama (mis. kimi-k3:cloud) berjalan di server Ollama, bukan di laptop.
# Kalau gagal (internet mati, belum `ollama signin`, kuota habis), giliran itu otomatis
# dijawab model lokal. Batas waktu dibuat lebih pendek agar peserta tidak menunggu lama.
CLOUD_TIMEOUT = float(os.getenv("VOIC_CLOUD_TIMEOUT", "45"))


def is_cloud_model(name: Optional[str]) -> bool:
    """Model ber-tag :cloud / -cloud di Ollama dijalankan di server Ollama, bukan lokal."""
    lowered = (name or "").lower()
    return lowered.endswith(":cloud") or lowered.endswith("-cloud")


def chat_payload(model: str, messages: List[Dict[str, str]], stream: bool, num_predict: int, temperature: float) -> Dict:
    """Isi request /api/chat. Model cloud tidak memakai opsi memori lokal (num_ctx, keep_alive), dan
    model penalar seperti Kimi K3 memakai token untuk berpikir dulu, jadi panjangnya tidak dibatasi di sini
    (balasan tetap dipotong ke kalimat utuh oleh clean_reply)."""
    if is_cloud_model(model):
        return {"model": model, "messages": messages, "stream": stream, "options": {"temperature": temperature}}
    return {
        "model": model,
        "messages": messages,
        "stream": stream,
        "think": THINK,
        "keep_alive": KEEP_ALIVE,
        "options": {
            "temperature": temperature,
            "top_p": 0.9,
            "repeat_penalty": 1.12,
            "num_predict": num_predict,
            "num_ctx": NUM_CTX,
        },
    }


async def candidate_models(requested: Optional[str]) -> List[str]:
    """Model yang dicoba berurutan: yang diminta Laravel (VOIC_AI_MODEL), lalu model lokal sebagai cadangan."""
    local = await resolve_model()
    if requested and requested != local:
        return [requested, local]
    return [local]


def model_timeout(model: str) -> httpx.Timeout:
    return httpx.Timeout(CLOUD_TIMEOUT if is_cloud_model(model) else LLM_TIMEOUT, connect=3.0)
KEEP_ALIVE = os.getenv("VOIC_KEEP_ALIVE", "2h")

router = APIRouter(prefix="/exam", tags=["exam"])

_model_cache = {"name": None, "checked": 0.0}


async def resolve_model() -> str:
    """Pakai EXAM_MODEL jika ada di Ollama; jika belum dibuat, pakai model qwen/llama yang tersedia.

    Tanpa ini, lupa menjalankan `ollama create voic-coach` membuat semua giliran gagal diam-diam.
    """
    now = time.time()
    if _model_cache["name"] and now - _model_cache["checked"] < 60:
        return _model_cache["name"]
    chosen = EXAM_MODEL
    try:
        async with httpx.AsyncClient(timeout=2.0) as client:
            res = await client.get(f"{OLLAMA_BASE_URL}/api/tags")
            names = [m["name"] for m in res.json().get("models", [])] if res.status_code == 200 else []
        if names and not any(n == EXAM_MODEL or n.startswith(EXAM_MODEL + ":") for n in names):
            # Urutan = prioritas: model yang paling nyambung dalam bahasa Indonesia lebih dulu.
            priority = ("qwen3:8b", "qwen3:4b", "qwen3", "qwen2.5:7b", "qwen2.5:3b", "qwen", "gemma", "llama3")
            preferred = [n for prefix in priority for n in names if n.startswith(prefix)]
            chosen = (preferred or names)[0]
            print(f"[VOIC] Model '{EXAM_MODEL}' belum ada di Ollama, memakai '{chosen}'. "
                  "Buat dengan: ollama create voic-coach -f Modelfile")
    except Exception:
        pass
    _model_cache.update(name=chosen, checked=now)
    return chosen

STOPWORDS = set(
    """yang dan di ke dari ini itu untuk dengan pada adalah saya aku kami kita anda kamu juga akan ada tidak bisa sudah atau
    jadi karena dalam seperti lebih sangat tersebut oleh agar bahwa sebagai ya nya lah kan pun saja lagi mau apa bagaimana
    kenapa mengapa tapi tetapi namun kalau jika maka sih dong deh nah oke baik terus gitu begitu banyak the a an and or of to
    in on for with is are was were be i my we our you it that this as at by so but if then very really just also have has""".split()
)

# Kalimat template lama yang membuat percakapan terasa robotik. Jangan pernah dipanen.
BANNED_PATTERNS = [
    r"mengenai pembahasan '.*' yang saudara sampaikan",
    r"poin tersebut saya catat\. namun bagaimana saudara membuktikan",
    r"menarik sekali penjelasan kamu seputar '",
    r"sebagai (sebuah )?ai",
    r"as an ai",
    r"\bjawaban peserta\b",
    r"\bcandidate answer\b",
]


UNSURE_PATTERN = re.compile(
    r"\b(tidak tahu|nggak tahu|gak tahu|ga tau|nggak tau|kurang tahu|belum (menghitung|tahu|tau)|lupa|bingung|i don'?t know|not sure|no idea|haven'?t (calculated|measured))\b",
    re.IGNORECASE,
)


class ChatTurn(BaseModel):
    role: str
    content: str


class RespondRequest(BaseModel):
    session_id: Optional[int] = None
    mode: str
    role_type: str
    language: str = "id"
    directive: str = "react"  # react | follow_up | next | clarify | coach | coach_close
    part: int = 1
    question: str = ""
    answer: str = ""
    messages: List[ChatTurn]
    # Model Ollama untuk giliran ini (dari VOIC_AI_MODEL di .env Laravel), mis. "kimi-k3:cloud".
    model: Optional[str] = None


class RespondResponse(BaseModel):
    reply: str
    passed_quality: bool
    issues: List[str] = Field(default_factory=list)
    model: str
    latency_ms: int
    error: Optional[str] = None
    stats: Dict = Field(default_factory=dict)


class ImproveItem(BaseModel):
    key: int
    question: str
    answer: str


class ImproveRequest(BaseModel):
    mode: str
    role_type: str
    language: str = "id"
    topic: Optional[str] = None
    items: List[ImproveItem]
    model: Optional[str] = None


# ---------------------------------------------------------------------------
# Output cleaning & quality gate
# ---------------------------------------------------------------------------

_THINK_BLOCK = re.compile(r"<think>.*?(</think>|$)", flags=re.S | re.IGNORECASE)


def strip_think(text: str) -> str:
    """Buang penalaran <think>...</think> (Qwen3/DeepSeek) agar tidak ikut diucapkan."""
    return _THINK_BLOCK.sub("", text or "").replace("</think>", "").strip()


class ThinkFilter:
    """Versi streaming dari strip_think: menahan teks di dalam <think>...</think>."""

    def __init__(self) -> None:
        self.pending = ""
        self.inside = False

    def feed(self, chunk: str) -> str:
        self.pending += chunk
        out = ""
        while self.pending:
            tag = "</think>" if self.inside else "<think>"
            index = self.pending.find(tag)
            if index >= 0:
                if not self.inside:
                    out += self.pending[:index]
                self.pending = self.pending[index + len(tag):]
                self.inside = not self.inside
                continue
            # Sisakan potongan yang mungkin awal sebuah tag ("<thi") untuk chunk berikutnya.
            keep = next((n for n in range(len(tag) - 1, 0, -1) if self.pending.endswith(tag[:n])), 0)
            if not self.inside:
                out += self.pending[: len(self.pending) - keep]
            self.pending = self.pending[len(self.pending) - keep:] if keep else ""
            break
        return out

    def flush(self) -> str:
        rest = "" if self.inside else self.pending
        self.pending = ""
        return rest


def clean_reply(text: str, max_sentences: int = 3) -> str:
    """Bersihkan markdown, label pembicara, dan potong ke kalimat utuh."""
    text = strip_think(text)
    if not text:
        return ""
    text = re.sub(r"\[[^\]]*\]", "", text)
    # Model kecil kadang membacakan tag instruksi ("FOKUS: ..."); buang kalimat itu, sisanya tetap dipakai.
    if leaks_tag(text):
        text = " ".join(s for s in re.split(r"(?<=[.?!])\s+|\n+", text) if s.strip() and not leaks_tag(s))
    text = re.sub(r"[\*\#\_\`\~]+", "", text)
    text = re.sub(r"^\s*[\-•\d]+[\.\)]\s*", "", text, flags=re.MULTILINE)
    # Label pembicara = beberapa kata berhuruf kapital ("Prof. Hendra:"), bukan kalimat seperti "Bagus. Coba bilang:".
    text = re.sub(r"^\s*[A-Z][A-Za-z\.]*(?:\s[A-Z][A-Za-z\.]*){0,3}:\s+", "", text.strip())
    text = re.sub(r"\s+", " ", text).strip()
    # Hanya buang tanda kutip yang membungkus seluruh balasan; contoh kalimat di dalamnya harus utuh.
    wrapped = re.match(r'^["\u201c](.*)["\u201d]$', text, flags=re.S)
    if wrapped and not re.search(r'["\u201c\u201d]', wrapped.group(1)):
        text = wrapped.group(1).strip()

    # A sentence may end with a closing quote after its punctuation ('... persen."'); keep it.
    if text and not re.search(r'[.?!]["\u201d\')\]]?$', text):
        cut = max(text.rfind("."), text.rfind("?"), text.rfind("!"))
        if cut > 15:
            text = text[: cut + 1]

    sentences = [s.strip() for s in re.split(r"(?<=[.?!])\s+", text) if s.strip()]
    return " ".join(sentences[:max_sentences]).strip()


# Spoken questions without a question mark: the scripted questions themselves are often imperatives
# ("Ceritakan pencapaian yang paling kamu banggakan.", "Describe a time you ...").
IMPERATIVE_ASK = re.compile(
    r"^[\"'“]?(?:coba\s+|tolong\s+|silakan\s+|please\s+)?"
    r"(?:ceritakan|jelaskan|sebutkan|bayangkan|gambarkan|uraikan|describe|tell me|explain|walk me|share|give me|imagine)\b",
    re.IGNORECASE,
)


def is_question(sentence: str) -> bool:
    sentence = (sentence or "").strip()
    return sentence.endswith("?") or bool(IMPERATIVE_ASK.match(sentence))


def count_questions(text: str) -> int:
    return sum(is_question(s) for s in re.split(r"(?<=[.?!])\s+", text or "") if s.strip())


def trim_questions(reply: str, directive: str) -> str:
    """Satu giliran = satu pertanyaan. Model kecil sering menumpuk dua pertanyaan; potong setelah yang pertama.

    Untuk react/coach_close (tidak boleh bertanya) buang kalimat tanya, selama masih ada kalimat lain.
    Deterministik, jadi tetap jalan di laptop lambat yang tidak sempat retry.
    """
    sentences = [s.strip() for s in re.split(r"(?<=[.?!])\s+", reply or "") if s.strip()]
    if directive in ("follow_up", "next", "clarify"):
        for index, sentence in enumerate(sentences):
            if is_question(sentence):
                return " ".join(sentences[: index + 1])
        return reply
    if directive in ("react", "coach_close"):
        kept = [s for s in sentences if not is_question(s)]
        return " ".join(kept) if kept else reply
    return reply


def content_words(text: str) -> set:
    words = re.findall(r"[a-zA-ZÀ-ɏ0-9]+", text.lower())
    return {w for w in words if len(w) > 3 and w not in STOPWORDS}


# Tag instruksi dari Laravel (ExaminerService::turnInstruction) yang tidak boleh ikut diucapkan.
LEAKED_TAG = re.compile(
    r"\b(FOKUS|KAITKAN|UMUMKAN|GALI|LANJUT|TANGGAPI|JELASKAN|FOCUS|LINK|ANNOUNCE|PROBE|NEXT|REACT|CLARIFY)\b\s*[:|\]]"
    r"|(?i:\bcek konsistensi\s*:|\bcheck consistency\s*:)"
)


def leaks_tag(text: str) -> bool:
    return bool(LEAKED_TAG.search(text or ""))


def _questions(text: str) -> List[str]:
    return [s.strip() for s in re.split(r"(?<=[.?!])\s+", text or "") if is_question(s)]


def _opener(text: str) -> str:
    match = re.match(r"\s*([^\W\d_]+)", (text or "").lower())
    return match.group(1) if match else ""


def repeats_question(reply: str, earlier: List[str]) -> bool:
    """Pertanyaan yang isinya sama dengan pertanyaan penguji sebelumnya (kemiripan kata isi >= 60%)."""
    previous = [content_words(q) for text in earlier for q in _questions(text)]
    for question in _questions(reply):
        words = content_words(question)
        if len(words) < 3:
            continue
        for old in previous:
            if len(old) >= 3 and len(words & old) / len(words | old) >= 0.6:
                return True
    return False


def earlier_examiner_lines(messages: List[Dict[str, str]]) -> List[str]:
    """Kalimat-kalimat penguji sebelumnya di prompt (untuk cek pengulangan)."""
    return [m.get("content", "") for m in messages if m.get("role") == "assistant"]


def quality_issues(reply: str, answer: str, directive: str, language: str, earlier: Optional[List[str]] = None) -> List[str]:
    """Daftar masalah kualitas. Kosong berarti balasan layak dipakai & dipanen.

    earlier = giliran penguji sebelumnya di sesi ini: dipakai untuk menolak pertanyaan yang diulang
    dan pembuka yang sama dua kali berturut-turut ("Menarik, ... Menarik, ...").
    """
    issues: List[str] = []
    lowered = reply.lower()
    words = reply.split()

    if not reply:
        return ["empty"]
    if leaks_tag(reply):
        issues.append("leaked_tag")
    if earlier:
        # A clarification is meant to re-ask the question after explaining it, so only new questions are checked.
        if directive in ("follow_up", "next") and repeats_question(reply, earlier):
            issues.append("repeated_question")
        if _opener(reply) and _opener(reply) == _opener(earlier[-1]):
            issues.append("repeated_opener")
    if len(words) < 3:
        issues.append("too_short")
    if len(words) > (85 if directive == "coach" else 55):
        issues.append("too_long")
    if any(re.search(p, lowered) for p in BANNED_PATTERNS):
        issues.append("banned_template")

    question_count = count_questions(reply)
    asks = directive in ("follow_up", "next", "clarify")
    if asks and question_count == 0:
        issues.append("follow_up_without_question")
    if asks and question_count > (2 if directive == "next" else 1):
        issues.append("multiple_questions")
    if directive == "coach" and question_count > 2:
        issues.append("multiple_questions")
    if directive in ("react", "coach_close") and question_count > 0:
        issues.append("react_with_question")

    # Active listening: tanggapan harus menyentuh isi jawaban, kecuali jawabannya sangat pendek
    # atau peserta bilang tidak tahu (balasan yang tepat justru menenangkan, bukan mengutip).
    answer_words = content_words(answer)
    if len(answer_words) >= 4 and directive != "clarify" and not UNSURE_PATTERN.search(answer):
        overlap = answer_words & content_words(reply)
        # Toleransi imbuhan Indonesia: cocokkan 5 huruf terakhir.
        if not overlap:
            tails = {w[-5:] for w in answer_words if len(w) >= 5}
            if not any(w[-5:] in tails for w in content_words(reply) if len(w) >= 5):
                issues.append("not_grounded_in_answer")

    if language == "id" and re.search(r"\b(the|and|you|your|what|how)\b", lowered) and not re.search(r"\b(yang|dan|anda|kamu|apa)\b", lowered):
        issues.append("wrong_language")

    return issues


def harvest(record: Dict) -> None:
    """Simpan balasan berkualitas sebagai kandidat data latih (append-only JSONL)."""
    try:
        os.makedirs(os.path.dirname(HARVEST_FILE), exist_ok=True)
        with open(HARVEST_FILE, "a", encoding="utf-8") as handle:
            handle.write(json.dumps(record, ensure_ascii=False) + "\n")
    except OSError as exc:
        print(f"Harvest notice: {exc}")


def ollama_stats(data: Dict) -> Dict:
    """Ringkas statistik waktu Ollama (nanodetik -> milidetik) untuk diagnosa kecepatan."""
    ms = lambda key: int((data.get(key) or 0) / 1_000_000)  # noqa: E731
    eval_count = int(data.get("eval_count") or 0)
    eval_ms = ms("eval_duration")
    return {
        "load_ms": ms("load_duration"),
        "prompt_tokens": int(data.get("prompt_eval_count") or 0),
        "prompt_ms": ms("prompt_eval_duration"),
        "output_tokens": eval_count,
        "output_ms": eval_ms,
        "tokens_per_second": round(eval_count / (eval_ms / 1000), 1) if eval_ms else None,
    }


async def ollama_chat(messages: List[Dict[str, str]], num_predict: int, temperature: float, stats: Optional[Dict] = None, model: Optional[str] = None) -> str:
    """Satu balasan utuh. Jika model yang diminta gagal, model lokal dicoba. stats["model"] = model yang menjawab."""
    models = await candidate_models(model)
    for index, name in enumerate(models):
        try:
            async with httpx.AsyncClient(timeout=model_timeout(name)) as client:
                response = await client.post(f"{OLLAMA_BASE_URL}/api/chat", json=chat_payload(name, messages, False, num_predict, temperature))
                response.raise_for_status()
                data = response.json()
            if stats is not None:
                stats.update(ollama_stats(data))
                stats["model"] = name
            return data.get("message", {}).get("content", "")
        except Exception as exc:
            if index == len(models) - 1:
                raise
            print(f"[VOIC] Model {name} gagal ({describe_error(exc)}), memakai model lokal {models[index + 1]}.")
    return ""


def describe_error(exc: Exception) -> str:
    """Pesan error yang bisa ditindaklanjuti operator booth."""
    if isinstance(exc, httpx.TimeoutException):
        return f"timeout: model belum selesai menjawab dalam {int(LLM_TIMEOUT)} detik (model terlalu besar untuk laptop ini, atau masih dimuat)"
    if isinstance(exc, httpx.ConnectError):
        return "ollama_unreachable: Ollama tidak berjalan di " + OLLAMA_BASE_URL
    if isinstance(exc, httpx.HTTPStatusError):
        body = exc.response.text[:200] if exc.response is not None else ""
        return f"ollama_http_{exc.response.status_code}: {body}"
    return f"{type(exc).__name__}: {exc}"


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@router.post("/respond", response_model=RespondResponse)
async def respond(req: RespondRequest):
    """Jalankan model untuk satu giliran penguji. Retry sekali jika gagal quality gate."""
    started = time.time()
    messages = [m.model_dump() for m in req.messages]
    max_sentences = {"next": 3, "follow_up": 2, "clarify": 2, "coach": 4, "coach_close": 3}.get(req.directive, 1)
    num_predict = {"next": 120, "follow_up": 90, "clarify": 100, "coach": 170, "coach_close": 110}.get(req.directive, 55)

    best_reply, best_issues = "", ["empty"]
    error: Optional[str] = None
    stats: Dict = {}
    for attempt in range(2):
        attempt_started = time.time()
        try:
            raw = await ollama_chat(
                messages,
                num_predict=num_predict,
                temperature=0.7 if attempt == 0 else 0.55,
                stats=stats,
                model=req.model,
            )
        except Exception as exc:  # Ollama mati / model belum di-pull / terlalu lambat
            error = describe_error(exc)
            print(f"Exam model error: {error}")
            break

        reply = trim_questions(clean_reply(raw, max_sentences=max_sentences + (1 if req.directive != "react" else 0)), req.directive)
        issues = quality_issues(reply, req.answer, req.directive, req.language, earlier_examiner_lines(messages))
        if len(issues) < len(best_issues):
            best_reply, best_issues = reply, issues
        if not issues:
            break
        # Di laptop yang lambat, mengulang membuat peserta menunggu dua kali; pakai hasil terbaik saja.
        if time.time() - attempt_started > 8:
            break

    # Balasan kosong = biarkan Laravel memakai composer offline.
    hard_fail = {"empty", "banned_template", "wrong_language", "leaked_tag"}
    if not best_reply or hard_fail & set(best_issues):
        best_reply = ""

    if best_reply and not best_issues:
        harvest({
            "ts": int(time.time()),
            "session_id": req.session_id,
            "mode": req.mode,
            "role_type": req.role_type,
            "language": req.language,
            "directive": req.directive,
            "part": req.part,
            "messages": messages,
            "reply": best_reply,
            "model": stats.get("model", EXAM_MODEL),
            "reviewed": False,
        })

    return RespondResponse(
        reply=best_reply,
        passed_quality=bool(best_reply) and not best_issues,
        issues=best_issues,
        model=stats.get("model") or await resolve_model(),
        latency_ms=int((time.time() - started) * 1000),
        error=error,
        stats=stats,
    )


@router.post("/warmup")
async def warmup():
    """Muat model ke RAM/VRAM sekarang, supaya peserta pertama tidak menunggu model dimuat."""
    stats: Dict = {}
    started = time.time()
    try:
        await ollama_chat([{"role": "user", "content": "Halo"}], num_predict=1, temperature=0.1, stats=stats)
        return {"ok": True, "model": await resolve_model(), "seconds": round(time.time() - started, 1), "stats": stats}
    except Exception as exc:
        return {"ok": False, "model": await resolve_model(), "seconds": round(time.time() - started, 1), "error": describe_error(exc)}


class PrimeRequest(BaseModel):
    messages: List[ChatTurn]
    model: Optional[str] = None


_prime_tasks: set = set()


@router.post("/prime")
async def prime(req: PrimeRequest):
    """
    Bacakan awal prompt sebuah sesi (persona + aturan + salam pembuka) ke model SEKARANG, di latar belakang.
    Ollama menyimpan hasilnya di cache, jadi saat peserta selesai menjawab pertanyaan pertama, model
    tinggal membaca jawabannya saja. Tanpa ini, giliran pertama menunggu 25-45 detik di laptop CPU.
    Langsung mengembalikan jawaban; pekerjaannya berjalan sambil peserta mendengarkan salam pembuka.
    """
    if is_cloud_model(req.model):
        return {"ok": True, "skipped": "cloud"}
    messages = [m.model_dump() for m in req.messages]

    async def run() -> None:
        stats: Dict = {}
        try:
            await ollama_chat(messages, num_predict=1, temperature=0.1, stats=stats, model=req.model)
            print(f"[VOIC] Sesi dipanaskan: {stats.get('prompt_tokens', 0)} token dibaca dalam {stats.get('prompt_ms', 0) / 1000:.1f} dtk.")
        except Exception as exc:
            print(f"[VOIC] Pemanasan sesi dilewati: {describe_error(exc)}")

    task = asyncio.create_task(run())
    _prime_tasks.add(task)
    task.add_done_callback(_prime_tasks.discard)
    return {"ok": True}


class CoachSummaryRequest(BaseModel):
    mode: str
    role_type: str
    language: str = "id"
    transcript: List[ChatTurn]
    model: Optional[str] = None


def _bullet_lines(text: str, header: str) -> List[str]:
    """Ambil baris berawalan '-' di bawah judul tertentu (format yang mudah ditulis model kecil)."""
    block = re.search(rf"{header}\s*:?\s*\n(.*?)(?:\n\s*\n|\n[A-Z][A-Z ]+:|$)", text, flags=re.S | re.I)
    if not block:
        return []
    items = [re.sub(r"^[\s\-\*\u2022\d\.\)]+", "", line).strip().strip('"') for line in block.group(1).splitlines()]
    return [item for item in items if 6 <= len(item) <= 220][:3]


@router.post("/coach-summary")
async def coach_summary(req: CoachSummaryRequest):
    """Ringkas sesi coaching: apa yang sudah bagus, apa yang perlu dilatih, kalimat yang bisa dipakai."""
    is_en = req.language == "en"
    convo = "\n".join(f"{'PESERTA' if t.role == 'user' else 'COACH'}: {t.content}" for t in req.transcript[-30:])
    instruction = (
        "Summarise this coaching chat for the participant. Use exactly this format, max 3 lines each, short and specific to what they said:\n"
        "BAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (a better sentence they can reuse)\nWrite in English."
        if is_en else
        "Ringkas sesi coaching ini untuk peserta. Pakai format persis ini, maksimal 3 baris per bagian, singkat dan spesifik pada yang dia katakan:\n"
        "BAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (contoh kalimat lebih baik yang bisa dia pakai)\nTulis dalam Bahasa Indonesia, sapa peserta dengan 'kamu'."
    )
    try:
        raw = await ollama_chat(
            [{"role": "system", "content": "Kamu pelatih komunikasi yang ringkas dan jujur."}, {"role": "user", "content": convo + "\n\n" + instruction}],
            num_predict=260,
            temperature=0.4,
            model=req.model,
        )
    except Exception as exc:
        return {"strengths": [], "practice": [], "phrases": [], "error": describe_error(exc)}

    return {
        "strengths": _bullet_lines(raw, "BAGUS"),
        "practice": _bullet_lines(raw, "LATIH"),
        "phrases": _bullet_lines(raw, "KALIMAT"),
        "model": await resolve_model(),
    }


IMPROVE_GUIDE = {
    "hrd": {
        "id": "Gunakan pola STAR (situasi, tugas, aksi, hasil terukur) dan nada percaya diri tapi rendah hati.",
        "en": "Use the STAR pattern (situation, task, action, measurable result) with a confident but humble tone.",
    },
    "dosen_penguji": {
        "id": "Gunakan pola klaim, alasan, bukti/data, lalu batasan. Bahasa ilmiah yang lugas.",
        "en": "Use claim, reason, evidence/data, then limitation. Clear academic language.",
    },
    "investor": {
        "id": "Buka dengan angka atau insight paling kuat, lalu keunggulan dan langkah berikutnya. Padat dan meyakinkan.",
        "en": "Lead with the strongest number or insight, then the edge and next step. Tight and convincing.",
    },
}


@router.post("/improve")
async def improve(req: ImproveRequest):
    """Tulis ulang jawaban lemah menjadi versi lebih kuat, tanpa mengarang fakta baru."""
    is_en = req.language == "en"
    guide = IMPROVE_GUIDE.get(req.role_type, IMPROVE_GUIDE["hrd"])["en" if is_en else "id"]
    system = (
        "You are a speaking coach. Rewrite the candidate's spoken answer into a stronger spoken answer of 3-4 sentences. "
        "Keep every fact the candidate gave, do not invent numbers or names; where a detail is missing, use a short placeholder in square brackets. "
        f"{guide} Reply with the rewritten answer only, in English."
        if is_en
        else
        "Kamu adalah pelatih public speaking. Tulis ulang jawaban lisan peserta menjadi jawaban lisan yang lebih kuat dalam 3-4 kalimat. "
        "Pertahankan semua fakta dari peserta, jangan mengarang angka atau nama; jika ada detail yang kurang, pakai placeholder singkat dalam kurung siku. "
        f"{guide} Balas hanya dengan jawaban hasil tulis ulang, dalam Bahasa Indonesia."
    )

    results = []
    for item in req.items[:3]:
        if len(item.answer.split()) < 3:
            continue
        try:
            raw = await ollama_chat(
                [
                    {"role": "system", "content": system},
                    {"role": "user", "content": f"Pertanyaan: {item.question}\nJawaban asli: {item.answer}" if not is_en else f"Question: {item.question}\nOriginal answer: {item.answer}"},
                ],
                num_predict=200,
                temperature=0.5,
                model=req.model,
            )
        except Exception as exc:
            print(f"Improve error: {exc}")
            break

        # Placeholder [..] boleh di sini, jadi jangan pakai clean_reply yang menghapus kurung siku.
        text = re.sub(r"[\*\#\_\`\~]+", "", raw)
        text = re.sub(r"\s+", " ", text).strip().strip('"')
        if 12 <= len(text.split()) <= 120:
            results.append({"key": item.key, "improved": text})

    return {"items": results, "model": req.model or EXAM_MODEL}


# ---------------------------------------------------------------------------
# Streaming: kalimat pertama sudah bisa diucapkan selagi model masih menulis
# ---------------------------------------------------------------------------

class RespondStreamRequest(RespondRequest):
    generate_voice: bool = True
    # Kirim audio di dalam stream (base64) agar browser tidak perlu request tambahan.
    # Penting di Windows: `php artisan serve` hanya punya satu worker.
    inline_audio: bool = False


def audio_payload(filename: Optional[str], inline: bool) -> Dict:
    if not filename:
        return {"audio_file": None}
    if not inline:
        return {"audio_file": filename}
    import base64
    import voice_engine

    path = os.path.join(voice_engine.AUDIO_OUTPUT_DIR, os.path.basename(filename))
    try:
        with open(path, "rb") as handle:
            encoded = base64.b64encode(handle.read()).decode("ascii")
        os.unlink(path)
    except OSError:
        return {"audio_file": filename}
    mime = "audio/wav" if filename.endswith(".wav") else "audio/mpeg"
    return {"audio_file": None, "audio_data": f"data:{mime};base64,{encoded}"}


_ABBREVIATIONS = re.compile(r"\b(prof|dr|ir|pak|bu|bpk|dll|dsb|dst|no|vs|mr|mrs|ms|st|jl|tbk|pt)\.$", re.IGNORECASE)


def split_sentences(buffer: str, final: bool = False):
    """Ambil kalimat utuh dari buffer streaming. Sisanya dikembalikan untuk ditunggu."""
    sentences = []
    position = 0
    for match in re.finditer(r"[.!?…]+[\"')\]]?(?=\s)", buffer):
        candidate = buffer[position:match.end()].strip()
        # Jangan memotong singkatan ("Prof.") atau kalimat yang terlalu pendek ("Oke.").
        if len(candidate) < 14 or _ABBREVIATIONS.search(candidate):
            continue
        sentences.append(candidate)
        position = match.end()
    rest = buffer[position:].lstrip()
    if final and rest.strip():
        sentences.append(rest.strip())
        rest = ""
    return sentences, rest


async def ollama_stream(messages: List[Dict[str, str]], num_predict: int, temperature: float, model: Optional[str] = None, used: Optional[Dict] = None):
    """Token-by-token dari Ollama (NDJSON). Jika model yang diminta gagal sebelum mengirim teks apa pun,
    model lokal dipakai. used["model"] = model yang benar-benar menjawab."""
    models = await candidate_models(model)
    for index, name in enumerate(models):
        sent = False
        requested_at = time.time()
        try:
            async with httpx.AsyncClient(timeout=model_timeout(name)) as client:
                async with client.stream("POST", f"{OLLAMA_BASE_URL}/api/chat", json=chat_payload(name, messages, True, num_predict, temperature)) as response:
                    response.raise_for_status()
                    if used is not None:
                        used["model"] = name
                    # Penalaran di field "thinking" diabaikan; <think> di dalam content disaring.
                    thinking = ThinkFilter()
                    async for line in response.aiter_lines():
                        if not line.strip():
                            continue
                        data = json.loads(line)
                        if data.get("error"):
                            raise RuntimeError(str(data["error"]))
                        chunk = thinking.feed(data.get("message", {}).get("content", ""))
                        if chunk:
                            if not sent and used is not None:
                                # Waktu sampai token pertama = memuat model + membaca prompt (bagian yang tidak ada di cache).
                                used["first_token_ms"] = int((time.time() - requested_at) * 1000)
                            sent = True
                            yield chunk
                        if data.get("done"):
                            if used is not None:
                                used["stats"] = ollama_stats(data)
                            break
                    rest = thinking.flush()
                    if rest:
                        yield rest
            return
        except Exception as exc:
            # Setelah ada kalimat yang diucapkan, jangan mengulang dengan model lain.
            if sent or index == len(models) - 1:
                raise
            print(f"[VOIC] Model {name} gagal ({describe_error(exc)}), memakai model lokal {models[index + 1]}.")


def _line(payload: Dict) -> str:
    return json.dumps(payload, ensure_ascii=False) + "\n"


@router.post("/respond/stream")
async def respond_stream(req: RespondStreamRequest):
    """
    Seperti /respond, tetapi mengirim NDJSON per kalimat:
      {"type": "sentence", "text": "...", "audio_file": "tts_x.wav"}
      ...
      {"type": "done", "reply": "...", "issues": [...], "first_sentence_ms": 640}
    Untuk directive yang bertanya, generasi berhenti di pertanyaan pertama.
    """
    # Import di sini agar exam_engine tetap bisa dites tanpa dependensi suara.
    import voice_engine

    messages = [m.model_dump() for m in req.messages]
    asks = req.directive in ("follow_up", "next", "clarify")
    max_sentences = {"next": 3, "follow_up": 2, "clarify": 2, "coach": 4, "coach_close": 3}.get(req.directive, 1)
    num_predict = {"next": 120, "follow_up": 90, "clarify": 100, "coach": 170, "coach_close": 110}.get(req.directive, 55)
    temperature = 0.8 if req.role_type in ("hrd", "investor") else 0.7

    async def generate():
        started = time.time()
        first_ms = None
        spoken: List[str] = []
        used: Dict = {}
        buffer = ""
        finished = False

        async def emit(sentence: str):
            nonlocal first_ms
            # A chunk can hold a short fragment glued to the next sentence ("Oke. Lalu ...?"); keep all of it.
            # Satu potongan bisa memuat dua pertanyaan ("Kenapa? Lalu apa?"); simpan sampai pertanyaan pertama saja.
            text = trim_questions(clean_reply(sentence, max_sentences=3), req.directive)
            # Kalimat yang membacakan tag instruksi ("FOKUS: ...") tidak pernah diucapkan.
            if not text or leaks_tag(text) or (req.directive in ("react", "coach_close") and is_question(text)):
                return None
            spoken.append(text)
            tts_started = time.time()
            audio = await voice_engine.synthesize(text, req.role_type, req.language) if req.generate_voice else None
            if first_ms is None:
                first_ms = int((time.time() - started) * 1000)
                used["first_tts_ms"] = int((time.time() - tts_started) * 1000)
            return _line({"type": "sentence", "text": text, **audio_payload(audio, req.inline_audio)})

        try:
            async for chunk in ollama_stream(messages, num_predict, temperature, model=req.model, used=used):
                buffer += chunk
                sentences, buffer = split_sentences(buffer)
                for sentence in sentences:
                    line = await emit(sentence)
                    if line:
                        yield line
                    if (asks and spoken and is_question(spoken[-1])) or len(spoken) >= max_sentences:
                        finished = True
                        break
                if finished:
                    break
            if not finished:
                for sentence in split_sentences(buffer, final=True)[0]:
                    if len(spoken) >= max_sentences:
                        break
                    line = await emit(sentence)
                    if line:
                        yield line
        except Exception as exc:  # Ollama mati / putus di tengah / terlalu lambat
            yield _line({"type": "error", "message": describe_error(exc)})

        reply = " ".join(spoken)
        issues = quality_issues(reply, req.answer, req.directive, req.language, earlier_examiner_lines(messages))
        if reply and not issues:
            harvest({
                "ts": int(time.time()), "session_id": req.session_id, "mode": req.mode, "role_type": req.role_type,
                "language": req.language, "directive": req.directive, "part": req.part, "messages": messages,
                "reply": reply, "model": used.get("model", EXAM_MODEL), "reviewed": False,
            })
        yield _line({
            "type": "done",
            "reply": reply,
            "issues": issues,
            "model": used.get("model", EXAM_MODEL),
            "first_sentence_ms": first_ms,
            "latency_ms": int((time.time() - started) * 1000),
            # Rincian untuk `php artisan voic:latency`: baca prompt, lalu suara kalimat pertama.
            "first_token_ms": used.get("first_token_ms"),
            "first_tts_ms": used.get("first_tts_ms"),
            "stats": used.get("stats"),
        })

    return StreamingResponse(generate(), media_type="application/x-ndjson")
