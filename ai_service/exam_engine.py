"""
VOIC Exam Engine: endpoint untuk alur ujian 3 bagian (/exam/respond, /exam/improve).

Prinsip desain:
- Laravel (ExaminerService::buildMessages) adalah SATU-SATUNYA sumber format prompt.
  Service ini hanya menjalankan model, membersihkan output, dan menjaga kualitas.
  Dengan begitu data training (diekspor oleh `php artisan voic:export-training-data`)
  memakai format yang persis sama dengan saat inferensi.
- Struktur ujian (pertanyaan berikutnya, transisi bagian) dibacakan oleh Laravel.
  Model cukup menulis bagian "manusiawi": tanggapan spesifik atau 1 pertanyaan lanjutan.
- Setiap balasan dicek oleh quality gate. Balasan yang lolos dipanen ke
  datasets/harvest/exam_turns.jsonl sebagai kandidat data latih (perlu direview).
"""

from __future__ import annotations

import json
import os
import re
import time
from typing import Dict, List, Optional

import httpx
from fastapi import APIRouter
from pydantic import BaseModel, Field

OLLAMA_BASE_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434")
EXAM_MODEL = os.getenv("VOIC_EXAM_MODEL", os.getenv("OLLAMA_MODEL", "voic-coach"))
HARVEST_FILE = os.path.join(os.path.dirname(__file__), "datasets", "harvest", "exam_turns.jsonl")

router = APIRouter(prefix="/exam", tags=["exam"])

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
    directive: str = "react"  # react | follow_up
    part: int = 1
    question: str = ""
    answer: str = ""
    messages: List[ChatTurn]


class RespondResponse(BaseModel):
    reply: str
    passed_quality: bool
    issues: List[str] = Field(default_factory=list)
    model: str
    latency_ms: int


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


# ---------------------------------------------------------------------------
# Output cleaning & quality gate
# ---------------------------------------------------------------------------

def clean_reply(text: str, max_sentences: int = 3) -> str:
    """Bersihkan markdown, label pembicara, dan potong ke kalimat utuh."""
    if not text:
        return ""
    text = re.sub(r"\[[^\]]*\]", "", text)
    text = re.sub(r"[\*\#\_\`\~]+", "", text)
    text = re.sub(r"^\s*[\-•\d]+[\.\)]\s*", "", text, flags=re.MULTILINE)
    text = re.sub(r"^\s*[A-Za-z\.\s]{2,30}:\s+", "", text.strip())
    text = re.sub(r"\s+", " ", text).strip().strip('"').strip("'")

    if text and not text.endswith((".", "?", "!")):
        cut = max(text.rfind("."), text.rfind("?"), text.rfind("!"))
        if cut > 15:
            text = text[: cut + 1]

    sentences = [s.strip() for s in re.split(r"(?<=[.?!])\s+", text) if s.strip()]
    return " ".join(sentences[:max_sentences]).strip()


def content_words(text: str) -> set:
    words = re.findall(r"[a-zA-ZÀ-ɏ0-9]+", text.lower())
    return {w for w in words if len(w) > 3 and w not in STOPWORDS}


def quality_issues(reply: str, answer: str, directive: str, language: str) -> List[str]:
    """Daftar masalah kualitas. Kosong berarti balasan layak dipakai & dipanen."""
    issues: List[str] = []
    lowered = reply.lower()
    words = reply.split()

    if not reply:
        return ["empty"]
    if len(words) < 3:
        issues.append("too_short")
    if len(words) > 55:
        issues.append("too_long")
    if any(re.search(p, lowered) for p in BANNED_PATTERNS):
        issues.append("banned_template")

    question_count = reply.count("?")
    if directive == "follow_up" and question_count == 0:
        issues.append("follow_up_without_question")
    if directive == "follow_up" and question_count > 1:
        issues.append("multiple_questions")

    # Active listening: tanggapan harus menyentuh isi jawaban, kecuali jawabannya sangat pendek
    # atau peserta bilang tidak tahu (balasan yang tepat justru menenangkan, bukan mengutip).
    answer_words = content_words(answer)
    if len(answer_words) >= 4 and not UNSURE_PATTERN.search(answer):
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


async def ollama_chat(messages: List[Dict[str, str]], num_predict: int, temperature: float) -> str:
    async with httpx.AsyncClient(timeout=25.0) as client:
        response = await client.post(
            f"{OLLAMA_BASE_URL}/api/chat",
            json={
                "model": EXAM_MODEL,
                "messages": messages,
                "stream": False,
                "keep_alive": "60m",
                "options": {
                    "temperature": temperature,
                    "top_p": 0.9,
                    "repeat_penalty": 1.12,
                    "num_predict": num_predict,
                    "num_ctx": 4096,
                },
            },
        )
        response.raise_for_status()
        return response.json().get("message", {}).get("content", "")


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@router.post("/respond", response_model=RespondResponse)
async def respond(req: RespondRequest):
    """Jalankan model untuk satu giliran penguji. Retry sekali jika gagal quality gate."""
    started = time.time()
    messages = [m.model_dump() for m in req.messages]
    is_follow_up = req.directive == "follow_up"
    max_sentences = 2 if is_follow_up else 1

    best_reply, best_issues = "", ["empty"]
    for attempt in range(2):
        try:
            raw = await ollama_chat(
                messages,
                num_predict=90 if is_follow_up else 55,
                temperature=0.7 if attempt == 0 else 0.55,
            )
        except Exception as exc:  # Ollama mati / model belum di-pull
            print(f"Exam model error: {exc}")
            break

        reply = clean_reply(raw, max_sentences=max_sentences + (1 if is_follow_up else 0))
        issues = quality_issues(reply, req.answer, req.directive, req.language)
        if len(issues) < len(best_issues):
            best_reply, best_issues = reply, issues
        if not issues:
            break

    # Balasan kosong = biarkan Laravel memakai composer offline.
    hard_fail = {"empty", "banned_template", "wrong_language"}
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
            "model": EXAM_MODEL,
            "reviewed": False,
        })

    return RespondResponse(
        reply=best_reply,
        passed_quality=bool(best_reply) and not best_issues,
        issues=best_issues,
        model=EXAM_MODEL,
        latency_ms=int((time.time() - started) * 1000),
    )


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
            )
        except Exception as exc:
            print(f"Improve error: {exc}")
            break

        # Placeholder [..] boleh di sini, jadi jangan pakai clean_reply yang menghapus kurung siku.
        text = re.sub(r"[\*\#\_\`\~]+", "", raw)
        text = re.sub(r"\s+", " ", text).strip().strip('"')
        if 12 <= len(text.split()) <= 120:
            results.append({"key": item.key, "improved": text})

    return {"items": results, "model": EXAM_MODEL}
