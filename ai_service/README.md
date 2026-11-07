# 🧠 Panduan Menjalankan Layanan AI Lokal (FastAPI + Ollama) - Tim AI

Folder ini berisi layanan mikro Python (`ai_service.py`) yang menghubungkan aplikasi web Laravel dengan model AI lokal (Ollama) dan sintesis suara (TTS).

---

## 1. Langkah Persiapan di Laptop

### A. Download & Install Ollama
1. Unduh Ollama dari situs resmi: **[https://ollama.com/download](https://ollama.com/download)**
2. Install di laptop (Windows / macOS / Linux).

### B. Unduh Model Bahasa (LLM Ringan)
Buka Command Prompt / PowerShell, ketik:
```bash
# Model percakapan cepat bahasa Indonesia (sangat direkomendasikan untuk laptop standar):
ollama run llama3.2

# Atau alternatif yang sangat fasih bahasa Indonesia:
ollama run qwen2.5:3b
```
Tes percakapan singkat di terminal untuk memastikan model sudah terpasang.

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
- `POST /chat`: Menerima pesan ucapan pengguna beserta data ekspresi wajah kamera (`emotion`, `eye_contact_ratio`, `smile_detected`), memproses ke Ollama dengan persona karakter, dan menghasilkan suara balasan otomatis (TTS).
- `GET /audio/{filename}`: Mengunduh file audio balasan AI dalam format MP3.

---

## 4. Mode Ujian 3 Bagian (`/latihan`)

Halaman `/latihan` di Laravel memakai dua endpoint baru:

- `POST /exam/respond`: menjalankan model untuk satu giliran penguji. Prompt lengkap (persona, riwayat, instruksi giliran) dibuat oleh Laravel (`App\Services\Exam\ExaminerService`); service ini hanya menjalankan Ollama, membersihkan output, dan menjalankan *quality gate*. Jika balasan gagal, model diminta ulang sekali. Jika tetap gagal, Laravel memakai composer offline.
- `POST /exam/improve`: menulis ulang 3 jawaban terlemah menjadi versi yang lebih kuat untuk rapor.

Model menyusun seluruh giliran penguji dengan kata-katanya sendiri. Laravel hanya memberi tahu *apa* yang perlu dibahas lewat `directive`:
- `next`: tanggapi jawaban, lalu bawa topik berikutnya dari rencana ujian (dikirim sebagai panduan, bukan naskah).
- `follow_up`: gali jawaban lebih dalam dengan satu pertanyaan.
- `clarify`: peserta balik bertanya ("maksudnya gimana?"), jadi jelaskan lalu tanyakan ulang. Sesi tidak maju dan jawaban ini tidak dinilai.
- `react`: satu kalimat tanggapan, lalu Laravel membacakan pengumuman tetap (aturan kartu topik Bagian 2 atau penutup).

Naskah pertanyaan hanya dipakai bila model tidak tersedia atau balasannya gagal validasi. Ruang ujian menampilkan badge **AI lokal aktif / AI cloud aktif / AI offline · mode cadangan** (`GET /latihan/status-ai`). Kalau badge kuning, percakapan masih memakai jawaban cadangan berbasis aturan: jalankan Ollama dan `python ai_service.py`, atau isi `GROQ_API_KEY` di `.env`.

Kalau ingin memakai model selain `voic-coach`, set `VOIC_EXAM_MODEL=qwen2.5:7b` sebelum menjalankan service.

## 5. Melatih AI Lokal agar Lebih Nyambung

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
python eval_dialogue.py --models voic-coach qwen2.5:3b
```
Skrip ini menilai setiap balasan dengan quality gate yang sama dengan produksi: menyinggung isi jawaban, jumlah pertanyaan sesuai instruksi, dan panjang yang wajar.

### d. Fine-tune (LoRA) di GPU / Google Colab
```bash
pip install -r requirements-train.txt
python finetune_lora.py --train datasets/sft/voic_exam_sft.jsonl --eval datasets/sft/voic_exam_eval.jsonl
```
Loss hanya dihitung pada balasan penguji. Targetkan minimal 200-300 contoh. Dengan data seed saja (~40), gunakan `--epochs 2` agar tidak overfit.

### e. Pasang & bandingkan
```bash
ollama create voic-coach -f outputs/voic-coach/Modelfile
python eval_dialogue.py --models voic-coach qwen2.5:3b
```
Pakai model hasil fine-tune hanya jika *pass rate*-nya lebih tinggi dari baseline.

> Catatan: `datasets/voic_train_dialogue.jsonl` (dataset lama dari endpoint `/chat`) berisi balasan template berulang seperti *"Mengenai pembahasan '...' yang Saudara sampaikan"*. Jangan dipakai untuk training; quality gate baru menolak pola tersebut.
