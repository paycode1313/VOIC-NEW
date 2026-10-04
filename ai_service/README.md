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
