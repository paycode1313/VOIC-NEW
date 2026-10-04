# Database Schema - VOIC

## 1. Tabel `users` (Bawaan Laravel + Tambahan)
- `id` (Primary Key)
- `name` (String)
- `email` (String, Unique)
- `password` (String)
- `timestamps`

## 2. Tabel `practice_sessions` (Menyimpan riwayat latihan)
- `id` (Primary Key)
- `user_id` (Foreign Key ke tabel `users`)
- `scenario_type` (String: misal "Sidang Skripsi", "Pitching")
- `duration_seconds` (Integer)
- `overall_score` (Integer / Float)
- `feedback_notes` (Text / JSON - catatan dari sistem)
- `timestamps`