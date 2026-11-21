# Pembaruan 21 November 2026

Ringkasan perubahan di `main` hari ini, untuk anggota tim yang baru `git pull`.

## Setelah `git pull`

```bash
cd frontend && npm install && npm run build   # ada perubahan tampilan & JS (font Bricolage Grotesque baru)
cd ../backend && composer install && php artisan migrate
```

Tidak ada migration baru dan tidak ada setting `.env` baru. Mode online (Groq) tetap memakai `GROQ_API_KEY` yang sama.

## Struktur folder (penting)

Branch `karakter-suara-dan-tampilan` sudah digabung ke `main`. Struktur yang dipakai adalah struktur di `main`:
halaman Blade di `backend/resources/views`, JS/CSS di `frontend/src/js` dan `frontend/src/css`.
Folder `frontend/views`, `frontend/js`, `frontend/css`, dan `backend/config/view.php` dari branch itu **tidak dipakai lagi**;
isinya sudah dipindah ke lokasi di atas.

## Cara bicara penguji (AI)

Diuji langsung dengan model cloud (Groq) di ketiga penguji, mode coaching, dan bahasa Inggris.

- **Tidak membeo lagi.** Sebelumnya setiap balasan mengulang jawaban peserta lalu memuji ("Profiling query, terus pakai
  eager loading sama index, terus respons turun ke 300 milidetik, mantap!"). Sekarang penguji menanggapi satu detail
  dengan kata-katanya sendiri.
- **Pujian tidak di setiap giliran.** Jika giliran sebelumnya memuji, giliran berikutnya mendapat tag `TANPA PUJIAN`
  (berlaku juga untuk coach).
- Pertanyaan tetap lisan dan pendek ("gimana cara kamu ngukurnya?", bukan "bagaimana kamu mengukurnya?").
- Tanda pisah, titik koma, dan kurung diubah menjadi jeda koma sebelum dibacakan suara.
- **Bug yang diperbaiki:**
  - Reaksi pendek seperti "300 milidetik?" membuang pertanyaan aslinya.
  - Balasan cloud yang terpotong karena batas token ("…itu kan jawaban buat to") ikut diucapkan.
  - Tag instruksi ikut dibacakan ("ANNOUNCE: Now we're in Part 3").
  - Pengumuman bagian terucap dua kali.
  - "Oke… Oke, sekarang kita masuk…".
  - Kalimat yang menempel tanpa spasi ("ya?Bagus") membuat satu giliran sangat panjang.
- Memilih "Fresh Graduate" atau "Mahasiswa" di kolom kampus tidak lagi menghasilkan sapaan "Halo Rina dari Fresh Graduate".
- Coach tidak lagi menyuruh mengganti kata lisan yang wajar ("pakai" → "menggunakan").
- Layanan AI lokal (Python) menandai balasan yang membeo agar tidak dipanen sebagai data latih.

## Rapor

- **Versi jawaban yang lebih kuat** kini juga ditulis oleh model cloud, sehingga tetap muncul di mode online
  (laptop tanpa GPU). Sebelumnya hanya muncul template STAR generik. Ketiga jawaban terlemah ditulis ulang dalam
  satu permintaan, tanpa label "Situasi:", dan tanpa mengarang angka (bagian yang kurang diisi `[placeholder]`).
- Tombol **Dengarkan** untuk mendengar versi jawaban yang lebih kuat.
- Kartu yang terbuka otomatis adalah jawaban terlemah yang punya versi lebih kuat. Kartu lain yang punya versi
  lebih kuat diberi lencana "Ada versi lebih kuat".
- **Latih ulang pertanyaan ini**: dari setiap jawaban di rapor, buka sesi coaching (tanpa nilai) yang langsung
  membahas pertanyaan itu. Coach tahu jawaban pertamanya dan membandingkannya.
- Saat rapor disusun, layar menampilkan langkah yang sedang dikerjakan (tempo, kata pengisi, empat kriteria,
  versi jawaban yang lebih kuat).
- Kata pengisi yang dipanjangkan ("eee", "emmm", "hmmm", "uhhh") sekarang terhitung dan ditandai.
- Penilaian yang lebih adil: target 160 kata hanya untuk cerita 2 menit di Bagian 2, bukan untuk pertanyaan
  lanjutan sesudahnya.
- "Kalimat yang bisa kamu pakai" di catatan coaching hanya berisi contoh kalimat utuh, bukan potongan teks.

## Tampilan

- **HP:**
  - Di beranda, avatar tidak lagi menimpa teks dan header mockup panggilan tidak lagi terpecah.
  - Di ruang panggilan, header hanya menampilkan "Bagian 1/3" dan tile "Kamu" tidak lagi bertumpuk.
- **Dashboard:**
  - "Band terbaik 4.5" menggantikan "Skor terbaik 50".
  - Durasi ditampilkan sebagai "7:00 mnt", bukan "7m 0d".
  - Nama kriteria tidak terpotong lagi.
- **Login:** label "atau akses cepat" satu baris, dan mockup memakai nama penguji yang baru.
- **Riwayat:** lencana menampilkan nama penguji saat ini (VOIC-INTERVIEW, bukan VOIC-HRD).

## Tes

`php artisan test` (208 tes) dan `python -m unittest discover -s tests` di `backend/ai_service` (79 tes) lulus semua.
Setiap perbaikan di atas punya tesnya sendiri.
