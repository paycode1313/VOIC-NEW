# Diagram Sistem VOIC

| Diagram | Gambar | Sumber |
|---|---|---|
| ERD (notasi Crow's Foot) | `erd.png`, `erd.svg` | `erd.mmd` (Mermaid) |
| DFD Level 0 (diagram konteks) | `dfd-level-0.png` | `dfd-level-0.json` |
| DFD Level 1 | `dfd-level-1.png` | `dfd-level-1.json` |
| DFD Level 2, proses 3.0 Simulasi Panggilan | `dfd-level-2-proses-3.png` | `dfd-level-2-proses-3.json` |
| DFD Level 2, proses 4.0 Penilaian & Rapor | `dfd-level-2-proses-4.png` | `dfd-level-2-proses-4.json` |
| Arsitektur sistem | `arsitektur.png`, `arsitektur-dark.png`, `arsitektur.html` | dibuat dengan [stack-diagram](https://github.com/caru-ini/stack-diagram) |

Setiap DFD juga tersedia sebagai `.svg` (tajam di ukuran berapa pun) dan `.html`.

## Notasi DFD (Yourdon/DeMarco)

- **Lingkaran**: proses, bernomor (0, 1.0, 3.1, ...).
- **Kotak**: entitas luar (Peserta, Operator Booth, Tim Peneliti, layanan online).
- **Dua garis sejajar**: penyimpanan data (D1 sampai D7).
- **Tanda \***: entitas atau penyimpanan yang digambar ulang agar garis tidak bersilangan.

## Penyimpanan data dan tabelnya

| Kode | Nama | Tabel / lokasi |
|---|---|---|
| D1 | Pengguna | `users` |
| D2 | Sesi Latihan | `practice_sessions` |
| D3 | Peran Penguji | `ai_roles` |
| D4 | Percakapan | `session_messages` |
| D5 | Hasil Uji Coba | `study_attempts` |
| D6 | Kuesioner | `study_surveys` |
| D7 | Rekaman Jawaban | berkas audio di `backend/storage/app` (bukan tabel) |

Tabel bawaan Laravel (`sessions`, `cache`, `jobs`, `failed_jobs`) tidak dimasukkan ke ERD karena bukan data bisnis.

## Memperbarui diagram

- **ERD**: ubah `erd.mmd`, lalu render di <https://mermaid.live> atau dengan Mermaid CLI.
- **DFD**: ubah file `.json`, lalu jalankan `python dfd.py dfd-level-1.json dfd-level-1 ../../frontend/node_modules/@fontsource/figtree/files`.
  Hasilnya `.svg` dan `.html`; buka `.html` di browser lalu simpan sebagai gambar, atau screenshot.
- Diagram ini dibuat berdasarkan kode per 16 November 2026 (migrasi database dan layanan di `backend/app/Services/Exam`).
