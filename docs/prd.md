# Product Requirements Document (PRD) - VOIC

## 1. Deskripsi Singkat
VOIC adalah aplikasi berbasis web (Laravel 12) yang dirancang untuk melatih kemampuan public speaking, gestur/ekspresi wajah, dan intonasi suara pengguna dengan bantuan AI. Aplikasi ini ditujukan untuk persiapan sidang skripsi, pitching startup, atau presentasi umum.

## 2. Target Pengguna (User)
- Mahasiswa/Umum: Yang ingin melatih kepercayaan diri dan teknik berbicara.
- Pengunjung Innofest (Juri/Audience): Yang mencoba demo aplikasi secara langsung.

## 3. Fitur Minimum Viable Product (MVP) - Fokus Innofest
- [ ] **Autentikasi Pengguna:** Register, Login, Logout (Laravel Breeze/Jetstream).
- [ ] **Dashboard Utama:** Menampilkan riwayat skor latihan sebelumnya dan grafik perkembangan.
- [ ] **Sesi Latihan (Practice Mode):** 
  - Pengguna memilih topik/skenario (Misal: Sidang Skripsi).
  - Akses webcam & mikrofon langsung dari browser.
  - Tombol Mulai / Berhenti Rekam.
- [ ] **Modul Analisis AI (Sederhana untuk Demo):**
  - Merekam durasi bicara.
  - Deteksi ekspresi wajah dasar (tersenyum/tegang) menggunakan Face API / Client-side script.
  - Memberikan ringkasan skor akhir setelah latihan selesai.