"""Cek file dialog dari Kimi sebelum dipakai melatih.

    python ai_service/datasets/kimi/cek_kimi.py            # semua *.json di folder ini
    python ai_service/datasets/kimi/cek_kimi.py sidang_05.json

Untuk setiap file dilaporkan:
- RUSAK  : JSON/struktur tidak bisa dibaca; dialog atau sisa giliran akan DIBUANG saat export. Minta Kimi membuat ulang.
- SAMBUNG: follow_up/clarify yang pertanyaan lanjutannya tidak dipakai di giliran berikutnya (riwayat jadi tidak nyambung).
- KARANG : penguji merujuk ucapan peserta yang tidak pernah ada ("Tadi Anda menyebut ..."); perbaiki reply itu.
- MUTU   : balasan penguji yang gagal pemeriksa kualitas aplikasi (dua pertanyaan, react bertanya, tidak menyentuh
           jawaban, dst.). Contoh seperti ini otomatis dibuang saat membuat notebook latih, tapi kalau banyak, lebih baik
           minta Kimi memperbaiki dialog itu.
"""
import glob
import json
import os
import sys
from collections import Counter
from typing import Dict, List, Optional, Tuple

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(os.path.dirname(HERE)))  # ai_service/
from exam_engine import content_words, quality_issues  # noqa: E402

# "Tadi Anda menyebut ...", "you mentioned ...": the examiner quotes the candidate. The quoted part must
# come from what the candidate actually said, otherwise the model learns to invent references.
import re  # noqa: E402

REFERENCE = re.compile(
    r"(?:tadi|sempat|sebelumnya|juga)\s+(?:\w+\s+){0,2}?(?:menyebut|bilang|mengatakan|cerita|menceritakan|menyinggung)\s+([^.,;?!]{3,90})"
    r"|\byou\s+(?:also\s+)?(?:mentioned|said|told me|talked about)\s+([^.,;?!]{3,90})",
    re.IGNORECASE,
)

MODES = {"wawancara", "sidang", "pitching"}
DIRECTIVES = {"react", "follow_up", "clarify", "next"}
ISSUE_TEXT = {
    "multiple_questions": "lebih dari satu pertanyaan",
    "follow_up_without_question": "follow_up/clarify tanpa pertanyaan",
    "react_with_question": "react tapi bertanya",
    "not_grounded_in_answer": "tidak menyebut isi jawaban peserta",
    "repeated_question": "mengulang pertanyaan sebelumnya",
    "repeated_opener": "kata pembuka sama dengan balasan sebelumnya",
    "too_long": "terlalu panjang",
    "too_short": "terlalu pendek",
    "banned_template": "kalimat template/pujian umum",
    "wrong_language": "bahasa salah",
    "leaked_tag": "menyebut tag instruksi",
}


class DuplicateKey(ValueError):
    pass


def no_duplicate_keys(pairs):
    """JSON quietly keeps the LAST of two equal keys (Python and PHP alike). Kimi sometimes writes a second
    "question" after "reply" (the next question), which would silently overwrite the turn's real question."""
    seen = {}
    for key, value in pairs:
        if key in seen:
            raise DuplicateKey(f"field \"{key}\" muncul dua kali dalam satu giliran (mis. \"question\" kedua setelah \"reply\"); "
                               f"hapus yang kedua, karena itu menimpa pertanyaan aslinya")
        seen[key] = value
    return seen


def load(path: str) -> Tuple[Optional[list], Optional[str]]:
    raw = open(path, encoding="utf-8-sig").read().strip()
    try:
        data = json.loads(raw, object_pairs_hook=no_duplicate_keys)
    except DuplicateKey as exc:
        return None, str(exc)
    except json.JSONDecodeError:
        # Sama seperti export Laravel: ambil blok JSON terluar, buang ```json dan teks tambahan.
        start = min([i for i in (raw.find("["), raw.find("{")) if i >= 0], default=-1)
        end = max(raw.rfind("]"), raw.rfind("}"))
        try:
            data = json.loads(raw[start:end + 1], object_pairs_hook=no_duplicate_keys) if start >= 0 and end > start else None
        except DuplicateKey as exc:
            return None, str(exc)
        except json.JSONDecodeError as exc:
            return None, f"JSON tidak valid ({exc.msg} di baris {exc.lineno}); kemungkinan jawaban Kimi terpotong"
    if isinstance(data, dict) and "turns" in data:
        data = [data]
    if not isinstance(data, list):
        return None, "isi file bukan daftar dialog"
    return data, None


def check_dialog(dialog: Dict, label: str) -> Tuple[int, List[str]]:
    """Kembalikan (jumlah giliran yang bisa dipakai, daftar temuan)."""
    findings: List[str] = []
    if not isinstance(dialog, dict) or dialog.get("mode") not in MODES or not isinstance(dialog.get("turns"), list):
        return 0, [f"RUSAK  {label}: mode harus wawancara/sidang/pitching dan harus ada \"turns\""]
    language = "en" if dialog.get("language") == "en" else "id"

    turns = []
    for number, turn in enumerate(dialog["turns"], start=1):
        ok = isinstance(turn, dict) and turn.get("part") in (1, 2, 3, "1", "2", "3") and turn.get("directive") in DIRECTIVES
        ok = ok and all(isinstance(turn.get(f), str) and turn[f].strip() for f in ("question", "answer", "reply"))
        if not ok:
            findings.append(f"RUSAK  {label} giliran {number}: field tidak lengkap/salah; giliran ini dan sesudahnya dibuang")
            break
        turns.append({**turn, "part": int(turn["part"])})

    parts = [t["part"] for t in turns]
    if parts != sorted(parts):
        findings.append(f"SAMBUNG {label}: part harus berurutan 1 lalu 2 lalu 3 (sekarang {parts})")

    earlier: List[str] = []
    for index, turn in enumerate(turns):
        following = turns[index + 1] if index + 1 < len(turns) else None
        directive, target = turn["directive"], turn["reply"]
        # Sama seperti export: react + pertanyaan berikutnya di part yang sama = directive "next".
        if directive == "react" and following and following["part"] == turn["part"] and following["question"] not in target:
            directive, target = "next", f"{target} {following['question']}"
        if turn["directive"] in ("follow_up", "clarify") and following and following["question"] not in turn["reply"]:
            findings.append(f"SAMBUNG {label} giliran {index + 2}: \"question\" harus sama persis dengan pertanyaan di reply giliran {index + 1}")

        said = set().union(*(content_words(t["answer"]) for t in turns[: index + 1]))
        for match in REFERENCE.finditer(turn["reply"]):
            quoted = content_words(match.group(1) or match.group(2) or "")
            # Words may be inflected ("membangun"/"bangun"), so compare 5-letter prefixes.
            found = {w for w in quoted if any(w[:5] == s[:5] for s in said)}
            if len(quoted) >= 2 and len(found) < len(quoted) / 2:
                findings.append(f"KARANG {label} giliran {index + 1}: penguji merujuk hal yang tidak pernah diucapkan peserta: "
                                f"\"{(match.group(1) or match.group(2)).strip()}\"")

        issues = quality_issues(target, turn["answer"], directive, language, earlier or None)
        if issues:
            reasons = ", ".join(ISSUE_TEXT.get(issue, issue) for issue in issues)
            findings.append(f"MUTU   {label} giliran {index + 1} ({turn['directive']}): {reasons}\n         reply: {turn['reply'][:140]}")
        earlier.append(target)
    return len(turns), findings


def main() -> int:
    names = sys.argv[1:] or sorted(os.path.basename(p) for p in glob.glob(os.path.join(HERE, "*.json")))
    if not names:
        print("Belum ada file *.json di", HERE)
        print("Ikuti PROMPT_KIMI.md: simpan jawaban Kimi sebagai file .json di folder ini.")
        return 1

    totals: Counter = Counter()
    directive_mix: Counter = Counter()
    broken_files = []
    for name in names:
        path = name if os.path.isabs(name) else os.path.join(HERE, name)
        data, error = load(path)
        if error:
            print(f"✗ {name}: RUSAK, {error}")
            broken_files.append(name)
            continue
        file_findings: List[str] = []
        usable = 0
        for number, dialog in enumerate(data, start=1):
            count, findings = check_dialog(dialog, f"dialog {number}")
            usable += count
            file_findings += findings
            if isinstance(dialog, dict):
                directive_mix.update(t["directive"] for t in dialog.get("turns", []) if isinstance(t, dict) and t.get("directive") in DIRECTIVES)
        quality = sum(f.startswith(("MUTU", "KARANG")) for f in file_findings)
        broken = sum(f.startswith("RUSAK") for f in file_findings)
        totals.update(dialogs=len(data), turns=usable, quality=quality)
        mark = "✗" if broken else ("!" if file_findings else "✓")
        print(f"{mark} {name}: {len(data)} dialog, {usable} giliran, {quality} balasan gagal mutu")
        for finding in file_findings:
            print("    " + finding)
        if broken:
            broken_files.append(name)

    usable = totals["turns"] - totals["quality"]
    print("\n" + "=" * 60)
    print(f"Total: {totals['dialogs']} dialog, {totals['turns']} giliran, {usable} contoh latih yang lolos mutu "
          f"({(usable / totals['turns'] * 100) if totals['turns'] else 0:.0f}%)")
    if directive_mix:
        mix = ", ".join(f"{k} {v / sum(directive_mix.values()) * 100:.0f}%" for k, v in directive_mix.most_common())
        print(f"Campuran directive: {mix}  (target: follow_up ±45%, react ±40%, clarify ±15%)")
    print(f"Target minggu 1: ±700 contoh. Kurang {max(0, 700 - usable)} lagi." if usable < 700 else "Target minggu 1 tercapai ✓")
    if broken_files:
        print("Buat ulang file ini di Kimi:", ", ".join(broken_files))
    return 1 if broken_files else 0


if __name__ == "__main__":
    sys.exit(main())
