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
- **Pelafalan:** "Rp 12-15 juta" dibaca "12 sampai 15 juta rupiah" (sebelumnya "12 rupiah-15 juta"). Rentang angka
  dibaca "sampai"/"to". Aturan ini berlaku sama di suara server (Python) dan suara browser (JS).
- Penguji tidak menanyakan ulang hal yang sudah dijawab: Dosen tidak lagi bertanya "berapa jumlah data?" setelah
  peserta menyebut "2000 gambar", dan Investor mengenali "buat ekspansi" / "nambah tim" sebagai penggunaan dana.
- **Tanpa internet dan tanpa AI lokal** (composer offline):
  - Coach mengenali permintaan topik (kelemahan, gaji, kelebihan, perkenalan, kenapa tertarik, konflik) dan
    memberi tips khusus beserta pertanyaan latihannya.
  - Coach membalas ucapan terima kasih dengan hangat dan tidak mengulang tips yang sama.
  - Kutipan jawaban memakai huruf asli peserta ("API-nya", bukan "api-nya"), dan HRD konsisten memakai "aku".

## AI lokal (Ollama)

- AI lokal kini memakai **prompt ringkas** (sekitar 400 token, sebelumnya 2400). Diukur di laptop ini (Qwen2.5 3B, CPU
  sekitar 23 token/detik): giliran pertama turun dari 90 detik (gagal) menjadi 43 detik, giliran berikutnya 15-19 detik,
  dan balasannya **nyambung** dengan jawaban peserta (sebelumnya salah tangkap dan mengulang instruksinya sendiri).
  AI cloud tetap memakai prompt lengkap. Setel `VOIC_LOCAL_PROMPT=full` untuk laptop dengan GPU.
- AI lokal tetap jauh lebih lambat dari Groq, jadi untuk booth sebaiknya tetap mode online dengan AI lokal hanya sebagai cadangan.

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
- **Perbandingan dengan sesi sebelumnya** (akun yang login): "▲ +0.5 dari sesi Wawancara Kerja sebelumnya".
- Versi jawaban yang lebih kuat tidak mengarang pengalaman, proyek, atau pencapaian yang tidak disebut peserta.
- **Catatan coaching** ("Sudah bagus", "Latih berikutnya", "Kalimat yang bisa kamu pakai") kini juga ditulis model cloud
  di mode online, sehingga isinya tentang percakapan itu sendiri, bukan kalimat umum. Contoh kalimat tidak mengarang
  angka, dan template berisi "…" tidak dimasukkan ke daftar.
- **Menyelesaikan sesi tidak pernah menunggu kuota cloud.** Jika kuota gratis Groq habis tepat saat sesi berakhir,
  rapor langsung jadi dengan kerangka jawaban (sebelumnya bisa tertahan beberapa kali 6 detik).
- **Jawaban terbaikmu**: kartu yang mengutip jawaban dengan band tertinggi dan kriteria terkuatnya, ditampilkan sebelum
  daftar hal yang perlu diperbaiki.

## Kecepatan "Selesai"

- Rapor **langsung tampil** setelah "Selesai" (sekitar 0,4 detik, sebelumnya 4 detik atau lebih, dan bisa lebih dari
  30 detik jika jatuh ke AI lokal). Versi jawaban yang lebih kuat (ujian) dan catatan coach (coaching) ditulis AI
  **setelah** halaman rapor terbuka dan muncul sendiri beberapa detik kemudian ("Lagi nulis…"). Sementara itu
  kerangka jawaban dan catatan dasar sudah terlihat.
- `jalankan_voic.bat` mengaktifkan **OPcache** untuk server web tanpa mengubah `php.ini`. Setiap request turun dari
  sekitar 0,9 detik menjadi sekitar 0,1 detik, termasuk setiap giliran menjawab di panggilan.

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
- **Form:** petunjuk kolom wajib dalam bahasa Indonesia ("Kolom ini perlu diisi", "Isi dulu, atau pilih salah satu di bawah"),
  bukan "Please fill out this field" dari browser.
- **Lebih ringan:** Chart.js hanya dimuat di halaman yang menggambar grafik, sehingga skrip utama turun dari 293 KB menjadi 93 KB
  (gzip 33 KB) di semua halaman lain, termasuk ruang panggilan di HP pengunjung.
- **Booth:** pengunjung yang mencoba berkali-kali hanya muncul sekali di papan peringkat, dengan band terbaiknya.

## Tes

`php artisan test` (222 tes) dan `python -m unittest discover -s tests` di `backend/ai_service` (80 tes) lulus semua.
Setiap perbaikan di atas punya tesnya sendiri.
