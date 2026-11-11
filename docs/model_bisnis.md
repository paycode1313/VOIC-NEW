# Model Bisnis & Keberlanjutan VOIC (draf)

> Ini draf untuk dibahas tim. Semua angka bertanda **[ISI]** adalah asumsi yang harus diganti dengan data
> atau hasil wawancara calon pembeli sebelum dipresentasikan. Juri biasanya menanyakan dasar setiap angka.

---

## 1. Siapa yang memakai, siapa yang membayar

| Segmen | Pemakai | Pembayar | Masalah yang mereka bayar untuk diselesaikan |
|---|---|---|---|
| **Kampus: prodi & biro karier** (utama) | Mahasiswa tingkat akhir, calon lulusan | Fakultas / biro karier / pusat pengembangan karier | Mahasiswa gugup saat sidang dan wawancara kerja; dosen tidak punya waktu melatih satu per satu; kampus dinilai dari kesiapan kerja lulusan |
| **Inkubator & program startup** | Tim startup | Inkubator, program akselerasi, kompetisi bisnis | Latihan pitching sebelum demo day tanpa menyita waktu mentor |
| **Lembaga pelatihan kerja / bootcamp** | Peserta pelatihan | Lembaga | Simulasi wawancara sebagai bagian kurikulum persiapan kerja |
| **Pengguna umum** | Pencari kerja, mahasiswa | Gratis (pintu masuk), sebagian berlangganan | Latihan kapan saja dari HP atau laptop |

**Fokus tahun pertama:** kampus, karena satu keputusan pembelian mencakup ratusan mahasiswa, dan VOIC
cocok dipasang di lab atau server kampus (berjalan offline, data mahasiswa tidak keluar dari kampus).

---

## 2. Penawaran & harga (usulan, perlu divalidasi)

| Paket | Isi | Usulan harga |
|---|---|---|
| **Gratis** | Mode latihan dan coaching di web, rapor dasar | Rp0 |
| **Pribadi** | Riwayat kemajuan, latihan tanpa batas, sertifikat | [ISI] per bulan |
| **Kampus / lembaga** | Pemasangan di server atau lab kampus, akun mahasiswa, dashboard dosen/biro karier, persona penguji sesuai prodi, pelatihan operator | [ISI] per tahun per prodi / per kampus |
| **Event & booth** | Mode booth (operator ber-PIN, papan skor, QR rapor) untuk job fair, open house, kompetisi | [ISI] per acara |

Cara menentukan [ISI]: tanyakan ke 3–5 biro karier atau prodi berapa anggaran mereka untuk pelatihan
persiapan kerja atau sidang per tahun, lalu bandingkan dengan biaya alternatifnya (jam dosen/mentor, coaching profesional).

---

## 3. Struktur biaya

| Biaya | Keterangan |
|---|---|
| **AI per percakapan** | Hampir nol: model AI, pengenal suara, dan suara penguji berjalan di perangkat sendiri. Tidak ada tagihan API per percakapan. |
| **Server / perangkat** | Paket kampus memakai server atau PC lab milik kampus. Untuk versi web publik: [ISI] per bulan. |
| **Pengembangan & perawatan** | Waktu tim. Melatih ulang model memakai Google Colab gratis. |
| **Pemasaran & penjualan** | Demo ke kampus, booth di job fair, kerja sama dengan himpunan mahasiswa. |

Keunggulan biaya: karena AI berjalan lokal, menambah pengguna tidak menambah biaya AI. Ini berbeda
dengan aplikasi yang memanggil API AI berbayar setiap kali pengguna bicara.

---

## 4. Rencana 12 bulan

| Waktu | Target | Ukuran keberhasilan |
|---|---|---|
| Bulan 1–2 | Uji coba ke 20–30 mahasiswa (`voic:study-report`), latih `voic-coach` dan bandingkan dengan model lama (`voic:compare-models`) | Skor latihan ke-3 lebih tinggi dari latihan ke-1; ≥ 70% peserta setuju "lebih percaya diri" **[ISI hasil]** |
| Bulan 3–4 | Pilot gratis di 1–2 prodi atau biro karier kampus sendiri | [ISI] mahasiswa aktif, surat pernyataan / testimoni dari prodi |
| Bulan 5–6 | Dashboard dosen/biro karier, persona penguji per prodi | Prodi pilot bersedia lanjut berbayar atau merekomendasikan |
| Bulan 7–9 | Penjualan ke [ISI] kampus lain; mode booth disewakan untuk job fair | [ISI] kontrak pertama |
| Bulan 10–12 | Aplikasi mobile dirilis, versi publik freemium | [ISI] pengguna terdaftar, [ISI]% berbayar |

---

## 5. Keberlanjutan

- **Ekonomi:** biaya AI tidak naik seiring jumlah pengguna; pendapatan utama dari lisensi tahunan institusi yang berulang setiap tahun ajaran.
- **Produk yang terus membaik:** setiap balasan penguji bisa dinilai pengguna (bagus/jelek). Balasan yang dinilai bagus dan dialog baru dari model guru (Kimi) menjadi data latih berikutnya, sehingga model lokal makin nyambung tanpa biaya API.
- **Privasi sebagai nilai jual:** suara dan wajah diproses di perangkat; data tamu terhapus otomatis (default 7 hari); data uji coba hanya berupa kode dan skor.
- **Tim:** [ISI] siapa yang mengurus produk, AI, dan penjualan setelah lomba; rencana jika anggota lulus.
- **Dampak sosial:** membantu mahasiswa dari kampus atau daerah yang tidak punya akses ke pelatih wawancara atau public speaking; bisa berjalan di lab kampus tanpa internet cepat.

---

## 6. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Laptop/PC kampus terlalu lambat untuk AI lokal | Model lebih kecil hasil distilasi; satu server AI untuk satu lab; opsi AI cloud untuk kampus yang mengizinkan |
| Kampus ragu membayar | Pilot gratis dulu, ukur kemajuan mahasiswa, tawarkan harga per prodi yang kecil |
| Pesaing besar (chatbot AI umum) | Fokus pada format ujian sungguhan, rapor terukur, persona penguji lokal berbahasa Indonesia, dan offline/privat |
| Penilaian AI dianggap tidak adil | Rapor menjelaskan dasar setiap skor; skor dipakai untuk latihan, bukan untuk kelulusan |
