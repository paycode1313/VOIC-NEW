"""
Pelafalan suara penguji: ubah teks menjadi ejaan yang dibaca benar oleh mesin suara.

Contoh: Piper Indonesia membaca "coding" sebagai "co-ding" (c Indonesia) dan "online" sebagai
"on-li-ne", jadi teks yang diucapkan diubah ke "koding" dan "onlain". Hanya teks untuk suara
yang diubah; teks di layar tetap asli. Kamusnya di pronunciation.json (juga dipakai suara
browser lewat frontend/src/js/pronunciation.js, jadi aturan di sini dan di sana harus sama).
"""

from __future__ import annotations

import json
import os
import re
from typing import Callable, Dict

with open(os.path.join(os.path.dirname(__file__), "pronunciation.json"), encoding="utf-8") as _f:
    LEXICON: Dict[str, Dict[str, str]] = {lang: words for lang, words in json.load(_f).items() if not lang.startswith("_")}

# "Rp 5.000.000" / "Rp5,000,000" -> "5000000 rupiah" (kedua mesin suara membaca angka utuh dengan benar,
# sedangkan "Rp" dibaca "er-pe" dan titik ribuan dibaca "point" oleh suara Inggris).
_RUPIAH = re.compile(r"\bRp\.?\s*(\d{1,3}(?:[.,]\d{3})+|\d+)(?![.,]?\d)")
# "Rp 2,5 juta" -> "2,5 juta rupiah".
_RUPIAH_UNIT = re.compile(r"\bRp\.?\s*(\d+(?:[.,]\d+)?\s*(?:ribu|juta|miliar|triliun|thousand|million|billion|trillion))\b", re.IGNORECASE)


def _word_pattern(words: Dict[str, str]) -> re.Pattern:
    # Frasa terpanjang dicoba lebih dulu ("pitch deck" sebelum "pitch"); hanya kata utuh,
    # jadi akhiran "-nya" dan awalan "di-" tetap ikut ("coding-nya" -> "koding-nya").
    keys = sorted(words, key=len, reverse=True)
    return re.compile(r"(?<!\w)(" + "|".join(re.escape(k) for k in keys) + r")(?!\w)", re.IGNORECASE)


_PATTERNS = {lang: (_word_pattern(words), {k.lower(): (k, v) for k, v in words.items()}) for lang, words in LEXICON.items()}


def _replacer(table: Dict[str, tuple]) -> Callable[[re.Match], str]:
    def replace(match: re.Match) -> str:
        found = match.group(1)
        key, spoken = table[found.lower()]
        if key.isupper():
            # Singkatan hanya jika ditulis huruf besar: "IT" ya, "it" tidak.
            return spoken if found == key else found
        return spoken[:1].upper() + spoken[1:] if found[:1].isupper() else spoken

    return replace


def speech_text(text: str, language: str = "id") -> str:
    """Teks yang dikirim ke mesin suara untuk bahasa ini ("id" atau "en")."""
    if not text:
        return text
    text = _RUPIAH_UNIT.sub(lambda m: m.group(1) + " rupiah", text)
    text = _RUPIAH.sub(lambda m: re.sub(r"[.,]", "", m.group(1)) + " rupiah", text)
    pattern, table = _PATTERNS["en" if language == "en" else "id"]
    return pattern.sub(_replacer(table), text)
