# Database Schema - VOIC
**Voice & Optics Intelligent Coach (AI Roleplay, Real-Time Voice & Open Cam)**

## 1. Tabel `users` (Bawaan Laravel + Akun Pengguna)
- `id` (Primary Key, BigInt Unsigned)
- `name` (String)
- `email` (String, Unique)
- `password` (String, Hashed)
- `remember_token` (String, Nullable)
- `timestamps`

## 2. Tabel `ai_roles` (Menyimpan Karakter & Sifat AI)
Menyimpan karakter lawan bicara (Dosen Penguji, HRD, Investor) beserta kepribadian dan prompt instruksinya.
- `id` (Primary Key, BigInt Unsigned)
- `name` (String: misal "Dr. Ir. Hartono, M.T.")
- `role_type` (String, Index: misal "dosen_penguji", "hrd", "investor")
- `avatar` (String, Nullable: URL atau path foto/ilustrasi karakter)
- `description` (Text: ringkasan peran dan latar belakang karakter)
- `system_prompt` (Text: instruksi perilaku AI, watak, kriteria pengujian, dan gaya bicara)
- `personality_traits` (JSON, Nullable: misal `["kritis", "teliti", "menuntut logika kuat"]`)
- `voice_id` (String, Nullable: identitas model suara TTS yang digunakan)
- `difficulty_level` (String: "Mudah", "Sedang", "Sulit", Default "Sedang")
- `is_active` (Boolean, Default true)
- `timestamps`

## 3. Tabel `practice_sessions` (Rangkuman Sesi Latihan)
Menyimpan data ringkasan hasil latihan, skor terpisah (wajah & suara), dan kesimpulan evaluasi dari AI.
- `id` (Primary Key, BigInt Unsigned)
- `user_id` (Foreign Key ke `users.id`, Cascade on Delete, Nullable untuk demo booth tamu)
- `ai_role_id` (Foreign Key ke `ai_roles.id`, Nullable, Null on Delete)
- `scenario_type` (String: misal "Sidang Skripsi", "Wawancara HRD", "Pitching Investor")
- `duration_seconds` (Unsigned Integer, Default 0: total durasi latihan dalam detik)
- `face_score` (Decimal 5,2, Default 0.00: skor penilaian visual kontak mata & ekspresi wajah)
- `voice_score` (Decimal 5,2, Default 0.00: skor penilaian vokal, WPM, artikulasi nada)
- `overall_score` (Decimal 5,2, Default 0.00: agregasi skor akhir 0-100)
- `ai_conclusion` (Text, Nullable: ringkasan kesimpulan dan catatan penting dari AI)
- `feedback_notes` (JSON, Nullable: detail metrik, kelebihan, dan rekomendasi perbaikan)
- `timestamps`

## 4. Tabel `session_messages` (Riwayat Percakapan & Status Ekspresi Wajah)
Menyimpan riwayat obrolan bolak-balik antara pengguna dan AI secara real-time, lengkap dengan status ekspresi wajah pengguna pada detik kejadian.
- `id` (Primary Key, BigInt Unsigned)
- `practice_session_id` (Foreign Key ke `practice_sessions.id`, Cascade on Delete)
- `sender` (Enum/String: "user", "ai", "system")
- `message` (Text: isi percakapan yang diucapkan atau dibalas AI)
- `audio_url` (String, Nullable: URL rekaman audio / hasil sintesis suara TTS)
- `facial_status` (JSON, Nullable: status ekspresi wajah pengguna saat pesan diucapkan, misal `{"emotion": "nervous", "eye_contact_ratio": 72, "blink_rate": 14}`)
- `timestamp_seconds` (Unsigned Integer, Default 0: penanda waktu detik latihan)
- `timestamps`

---

## Relasi Antar Tabel (Entity Relationships)
```
[ users ] 1 ───< [ practice_sessions ] >─── 1 [ ai_roles ]
                          │ 1
                          │
                          ▼ *
                 [ session_messages ]
```
- 1 `User` memiliki banyak `PracticeSession`.
- 1 `AiRole` dapat digunakan dalam banyak `PracticeSession`.
- 1 `PracticeSession` memiliki banyak `SessionMessage` (riwayat obrolan turn-by-turn).