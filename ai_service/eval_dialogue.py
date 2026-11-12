"""
Ukur seberapa "nyambung" model penguji, dan bandingkan beberapa model sekaligus.

    python eval_dialogue.py --models voic-coach qwen3:4b qwen2.5:3b
    python eval_dialogue.py --models voic-coach --show 5

Setiap contoh di set evaluasi (hasil `php artisan voic:export-training-data`) dikirim ke
Ollama dengan prompt yang sama persis seperti saat ujian. Balasan dinilai oleh quality
gate yang sama dengan produksi (exam_engine.quality_issues):
  - grounded   : menyinggung isi jawaban peserta (active listening)
  - directive  : 'react' tanpa pertanyaan, 'follow_up' tepat satu pertanyaan
  - panjang    : 3-55 kata, tanpa template lama / bahasa yang salah
  - nyambung   : tidak mengulang pertanyaan yang sudah diajukan, tidak membuka dua giliran
                 dengan kata yang sama, dan tidak membacakan tag instruksi (FOKUS/KAITKAN)
Skor akhir = persentase balasan tanpa masalah. Jalankan sebelum & sesudah fine-tune.
"""

from __future__ import annotations

import argparse
import asyncio
import json
import os
import sys
import time
from collections import Counter
from typing import Dict, List

import httpx

sys.path.insert(0, os.path.dirname(__file__))
from exam_engine import OLLAMA_BASE_URL, clean_reply, earlier_examiner_lines, quality_issues, trim_questions  # noqa: E402


def load(path: str) -> List[Dict]:
    with open(path, encoding="utf-8") as handle:
        return [json.loads(line) for line in handle if line.strip()]


async def generate(client: httpx.AsyncClient, model: str, messages: List[Dict]) -> str:
    response = await client.post(
        f"{OLLAMA_BASE_URL}/api/chat",
        json={
            "model": model,
            "messages": messages,
            "stream": False,
            "think": False,
            "options": {"temperature": 0.7, "top_p": 0.9, "repeat_penalty": 1.12, "num_predict": 120, "num_ctx": 4096},
        },
        # CPU-only laptops need minutes for the first call (model load + ~1.3k prompt tokens).
        timeout=httpx.Timeout(600.0, connect=10.0),
    )
    response.raise_for_status()
    return response.json().get("message", {}).get("content", "")


async def evaluate(model: str, rows: List[Dict], show: int) -> Dict:
    issues_counter: Counter = Counter()
    passed = 0
    latencies: List[float] = []
    samples = []

    async with httpx.AsyncClient() as client:
        for row in rows:
            messages = row["messages"][:-1]
            reference = row["messages"][-1]["content"]
            meta = row.get("meta", {})
            directive = meta.get("directive", "react")

            started = time.time()
            try:
                raw = await generate(client, model, messages)
            except Exception as exc:  # model belum ada / Ollama mati
                return {"model": model, "error": f"{type(exc).__name__}: {exc}"}
            latencies.append(time.time() - started)

            # Sama dengan /exam/respond: batas kalimat per directive (+1 untuk kalimat tanggapan).
            limit = {"next": 3, "follow_up": 2, "clarify": 2}.get(directive, 1)
            reply = trim_questions(clean_reply(raw, max_sentences=limit + (1 if directive != "react" else 0)), directive)
            issues = quality_issues(reply, meta.get("answer", ""), directive, meta.get("language", "id"), earlier_examiner_lines(messages))
            issues_counter.update(issues)
            if not issues:
                passed += 1
            if len(samples) < show:
                samples.append({"answer": meta.get("answer", "")[:140], "reference": reference, "model": reply, "issues": issues})

    total = max(1, len(rows))
    return {
        "model": model,
        "pass_rate": round(passed / total * 100, 1),
        "avg_latency_s": round(sum(latencies) / max(1, len(latencies)), 2),
        "issues": dict(issues_counter.most_common()),
        "samples": samples,
    }


async def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--eval", default=os.path.join(os.path.dirname(__file__), "datasets", "sft", "voic_exam_eval.jsonl"))
    parser.add_argument("--models", nargs="+", default=["voic-coach"])
    parser.add_argument("--show", type=int, default=3, help="Jumlah contoh balasan yang ditampilkan per model")
    args = parser.parse_args()

    rows = load(args.eval)
    print(f"Evaluating {len(rows)} examples from {args.eval}\n")

    for model in args.models:
        result = await evaluate(model, rows, args.show)
        if "error" in result:
            print(f"[{model}] ERROR: {result['error']}\n")
            continue
        print(f"[{model}] pass rate {result['pass_rate']}%  ·  avg latency {result['avg_latency_s']}s")
        if result["issues"]:
            print("  issues: " + ", ".join(f"{k}×{v}" for k, v in result["issues"].items()))
        for sample in result["samples"]:
            print(f"  - jawaban : {sample['answer']}")
            print(f"    model   : {sample['model']}")
            print(f"    referensi: {sample['reference']}")
            if sample["issues"]:
                print(f"    masalah : {', '.join(sample['issues'])}")
        print()


if __name__ == "__main__":
    asyncio.run(main())
