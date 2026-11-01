"""
VOIC Interactive AI Training & Dialogue Tester
Jalankan skrip ini di terminal untuk melatih, menguji keluwesan, dan mengecek kesinambungan
alur bicara AI VOIC secara langsung (Real-Time).
"""

import sys
import time
import json
import os
import httpx

OLLAMA_URL = "http://127.0.0.1:11434/api/chat"
MODEL_NAME = "voic-coach"

PERSONAS = {
    "1": {
        "title": "Dosen Penguji Sidang Skripsi (Prof. Hendra)",
        "role_type": "dosen_penguji",
        "system": (
            "Kamu adalah Prof. Hendra, Dosen Penguji Sidang Skripsi di universitas terkemuka. "
            "Karaktermu: berwibawa, to-the-point, kritis, tapi manusiawi dan mengalir luwes. "
            "Dengarkan apa yang dikatakan mahasiswa dengan seksama. Jika mahasiswa menyapa/berkenalan, sambut dengan hormat dan persilakan masuk ke topik. "
            "Jika mahasiswa memaparkan penelitian, tanggapi substansinya dan ajukan 1 pertanyaan tajam seputar metodologi atau data. "
            "Bicaralah dalam 2-3 kalimat lisan yang padat tanpa format bullet point."
        )
    },
    "2": {
        "title": "HRD Recruiter Senior (Kak Nadia)",
        "role_type": "hrd",
        "system": (
            "Kamu adalah Nadia, HR Recruiter senior yang ramah, hangat, luwes, dan sangat komunikatif. "
            "Karaktermu: tidak kaku, bicaralah santai seperti manusia nyata yang sedang berbincang dengan kandidat. "
            "Dengarkan lawan bicara, sambut perkenalan mereka dengan hangat, gali pengalaman nyata dan cara kerja mereka. "
            "Sampaikan respon dalam 2-3 kalimat mengalir dan akhiri dengan pertanyaan pemantik yang relevan."
        )
    },
    "3": {
        "title": "Investor / Venture Capitalist (Pak William)",
        "role_type": "investor",
        "system": (
            "Kamu adalah Pak William, seorang Venture Capitalist kawakan. "
            "Karaktermu: tajam, to-the-point, fokus pada validasi pasar nyata, unit economics, dan keunggulan kompetitif, "
            "tapi tetap komunikatif dan mengalir luwes seperti rekan bisnis profesional. "
            "Tanggapi poin yang dipaparkan founder dalam 2-3 kalimat, lalu tanyakan angka atau strategi defensible moat mereka."
        )
    },
    "4": {
        "title": "VOIC Casual AI Coach (Mitra Santai)",
        "role_type": "casual",
        "system": (
            "Kamu adalah VOIC AI Coach, mitra latihan public speaking yang ramah, santai, empatik, dan seru diajak ngobrol. "
            "Bicaralah dalam bahasa Indonesia yang hidup dan luwes, mengalir alami seperti teman mengobrol yang cerdas. "
            "Tanggapi topik apa pun yang dibicarakan pengguna secara nyambung, beri dorongan positif, dan jaga percakapan tetap asyik dalam 2-3 kalimat."
        )
    }
}

DATASET_FILE = os.path.join(os.path.dirname(__file__), "datasets", "voic_train_dialogue.jsonl")


def record_training_turn(role_type: str, user_text: str, assistant_text: str):
    """Menyimpan turn berkualitas tinggi ke dataset latih JSONL untuk continuous fine-tuning."""
    try:
        os.makedirs(os.path.dirname(DATASET_FILE), exist_ok=True)
        record = {
            "timestamp": time.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "role_type": role_type,
            "messages": [
                {"role": "user", "content": user_text},
                {"role": "assistant", "content": assistant_text}
            ]
        }
        with open(DATASET_FILE, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")
    except Exception:
        pass


def main():
    print("=" * 65)
    print("   🎙️  VOIC AI INTERACTIVE CONVERSATION TRAINER  🎙️")
    print("=" * 65)
    print(f"Model Engine: {MODEL_NAME} (Ollama Local)")
    print("\nPilih Persona Lawan Bicara:")
    for k, v in PERSONAS.items():
        print(f" [{k}] {v['title']}")
    
    choice = input("\nPilih nomor persona (1-4) [Default: 4]: ").strip()
    if choice not in PERSONAS:
        choice = "4"
        
    active_persona = PERSONAS[choice]
    print(f"\n[OK] Mode Aktif: {active_persona['title']}")
    print("-" * 65)
    print("Ketik pesan Anda dan tekan Enter untuk berbicara.")
    print("Perintah khusus:")
    print("  /reset   -> Hapus riwayat percakapan (mulai sesi baru)")
    print("  /persona -> Ganti persona lawan bicara")
    print("  exit     -> Keluar")
    print("-" * 65 + "\n")

    history = [
        {"role": "system", "content": active_persona["system"]}
    ]

    while True:
        try:
            user_input = input("👤 Anda: ").strip()
            if not user_input:
                continue

            if user_input.lower() in ("exit", "quit", "/exit"):
                print("\nTerima kasih! Sesi latihan selesai.\n")
                break

            if user_input.lower() == "/reset":
                history = [{"role": "system", "content": active_persona["system"]}]
                print("\n[OK] Riwayat percakapan telah di-reset. Memulai sesi baru.\n")
                continue

            if user_input.lower() == "/persona":
                print("\nPilih Persona Baru:")
                for k, v in PERSONAS.items():
                    print(f" [{k}] {v['title']}")
                c = input("Nomor (1-4): ").strip()
                if c in PERSONAS:
                    active_persona = PERSONAS[c]
                    history = [{"role": "system", "content": active_persona["system"]}]
                    print(f"\n[OK] Persona berganti menjadi: {active_persona['title']}\n")
                continue

            # Tambahkan ke riwayat
            history.append({"role": "user", "content": user_input})

            # Batasi window riwayat (maksimal 10 turn terakhir agar konteks fokus)
            windowed_messages = [history[0]] + history[-10:]

            start_t = time.time()
            with httpx.Client(timeout=30.0) as client:
                res = client.post(
                    OLLAMA_URL,
                    json={
                        "model": MODEL_NAME,
                        "messages": windowed_messages,
                        "stream": False,
                        "options": {
                            "temperature": 0.72,
                            "top_p": 0.9,
                            "repeat_penalty": 1.15
                        }
                    }
                )
            
            latency = (time.time() - start_t) * 1000

            if res.status_code == 200:
                reply = res.json().get("message", {}).get("content", "").strip()
                history.append({"role": "assistant", "content": reply})
                
                print(f"\n🤖 AI: {reply}")
                print(f"   └─ ⚡ Latensi: {latency:.0f}ms | Model: {MODEL_NAME}\n")

                # Rekam untuk pembelajaran berkelanjutan
                record_training_turn(active_persona["role_type"], user_input, reply)
            else:
                print(f"\n[Error] Gagal mendapatkan respon dari Ollama (Status: {res.status_code})\n")

        except KeyboardInterrupt:
            print("\nSelesai.")
            break
        except Exception as e:
            print(f"\n[Error] {e}\n")


if __name__ == "__main__":
    main()
