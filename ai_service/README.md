# 🧠 Panduan Menjalankan Layanan AI Lokal (FastAPI + Ollama) - Tim AI

Folder ini berisi layanan mikro Python (`ai_service.py`) yang menghubungkan aplikasi web Laravel dengan model AI lokal (Ollama) dan sintesis suara (TTS).

---

## 1. Langkah Persiapan di Laptop

### A. Download & Install Ollama
1. Unduh Ollama dari situs resmi: **[https://ollama.com/download](https://ollama.com/download)**
2. Install di laptop (Windows / macOS / Linux).

### B. Unduh Model Bahasa
Cara termudah: jalankan `python setup_models.py` (lihat bagian 4). Skrip itu mengunduh model yang
cocok dengan laptop dan membuat model `voic-coach`. Manual:
```bash
# Laptop biasa (CPU, RAM 8 GB+): paling nyambung untuk ukurannya
ollama pull qwen3:4b

# Laptop dengan GPU NVIDIA (VRAM 6 GB+): jauh lebih pintar
ollama pull qwen3:8b
```
Qwen3 butuh **Ollama 0.9 atau lebih baru** (agar mode "berpikir"-nya bisa dimatikan; kalau tidak,
balasan jadi sangat lambat). Cek dengan `ollama --version`. Ollama lama: pakai `qwen2.5:3b`.

---

## 2. Menjalankan Layanan AI Service (FastAPI)

1. Buka folder `ai_service`:
   ```bash
   cd ai_service
   ```
2. Pasang pustaka pendukung Python:
   ```bash
   pip install -r requirements.txt
   ```
3. Jalankan server FastAPI:
   ```bash
   python ai_service.py
   ```
   Layanan akan aktif di: **`http://127.0.0.1:8001`**

---

## 3. Endpoint API yang Disediakan

- `GET /`: Health check status layanan.
- `GET /health/ollama`: Memeriksa apakah aplikasi Ollama sudah aktif dan mendeteksi daftar model yang terpasang di laptop.
- `POST /tts`: Sintesis suara (Piper lokal, cadangan edge-tts).
- `GET /audio/{filename}`: Mengunduh file audio balasan AI (WAV/MP3).
- Endpoint ujian dan suara lokal dijelaskan di bagian 4 dan 5.

---

## 4. Persiapan Laptop Pameran (100% Lokal, Tanpa Internet)

Saat ada internet (sekali saja, sebelum hari H):
```bash
cd ai_service
pip install -r requirements.txt
python setup_models.py          # laptop CPU biasa
python setup_models.py --gpu    # laptop dengan GPU NVIDIA
```
Skrip ini mengunduh semua yang dibutuhkan agar booth bisa jalan tanpa internet:

| Bagian | Model | Catatan |
|---|---|---|
| Otak | Ollama `qwen3:4b` (CPU) / `qwen3:8b` (GPU) → `voic-coach` | 8B jauh lebih nyambung, butuh GPU ±6 GB VRAM. Cadangan otomatis: `qwen2.5:3b` / `qwen2.5:7b` |
| Telinga (STT) | faster-whisper `small` (CPU) / `large-v3-turbo` (GPU) | menggantikan pengenal suara Chrome/Edge yang sebenarnya online |
| Suara (TTS) | Piper `id_ID-news_tts-medium` | ±0,2 detik per kalimat; edge-tts (online) hanya cadangan |
| Mata | MediaPipe Face Landmarker → `public/vendor/mediapipe` | kontak mata di browser tanpa CDN |

Cek kesiapan kapan saja: `python setup_models.py --check`. Di ruang ujian, label kecil di bawah nama penguji menampilkan **Telinga / Suara / waktu respon** yang sedang dipakai.

### Kenapa terasa lebih "ngobrol"
- **Akhir bicara dideteksi lokal** (jeda ±1 detik, lebih sabar jika jawaban masih pendek), tidak menunggu 2,6 detik.
- **Satu kali kirim**: rekaman suara → Whisper → model, dalam satu request.
- **Streaming per kalimat**: kalimat pertama penguji sudah diucapkan selagi model menulis kalimat berikutnya. Audio dikirim di dalam stream (penting di Windows karena `php artisan serve` hanya punya satu worker).
- **Boleh menyela** (centang di ruang ujian, wajib pakai headset): bicara saat penguji bicara akan memotongnya.

### Variabel lingkungan (opsional)
`VOIC_EXAM_MODEL` (default `voic-coach`), `VOIC_STT_MODEL` (`small`), `VOIC_STT_DEVICE` (`auto`/`cuda`/`cpu`), `VOIC_STT_COMPUTE` (`int8`, atau `float16` untuk GPU), `VOIC_PIPER_VOICE_ID` (path `.onnx`).

## 5. Mode Ujian 3 Bagian (`/latihan`)

Halaman `/latihan` di Laravel memakai dua endpoint baru:

- `POST /exam/respond`: menjalankan model untuk satu giliran penguji. Prompt lengkap (persona, riwayat, instruksi giliran) dibuat oleh Laravel (`App\Services\Exam\ExaminerService`); service ini hanya menjalankan Ollama, membersihkan output, dan menjalankan *quality gate*. Jika balasan gagal, model diminta ulang sekali. Jika tetap gagal, Laravel memakai composer offline.
- `POST /exam/respond/stream`: sama, tetapi NDJSON per kalimat beserta audionya (dipakai ruang ujian).
- `POST /stt`: speech-to-text lokal (faster-whisper). `GET /voice/status`: status otak, telinga, dan suara.
- `POST /exam/improve`: menulis ulang 3 jawaban terlemah menjadi versi yang lebih kuat untuk rapor.

Model menyusun seluruh giliran penguji dengan kata-katanya sendiri. Laravel hanya memberi tahu *apa* yang perlu dibahas lewat `directive`:
- `next`: tanggapi jawaban, lalu bawa topik berikutnya dari rencana ujian (dikirim sebagai panduan, bukan naskah).
- `follow_up`: gali jawaban lebih dalam dengan satu pertanyaan.
- `clarify`: peserta balik bertanya ("maksudnya gimana?"), jadi jelaskan lalu tanyakan ulang. Sesi tidak maju dan jawaban ini tidak dinilai.
- `react`: satu kalimat tanggapan, lalu Laravel membacakan pengumuman tetap (aturan kartu topik Bagian 2 atau penutup).

Naskah pertanyaan hanya dipakai bila model tidak tersedia atau balasannya gagal validasi. Ruang ujian menampilkan badge **AI lokal aktif / AI cloud aktif / AI offline · mode cadangan** (`GET /latihan/status-ai`). Kalau badge kuning, percakapan masih memakai jawaban cadangan berbasis aturan: jalankan Ollama dan `python ai_service.py`, atau isi `GROQ_API_KEY` di `.env`.

Kalau ingin memakai model selain `voic-coach`, set `VOIC_EXAM_MODEL=qwen3:8b` sebelum menjalankan service.

### Supaya obrolan nyambung
- **Ingatan sesi**: 12 pesan terakhir dikirim utuh; jawaban dan pertanyaan yang lebih lama diringkas ke prompt sistem, jadi penguji tetap ingat nama, proyek, dan angka yang disebut peserta di awal.
- **Konteks 4096 token** (`VOIC_NUM_CTX`): persona tidak pernah terpotong walau sesi panjang.
- **Mode berpikir dimatikan** (`VOIC_THINK=0`): penalaran `<think>` Qwen3 tidak pernah diucapkan. Set `VOIC_THINK=1` hanya untuk eksperimen (jauh lebih lambat).
- Bandingkan model di laptopmu sendiri: `python eval_dialogue.py --models voic-coach qwen3:4b qwen2.5:3b`.

## 6. Melatih AI Lokal agar Lebih Nyambung

Siklusnya: **kumpulkan data → export → fine-tune → evaluasi → pasang**.

### a. Kumpulkan data
Ada tiga sumber, semuanya memakai format prompt yang sama dengan saat ujian:
1. **Seed manual**: `datasets/seed/exam_seed_dialogues.json`, berisi dialog contoh berkualitas. Tambahkan dialog baru di sini (tiap giliran: `part`, `question`, `answer`, `directive` = `react`/`follow_up`, `reply`).
2. **Rating pengguna**: di halaman rapor, buka *Transkrip lengkap* lalu beri 👍 pada balasan penguji yang nyambung. Balasan 👍 ikut di-export, balasan 👎 dibuang.
3. **Distilasi dari model besar**: isi `GROQ_API_KEY` atau `OPENAI_API_KEY` di `.env` Laravel selama fase pengumpulan data. Saat Ollama mati, balasan model cloud dipakai dan otomatis menjadi data latih (kecuali diberi 👎).

Balasan model lokal yang lolos quality gate juga dipanen ke `datasets/harvest/exam_turns.jsonl`. File ini hanya dipakai untuk training setelah direview manusia: set `"reviewed": true` pada baris yang bagus, lalu simpan sebagai `exam_turns.reviewed.jsonl`.

### b. Export dataset
```bash
php artisan voic:export-training-data
```
Hasilnya `datasets/sft/voic_exam_sft.jsonl` (train) dan `voic_exam_eval.jsonl` (evaluasi, dipisah secara deterministik). Folder ini di-*gitignore* karena bisa berisi percakapan pengguna.

### c. Ukur model sekarang (baseline)
```bash
python eval_dialogue.py --models voic-coach qwen3:4b qwen2.5:3b
```
Skrip ini menilai setiap balasan dengan quality gate yang sama dengan produksi: menyinggung isi jawaban, jumlah pertanyaan sesuai instruksi, dan panjang yang wajar.

### d. Fine-tune (LoRA) di GPU / Google Colab
```bash
pip install -r requirements-train.txt
python finetune_lora.py --train datasets/sft/voic_exam_sft.jsonl --eval datasets/sft/voic_exam_eval.jsonl
```
Skrip fine-tune memakai **Qwen3-4B-Instruct** (muat di Colab T4 gratis). Notebook siap pakai: `colab/latih_voic_coach.ipynb`. Data guru (Kimi K3) dibuat dengan `php artisan voic:teacher-generate`. Model hasil fine-tune dipasang dengan Modelfile dari `outputs/`, bukan dengan `setup_models.py`.
Loss hanya dihitung pada balasan penguji. Targetkan minimal 200-300 contoh. Dengan data seed saja (~40), gunakan `--epochs 2` agar tidak overfit.

### e. Pasang & bandingkan
```bash
ollama create voic-coach -f outputs/voic-coach/Modelfile
python eval_dialogue.py --models voic-coach qwen3:4b qwen2.5:3b
```
Pakai model hasil fine-tune hanya jika *pass rate*-nya lebih tinggi dari baseline.

> Catatan: dataset lama dari endpoint `/chat` (balasan template seperti *"Mengenai pembahasan '...' yang Saudara sampaikan"*) sudah dihapus bersama endpoint-nya; quality gate menolak pola tersebut.
