"""
Fine-tune model penguji VOIC (LoRA) lalu ekspor ke GGUF untuk Ollama.

Butuh GPU NVIDIA (>= 8 GB VRAM). Paling mudah: notebook colab/latih_voic_coach.ipynb di Google Colab (T4 gratis).
Manual:

    pip install -r requirements-train.txt
    python finetune_lora.py \
        --train datasets/sft/voic_exam_sft.jsonl \
        --eval datasets/sft/voic_exam_eval.jsonl \
        --extra datasets/harvest/exam_turns.reviewed.jsonl   # opsional

Hasil: outputs/voic-coach/ berisi file *.gguf dan Modelfile. Salin ke laptop, lalu:

    ollama create voic-coach -f outputs/voic-coach/Modelfile

Data guru-murid: `php artisan voic:teacher-generate` (Kimi K3 sebagai penguji/guru), lalu
`php artisan voic:export-training-data`, sehingga format prompt
(system + riwayat + instruksi giliran) persis sama dengan yang dipakai saat ujian berjalan.
Model hanya dilatih pada token balasan penguji (assistant), bukan pada prompt.
"""

from __future__ import annotations

import argparse
import glob
import json
import os
import random
from typing import Dict, List


def load_examples(path: str) -> List[Dict]:
    """Baca JSONL. Mendukung format export Laravel ({messages}) dan format harvest ({messages, reply})."""
    rows: List[Dict] = []
    if not path or not os.path.exists(path):
        return rows
    with open(path, encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if not line:
                continue
            row = json.loads(line)
            messages = row.get("messages") or []
            if row.get("reply") and (not messages or messages[-1].get("role") != "assistant"):
                # Harvest hanya dipakai jika sudah direview manusia.
                if row.get("reviewed") is not True:
                    continue
                messages = messages + [{"role": "assistant", "content": row["reply"]}]
            if len(messages) >= 3 and messages[-1]["role"] == "assistant":
                rows.append({"messages": messages})
    return rows


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--train", default="datasets/sft/voic_exam_sft.jsonl")
    parser.add_argument("--eval", default="datasets/sft/voic_exam_eval.jsonl")
    parser.add_argument("--extra", action="append", default=[], help="JSONL tambahan (boleh berulang)")
    parser.add_argument("--base", default="unsloth/Qwen3-4B-Instruct-2507",
                        help="Model murid. Qwen3-4B-Instruct-2507 muat di T4 gratis dan tidak punya mode berpikir.")
    parser.add_argument("--chat-template", default="auto",
                        help="auto = pakai template bawaan tokenizer (Qwen3), atau nama template Unsloth mis. qwen-2.5")
    parser.add_argument("--output", default="outputs/voic-coach")
    parser.add_argument("--epochs", type=float, default=3)
    parser.add_argument("--lr", type=float, default=2e-4)
    parser.add_argument("--rank", type=int, default=16)
    parser.add_argument("--max-seq", type=int, default=3072)
    parser.add_argument("--quant", default="q4_k_m", help="Kuantisasi GGUF: q4_k_m (cepat) atau q8_0 (lebih akurat)")
    parser.add_argument("--seed", type=int, default=3407)
    args = parser.parse_args()

    train_rows = load_examples(args.train)
    for extra in args.extra:
        train_rows += load_examples(extra)
    eval_rows = load_examples(args.eval)
    random.Random(args.seed).shuffle(train_rows)

    print(f"Train examples: {len(train_rows)} | Eval examples: {len(eval_rows)}")
    if len(train_rows) < 20:
        raise SystemExit("Data terlalu sedikit. Jalankan `php artisan voic:export-training-data` dan kumpulkan lebih banyak sesi.")
    if len(train_rows) < 200:
        print("Peringatan: < 200 contoh. Model bisa overfit; pertimbangkan --epochs 2 dan tambah data dari sesi yang dinilai 👍.")

    # Import berat ditunda agar `--help` tetap jalan tanpa GPU.
    from datasets import Dataset
    from trl import SFTConfig, SFTTrainer
    from unsloth import FastLanguageModel
    from unsloth.chat_templates import get_chat_template, train_on_responses_only

    model, tokenizer = FastLanguageModel.from_pretrained(
        model_name=args.base,
        max_seq_length=args.max_seq,
        load_in_4bit=True,
    )
    model = FastLanguageModel.get_peft_model(
        model,
        r=args.rank,
        lora_alpha=args.rank,
        lora_dropout=0.0,
        target_modules=["q_proj", "k_proj", "v_proj", "o_proj", "gate_proj", "up_proj", "down_proj"],
        use_gradient_checkpointing="unsloth",
        random_state=args.seed,
    )
    template = args.chat_template
    if template == "auto":
        # Qwen2.5 lama butuh template Unsloth; Qwen3-Instruct sudah membawa template ChatML sendiri.
        template = "qwen-2.5" if "qwen2.5" in args.base.lower() else None
    if template:
        tokenizer = get_chat_template(tokenizer, chat_template=template)

    def render(batch):
        return {"text": [tokenizer.apply_chat_template(m, tokenize=False, add_generation_prompt=False) for m in batch["messages"]]}

    train_ds = Dataset.from_list(train_rows).map(render, batched=True, remove_columns=["messages"])
    eval_ds = Dataset.from_list(eval_rows).map(render, batched=True, remove_columns=["messages"]) if eval_rows else None

    trainer = SFTTrainer(
        model=model,
        tokenizer=tokenizer,
        train_dataset=train_ds,
        eval_dataset=eval_ds,
        args=SFTConfig(
            dataset_text_field="text",
            max_seq_length=args.max_seq,
            per_device_train_batch_size=2,
            gradient_accumulation_steps=4,
            num_train_epochs=args.epochs,
            learning_rate=args.lr,
            lr_scheduler_type="cosine",
            warmup_ratio=0.05,
            weight_decay=0.01,
            logging_steps=5,
            eval_strategy="epoch" if eval_ds is not None else "no",
            save_strategy="no",
            optim="adamw_8bit",
            seed=args.seed,
            output_dir=os.path.join(args.output, "checkpoints"),
            report_to="none",
        ),
    )
    # Hitung loss hanya pada balasan penguji.
    trainer = train_on_responses_only(
        trainer,
        instruction_part="<|im_start|>user\n",
        response_part="<|im_start|>assistant\n",
    )
    trainer.train()

    os.makedirs(args.output, exist_ok=True)
    model.save_pretrained(os.path.join(args.output, "lora"))
    tokenizer.save_pretrained(os.path.join(args.output, "lora"))
    model.save_pretrained_gguf(args.output, tokenizer, quantization_method=args.quant)

    # Versi Unsloth berbeda menaruh file GGUF di folder output atau di "<output>_gguf".
    found = sorted(glob.glob(os.path.join(args.output, "**", "*.gguf"), recursive=True) + glob.glob(args.output + "_gguf/*.gguf"))
    found = [f for f in found if "mmproj" not in os.path.basename(f).lower()]
    if not found:
        raise SystemExit("Ekspor GGUF gagal, periksa log di atas.")
    # Taruh GGUF di sebelah Modelfile agar cukup menyalin satu folder ke laptop.
    target = os.path.join(args.output, os.path.basename(found[-1]))
    if os.path.abspath(found[-1]) != os.path.abspath(target):
        os.replace(found[-1], target)
    gguf = os.path.basename(target)

    with open(os.path.join(args.output, "Modelfile"), "w", encoding="utf-8") as handle:
        handle.write(
            f"FROM ./{gguf}\n\n"
            "# Template chat ChatML (Qwen), sama dengan saat training.\n"
            'TEMPLATE """{{- if .System }}<|im_start|>system\n{{ .System }}<|im_end|>\n{{ end }}'
            "{{- range .Messages }}<|im_start|>{{ .Role }}\n{{ .Content }}<|im_end|>\n{{ end }}"
            '<|im_start|>assistant\n"""\n\n'
            "PARAMETER temperature 0.7\n"
            "PARAMETER top_p 0.9\n"
            "PARAMETER repeat_penalty 1.12\n"
            "PARAMETER num_ctx 4096\n"
            'PARAMETER stop "<|im_end|>"\n'
            'PARAMETER stop "<|im_start|>"\n'
        )

    print(f"\nSelesai. Jalankan di laptop:\n  ollama create voic-coach -f {os.path.join(args.output, 'Modelfile')}\n"
          "Lalu ukur hasilnya:\n  python eval_dialogue.py --models voic-coach qwen3:4b")


if __name__ == "__main__":
    main()
