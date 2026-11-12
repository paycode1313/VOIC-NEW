"""Buat notebook Colab "tinggal Run all": data latih + skrip training sudah tertanam di dalamnya.

Tidak perlu upload file apa pun ke Colab. Jalankan dari folder proyek VOIC:

    php artisan voic:export-training-data
    python ai_service/colab/buat_notebook_otomatis.py

Hasilnya ai_service/datasets/sft/VOIC_latih_otomatis.ipynb (folder ini di-gitignore karena berisi data sesi).
Di Colab: File -> Upload notebook -> pilih file itu -> Runtime -> Run all.
"""
import base64
import io
import json
import os
import sys
import zipfile

HERE = os.path.dirname(os.path.abspath(__file__))
SERVICE = os.path.dirname(HERE)
SFT = os.path.join(SERVICE, "datasets", "sft")
FILES = {
    "datasets/sft/voic_exam_sft.jsonl": os.path.join(SFT, "voic_exam_sft.jsonl"),
    "datasets/sft/voic_exam_eval.jsonl": os.path.join(SFT, "voic_exam_eval.jsonl"),
    "finetune_lora.py": os.path.join(SERVICE, "finetune_lora.py"),
}
OUTPUT = os.path.join(SFT, "VOIC_latih_otomatis.ipynb")
# Qwen2.5-3B: on the target laptop (CPU only, ~1.3 GB free RAM) a Qwen3-4B fine-tune averaged 177 s per
# reply against 11.6 s for qwen2.5:3b at the same pass rate (eval 2026-11-12). Override with --base.
BASE_MODEL = "unsloth/Qwen2.5-3B-Instruct"


TRAIN = "datasets/sft/voic_exam_sft.jsonl"


def passing_train_rows() -> tuple:
    """Training rows whose examiner reply passes the app's quality gate, plus the rejected reasons.

    A bad written reply (two questions, a reaction that asks, a reply that ignores the answer) would be
    learned as readily as a good one, so it never reaches the training run.
    """
    from collections import Counter

    sys.path.insert(0, SERVICE)
    from exam_engine import quality_issues

    kept, rejected = [], Counter()
    with open(FILES[TRAIN], encoding="utf-8") as handle:
        for line in handle:
            if not line.strip():
                continue
            row = json.loads(line)
            meta = row.get("meta", {})
            earlier = [m["content"] for m in row["messages"][:-1] if m["role"] == "assistant"]
            issues = quality_issues(row["messages"][-1]["content"], meta.get("answer", ""), meta.get("directive", "react"),
                                    meta.get("language", "id"), earlier or None)
            if issues:
                rejected.update(issues)
            else:
                kept.append(line if line.endswith("\n") else line + "\n")
    return kept, rejected


def bundle(train_rows: list) -> str:
    buffer = io.BytesIO()
    with zipfile.ZipFile(buffer, "w", zipfile.ZIP_DEFLATED) as archive:
        for name, path in FILES.items():
            if name == TRAIN:
                archive.writestr(name, "".join(train_rows))
            else:
                archive.write(path, name)
    return base64.b64encode(buffer.getvalue()).decode("ascii")


def count_lines(path: str) -> int:
    with open(path, encoding="utf-8") as handle:
        return sum(1 for line in handle if line.strip())


def source(text: str) -> list:
    lines = text.strip("\n").splitlines(keepends=True)
    return lines


def markdown(text: str) -> dict:
    return {"cell_type": "markdown", "metadata": {}, "source": source(text)}


def code(text: str) -> dict:
    return {"cell_type": "code", "metadata": {}, "execution_count": None, "outputs": [], "source": source(text)}


def build(payload: str, train: int, evaluation: int) -> dict:
    # The payload is one long line; keeping it as a single source string avoids a giant cell diff per line.
    data_cell = code(
        "# Data latih + skrip training (tertanam, tidak perlu upload apa pun).\n"
        "import base64, io, os, zipfile\n"
        f'DATA = "{payload}"\n'
        "with zipfile.ZipFile(io.BytesIO(base64.b64decode(DATA))) as archive:\n"
        "    archive.extractall('.')\n"
        "for path in ['datasets/sft/voic_exam_sft.jsonl', 'datasets/sft/voic_exam_eval.jsonl']:\n"
        "    print('✓', path, sum(1 for line in open(path, encoding='utf-8') if line.strip()), 'contoh')\n"
        "print('✓ finetune_lora.py')\n"
    )
    return {
        "cells": [
            markdown(f"""
# 🎓 Latih AI penguji VOIC — tinggal **Run all**

Data latih ({train} contoh latih, {evaluation} contoh uji) **sudah ada di dalam notebook ini**. Tidak perlu upload file apa pun.

**Cara pakai (3 klik):**
1. Menu **Runtime → Run all** (atau tekan `Ctrl+F9`).
2. Kalau muncul jendela **Google Drive**, klik **Connect to Google Drive** lalu **Allow**. Ini untuk menyimpan hasil; hanya diminta sekali di awal.
3. Tunggu ± 30–60 menit sampai sel terakhir menulis **SELESAI**. Biarkan tab ini terbuka.

GPU yang dipakai adalah GPU gratis milik Google, **bukan** GPU laptopmu.
Kalau sel pertama menulis *GPU belum aktif*: menu **Runtime → Change runtime type → T4 GPU → Save**, lalu **Runtime → Run all** lagi.
"""),
            code("""
# 1. Cek GPU gratis dari Google + izin Google Drive (diminta di awal agar tidak perlu ditunggui).
import subprocess
gpu = subprocess.run(["nvidia-smi", "--query-gpu=name,memory.total", "--format=csv,noheader"], capture_output=True, text=True)
if gpu.returncode != 0 or not gpu.stdout.strip():
    raise RuntimeError(
        "GPU belum aktif. Menu Runtime -> Change runtime type -> pilih T4 GPU -> Save, lalu Runtime -> Run all lagi. "
        "Kalau T4 tidak bisa dipilih / 'usage limit', kuota GPU gratis sedang habis: coba lagi beberapa jam lagi."
    )
print("✓ GPU:", gpu.stdout.strip())

from google.colab import drive
drive.mount("/content/drive")
print("✓ Google Drive tersambung")
"""),
            code("""
# 2. Pasang alat training (± 3 menit).
%pip install -q unsloth
import unsloth  # gagal di sini = instalasi bermasalah; jalankan Runtime -> Run all sekali lagi
print("✓ alat training terpasang")
"""),
            data_cell,
            code(f"""
# 4. Latih model (± 20–40 menit). Di akhir otomatis dibuat file GGUF + Modelfile untuk Ollama.
import subprocess, sys

def train(batch):
    command = [sys.executable, "finetune_lora.py",
               "--train", "datasets/sft/voic_exam_sft.jsonl", "--eval", "datasets/sft/voic_exam_eval.jsonl",
               "--base", "{BASE_MODEL}", "--epochs", "2", "--batch", str(batch), "--output", "outputs/voic-coach"]
    lines = []
    with open("training_log.txt", "a", encoding="utf-8") as log:
        process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, bufsize=1)
        for line in process.stdout:
            print(line, end="")
            log.write(line)
            lines.append(line)
    return process.wait(), lines

code, lines = train(2)
if code != 0 and any("out of memory" in line.lower() for line in lines):
    print("\\n>>> Memori GPU tidak cukup, mengulang dengan batch lebih kecil...\\n")
    code, lines = train(1)
if code != 0:
    print("\\n" + "=" * 70 + "\\nRINGKASAN ERROR (kirim bagian ini ke Claude):\\n" + "=" * 70)
    print("".join(lines[-25:]))
    raise RuntimeError("Training gagal. Salin RINGKASAN ERROR di atas (atau file training_log.txt di panel Files).")
print("\\n✓ Training selesai")
"""),
            code("""
# 5. Simpan hasil ke Google Drive: folder VOIC-model.
import os, shutil
source_dir = "outputs/voic-coach"
saved = [name for name in os.listdir(source_dir) if name.endswith(".gguf") or name == "Modelfile"] if os.path.isdir(source_dir) else []
if not any(name.endswith(".gguf") for name in saved):
    raise RuntimeError("File GGUF tidak ditemukan: lihat pesan error di sel training (nomor 4) di atas.")
target = "/content/drive/MyDrive/VOIC-model"
os.makedirs(target, exist_ok=True)
for name in saved:
    shutil.copy(os.path.join(source_dir, name), target)
    print(f"✓ disimpan: MyDrive/VOIC-model/{name} ({os.path.getsize(os.path.join(target, name)) / 1e9:.2f} GB)")
print("\\nSELESAI. Lanjutkan langkah di laptop (di bawah).")
"""),
            markdown("""
## Setelah SELESAI — di laptop
1. Buka Google Drive → folder **VOIC-model** → unduh **file .gguf** dan **Modelfile** ke satu folder yang sama, misalnya `C:\\laragon\\www\\VOIC-NEW\\ai_service\\outputs\\voic-coach`.
2. Buka PowerShell di folder itu, lalu jalankan:

```bash
ollama create voic-coach -f Modelfile
```

3. Uji hasilnya dari folder `C:\\laragon\\www\\VOIC-NEW`:

```bash
python ai_service/eval_dialogue.py --models qwen2.5:3b voic-coach --show 5
```
"""),
        ],
        "metadata": {
            "accelerator": "GPU",
            "colab": {"provenance": [], "gpuType": "T4"},
            "kernelspec": {"display_name": "Python 3", "name": "python3"},
            "language_info": {"name": "python"},
        },
        "nbformat": 4,
        "nbformat_minor": 0,
    }


def main() -> int:
    global BASE_MODEL
    if "--base" in sys.argv:
        BASE_MODEL = sys.argv[sys.argv.index("--base") + 1]
    missing = [path for path in FILES.values() if not os.path.exists(path)]
    if missing:
        print("File belum ada:", *missing, sep="\n  ")
        print("Jalankan dulu: php artisan voic:export-training-data")
        return 1
    train_rows, rejected = passing_train_rows()
    train = len(train_rows)
    evaluation = count_lines(FILES["datasets/sft/voic_exam_eval.jsonl"])
    notebook = build(bundle(train_rows), train, evaluation)
    with open(OUTPUT, "w", encoding="utf-8", newline="\n") as handle:
        json.dump(notebook, handle, indent=1, ensure_ascii=False)
        handle.write("\n")
    print(f"✓ {OUTPUT}")
    print(f"  {train} contoh latih, {evaluation} contoh uji, {os.path.getsize(OUTPUT) / 1024:.0f} KB")
    if rejected:
        reasons = ", ".join(f"{issue} {count}" for issue, count in rejected.most_common())
        print(f"  Dibuang karena gagal mutu: {sum(rejected.values())} masalah ({reasons})")
    print(f"  Model dasar: {BASE_MODEL}")
    print("  Di Colab: File -> Upload notebook -> pilih file ini -> Runtime -> Run all")
    return 0


if __name__ == "__main__":
    sys.exit(main())
