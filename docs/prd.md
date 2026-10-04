# Product Requirements Document (PRD) - VOIC
**Voice & Optics Intelligent Coach (AI Roleplay, Real-Time Voice & Open Cam)**

## 1. Deskripsi Singkat
VOIC adalah platform web interaktif cerdas (Laravel 12 + Client-Side Vision + Local LLM Brain) yang mensimulasikan sesi latihan berbicara dua arah (AI Roleplay) secara tatap muka (Open Cam) dengan respons suara manusia (Real-Time Voice). Pengguna dapat berlatih menghadapi karakter AI seperti Dosen Penguji Skripsi yang kritis, HRD Recruiter yang teliti, atau Investor Startup yang berorientasi bisnis.

## 2. Target Pengguna (User)
- **Mahasiswa Tingkat Akhir:** Mempersiapkan mental, gestur, dan argumentasi sebelum menghadapi sidang tugas akhir/skripsi.
- **Job Seeker & Fresh Graduate:** Melatih teknik wawancara kerja (metode STAR) dengan ekspresi percaya diri.
- **Founder / Presenter:** Mengasah pitch deck di hadapan simulasi investor.
- **Juri & Pengunjung Pameran Innofest 2026:** Mencoba demo interaktif percakapan AI lokal langsung di tempat.

## 3. Pilar Fitur Utama (Core Pillars)

### A. AI Roleplay Engine (Karakter Spesifik & Bernyawa)
- Karakter AI memiliki persona, watak, dan *system prompt* tersendiri:
  1. **Dr. Ir. Hartono, M.T. (Dosen Penguji Skripsi):** Kritis, berwibawa, menuntut kejelasan metodologi, serta menegur jika mahasiswa tidak menatap layar atau terlihat ragu.
  2. **Nadia Putri, S.Psi (HRD Recruitment Lead):** Profesional, ramah namun analitis, menguji ketenangan di bawah tekanan dan artikulasi jawaban.
  3. **David Wijaya (Managing Partner Angel Investor):** Cepat, to-the-point, fokus pada nilai bisnis, validasi pasar, dan solusi konkret.

### B. Open Cam & Visual Telemetry (Kamera Real-Time)
- **MediaPipe / Face-API Integration:** Membaca feed webcam secara *real-time*:
  - **Kontak Mata (Eye Contact Ratio):** Mendeteksi apakah pengguna konsisten menatap lensa kamera atau menunduk/melihat ke arah lain.
  - **Status Emosi & Ekspresi (Facial Composure):** Mendeteksi senyum, wajah rileks, atau ketegangan otot wajah.
  - **Teguran Langsung:** AI dapat merespons ekspresi visual pengguna (misal: *"Saya lihat kamu terlihat tegang, coba tarik napas dan jelaskan ulang poin nomor dua"*).

### C. Real-Time Voice & Audio Orb (Interaksi Suara Dua Arah)
- **Speech-to-Text (STT) + Silence Detection:** Saat pengguna berbicara dan terdeteksi hening selama 1.5 detik, audio otomatis diubah menjadi teks dan dikirim ke server.
- **Audio Orb Animation:** Animasi gelombang suara interaktif di tengah layar yang bergerak saat AI berbicara.
- **Local AI Brain & TTS (Ollama + Fast Voice):** Menghasilkan balasan suara natural berbahasa Indonesia tanpa latensi server awan.

### D. Rapor Akhir Evaluasi (Comprehensive Assessment)
- Riwayat percakapan lengkap (*turn-by-turn chat transcript*).
- Skor terpisah:
  - **Skor Wajah (Face Score):** Konsistensi kontak mata dan relaksasi gestur.
  - **Skor Suara (Voice Score):** Kecepatan bicara (WPM) dan kejelasan intonasi.
  - **Skor Keseluruhan (Overall Score).**
- Saran perbaikan personal dari AI untuk perbaikan latihan berikutnya.