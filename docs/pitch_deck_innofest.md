# Pitch Deck VOIC — Innofest 2026

> **Produk:** VOIC (Voice & Optics Intelligent Coach)
> **Satu kalimat:** Penguji AI yang bisa diajak bicara, untuk latihan wawancara kerja, sidang skripsi, dan pitching. Berjalan 100% di laptop, tanpa internet.
> **Kategori:** ICT / Teknologi Pendidikan

Bagian bertanda **[ISI]** wajib diisi tim dengan data asli sebelum lomba. Jangan presentasikan angka yang belum ada sumbernya.

---

## Slide 1 — Judul

**VOIC: Latihan bicara di depan penguji, sebelum bertemu penguji sungguhan.**
Logo, nama tim, kampus, kategori lomba.

---

## Slide 2 — Masalah

- Wawancara kerja, sidang skripsi, dan pitching dinilai dari **cara bicara**, bukan hanya isi.
- Latihan yang tersedia biasanya **membaca sendiri** atau **minta tolong teman**. Teman jarang bertanya lanjutan yang sulit, dan masukannya tidak terukur.
- Coaching profesional mahal dan tidak bisa diulang kapan saja.
- **[ISI]** Satu data pendukung yang bersumber jelas, misalnya hasil survei tim ke N mahasiswa ("X% merasa paling cemas saat sidang/wawancara, Y% belum pernah latihan dengan simulasi"). Tulis sumbernya di slide.

---

## Slide 3 — Solusi: VOIC

Peserta **berbicara langsung** dengan penguji AI yang menanggapi jawabannya seperti orang sungguhan.

| Mode | Penguji AI | Yang dilatih |
|---|---|---|
| Wawancara kerja | Kak Nadia, Senior HR Recruiter | Perkenalan, cerita pengalaman (STAR), pertanyaan sulit |
| Sidang skripsi | Prof. Hendra, Dosen Penguji Utama | Pemaparan penelitian, tanya jawab metodologi |
| Pitching startup | Pak William, Partner Venture Capital | Masalah dan solusi, elevator pitch, due diligence |

Dua jenis sesi:
- **Ujian (3 bagian, ±12 menit):** pembuka → bicara panjang 2 menit setelah 1 menit persiapan → pendalaman. Diakhiri rapor skor.
- **Coaching (10 menit):** ngobrol santai tanpa nilai. Penguji memberi saran kata dan cara menjawab.

Tersedia dalam **bahasa Indonesia dan Inggris**. Bisa dicoba tanpa membuat akun.

---

## Slide 4 — Kenapa terasa seperti penguji sungguhan

- **Bertanya lanjutan dari jawabanmu.** Penguji menyebut detail yang kamu ucapkan (angka, nama alat, contoh), lalu menggali lebih dalam, bukan membaca daftar pertanyaan.
- **Menjelaskan ulang kalau kamu bingung** atau bilang "tidak tahu", seperti penguji yang baik.
- **Suara dua arah:** jawab dengan mikrofon, penguji menjawab dengan suara. Jawaban AI di-*stream*, jadi suara mulai terdengar sebelum seluruh kalimat selesai dibuat.
- **Avatar hidup:** mulut penguji mengikuti suara, berkedip, mengangguk saat kamu bicara, dan memiringkan kepala saat berpikir.
- **Membaca bahasa tubuh (kamera opsional):** kontak mata dan ketegangan wajah. Kalau kamu terlihat tegang atau tidak menatap kamera, penguji menegur dengan ramah.

---

## Slide 5 — Rapor yang terukur

Setiap jawaban dianalisis, lalu digabung menjadi **4 kriteria dengan skala band 0–9**:

| Kriteria | Yang diukur |
|---|---|
| Kelancaran & Alur | Tempo bicara, kata pengisi ("eh", "anu", "apa ya"), keterhubungan ide |
| Kosakata & Istilah | Variasi kata dan istilah sesuai bidang |
| Isi & Struktur | Relevansi, kerangka jawaban, contoh, dan bukti |
| Delivery & Kepercayaan Diri | Kontak mata, ketenangan wajah, tempo stabil |

- Jawaban panjang di Bagian 2 **dihitung dua kali**, karena paling mencerminkan kemampuan bicara.
- Tanpa kamera, Delivery hanya diperkirakan dari tempo dan **tidak pernah diklaim sebagai kekuatan**. Rapornya jujur.
- Rapor berisi kekuatan, saran perbaikan konkret, rekaman jawaban, **QR untuk dibawa pulang**, dan **sertifikat dengan link yang tidak bisa ditebak**.

---

## Slide 6 — Inovasi teknis: AI lokal yang dilatih sendiri

```
Mikrofon ─► faster-whisper (suara → teks) ─► Qwen via Ollama (penguji AI) ─► Piper (teks → suara) ─► Speaker
Kamera ──► MediaPipe Face Landmarker di browser (kontak mata, ketegangan wajah)
```

- **Semua berjalan di laptop.** Tidak ada suara atau video yang dikirim ke internet. Analisis kamera bahkan tidak meninggalkan browser.
- **Metode guru–murid (distilasi):** model besar (Kimi) menulis contoh percakapan ujian yang ideal, lalu model kecil Qwen dilatih meniru di Google Colab dan dipasang ke Ollama sebagai `voic-coach`. Hasilnya penguji yang nyambung, tetapi cukup ringan untuk laptop biasa.
- **[ISI]** Hasil perbandingan dari `php artisan voic:compare-models --score`: model sebelum vs sesudah dilatih, dinilai pada set percakapan uji yang sama (misalnya "% balasan yang menanggapi detail jawaban peserta: X% → Y%").
- **[ISI]** Dari `php artisan voic:latency`: jeda rata-rata dari peserta selesai bicara sampai penguji mulai menjawab, diukur di laptop demo (target < 3 detik).

---

## Slide 7 — Bukti dari pengguna

**[ISI] Wajib ada, ini yang paling dicari juri.** Cara mengumpulkan datanya: [uji_coba_pengguna.md](uji_coba_pengguna.md). Angka di bawah keluar dari `php artisan voic:study-report`. Format yang disarankan:

| Data | Hasil |
|---|---|
| Jumlah peserta uji | [ISI] orang (mahasiswa tingkat akhir / pencari kerja / tim startup) |
| Skor rata-rata latihan ke-1 → ke-3 | [ISI] → [ISI] (band 0–9) |
| Kata pengisi per menit, latihan ke-1 → ke-3 | [ISI] → [ISI] |
| "Lebih percaya diri setelah latihan" (skala 1–5) | [ISI] |
| Kutipan pengguna | "[ISI]" — nama, jurusan |

---

## Slide 8 — Dibandingkan alternatif

| | Latihan sendiri / dengan teman | Chatbot AI umum | Coaching profesional | **VOIC** |
|---|---|---|---|---|
| Bertanya lanjutan sesuai jawaban | Jarang | Ya, lewat teks | Ya | **Ya, dengan suara** |
| Format ujian sungguhan (3 bagian, waktu) | Tidak | Tidak | Kadang | **Ya** |
| Skor terukur + saran konkret | Tidak | Tidak terstruktur | Subjektif | **Ya, 4 kriteria** |
| Membaca kontak mata & ketegangan | Tidak | Tidak | Ya | **Ya (opsional)** |
| Privasi: data tetap di perangkat | Ya | Tidak | Ya | **Ya** |
| Biaya per latihan | Gratis | Gratis / berlangganan | Mahal | **Gratis setelah terpasang** |

---

## Slide 9 — Model bisnis & keberlanjutan

Versi lengkap (harga, biaya, rencana 12 bulan, risiko): [model_bisnis.md](model_bisnis.md).

**[ISI / sesuaikan dengan rencana tim.]** Usulan awal:
- **Kampus & career center (B2B):** lisensi per kampus untuk latihan sidang dan persiapan kerja, dipasang di lab atau server kampus (cocok karena bisa offline).
- **Inkubator & program startup:** latihan pitching sebelum demo day.
- **Pengguna umum (freemium):** mode latihan gratis via web dan aplikasi mobile, fitur lanjutan berbayar.
- **Biaya operasional rendah:** AI berjalan lokal, jadi tidak ada biaya API per percakapan.

---

## Slide 10 — Peta jalan

| Status | Rencana |
|---|---|
| ✅ Selesai | 3 mode ujian + coaching, bahasa Indonesia & Inggris, suara dua arah, avatar hidup, analisis kamera, rapor, QR & sertifikat, mode booth, API mobile |
| 🔄 Berjalan | Pengumpulan data guru–murid dan pelatihan `voic-coach`; aplikasi mobile (React Native / Flutter) |
| ⏭️ Berikutnya | **[ISI]** Uji coba di kampus mitra, dashboard dosen pembimbing untuk memantau latihan mahasiswa |

---

## Slide 11 — Penutup

**"VOIC membuat latihan bicara bisa diulang kapan saja, dinilai dengan jelas, dan tetap privat."**
Ajak juri mencoba langsung di booth.

---

# Naskah elevator pitch (60 detik)

> "Bapak/Ibu juri, wawancara kerja, sidang skripsi, dan pitching sering ditentukan bukan oleh isi materi, tetapi oleh cara kita menjawab saat ditanya. Sayangnya, kebanyakan dari kita hanya bisa latihan sendiri atau dengan teman, yang jarang bertanya lanjutan dan tidak bisa memberi nilai yang terukur.
>
> Kami membuat **VOIC**: penguji AI yang bisa diajak bicara. Pilih wawancara, sidang, atau pitching, lalu jawab dengan suara. Penguji AI menanggapi isi jawabanmu, bertanya lanjutan, dan menegur kalau kamu terlihat tegang. Di akhir, kamu mendapat rapor dengan empat kriteria dan saran konkret.
>
> Semuanya berjalan di laptop tanpa internet, dengan AI yang kami latih sendiri, jadi suara dan wajah pengguna tidak pernah keluar dari perangkat. **[ISI: satu kalimat hasil uji pengguna, misal "Dari 25 mahasiswa yang mencoba, skor rata-rata naik dari 5,2 ke 6,4 setelah tiga kali latihan."]** Silakan coba langsung di booth kami."

---

# Skenario demo booth (3 menit)

**Persiapan sebelum juri datang:** jalankan `jalankan_voic.bat`, cek `php artisan voic:doctor`, buka `/booth` (masukkan PIN) di laptop operator dan `/booth/papan` di layar kedua. Pakai headset bermikrofon supaya suara pameran tidak ikut terdengar.

**Menit 1: Pilih mode (30 detik)**
1. Buka `/latihan`, minta juri memilih: wawancara, sidang, atau pitching.
2. Isi topik singkat (misalnya posisi yang dilamar), nyalakan kamera, mulai.

**Menit 1–2,5: Juri menjawab langsung (90 detik)**
1. Penguji AI menyapa dengan suara. Juri menjawab 2–3 pertanyaan.
2. Tunjukkan bahwa pertanyaan berikutnya **menyebut detail dari jawaban juri**. Ini momen "wow"-nya.
3. Kalau juri diam atau bilang "tidak tahu", tunjukkan penguji menjelaskan ulang pertanyaannya.
4. Sambil berjalan, tunjuk avatar yang mengangguk dan berkedip, serta indikator kamera.

**Menit 2,5–3: Rapor (30 detik)**
1. Klik selesai, lalu tunjukkan rapor 4 kriteria dan saran konkret.
2. Juri memindai **QR** untuk membawa pulang rapornya. Tunjukkan peringkatnya di **papan skor** booth (`/booth/papan`), yang juga punya QR agar pengunjung lain bisa mencoba dari HP.
3. Tutup dengan: "Semua ini tadi berjalan di laptop ini, tanpa internet."

**Rencana cadangan**
- AI lambat atau mati: `php artisan voic:doctor` menunjukkan bagian yang bermasalah; aplikasi tetap menjawab dengan penguji cadangan offline.
- Ruangan terlalu bising: pakai headset, atau tunjukkan video demo cadangan **[ISI: siapkan video 2 menit]**.

---

# Antisipasi pertanyaan juri

**Q: Apa bedanya dengan ChatGPT mode suara?**
> ChatGPT adalah asisten umum. VOIC meniru format ujian sungguhan: 3 bagian dengan waktu persiapan dan bicara, persona penguji sesuai konteks, analisis kamera, dan rapor dengan kriteria tetap. Selain itu, VOIC berjalan offline, jadi suara dan wajah pengguna tidak dikirim ke server mana pun.

**Q: Bagaimana skor dihitung? Apakah objektif?**
> Setiap jawaban diukur dari transkrip dan kamera: tempo bicara, kata pengisi, variasi kosakata dan istilah bidang, kerangka jawaban dan contoh, serta kontak mata dan ketegangan wajah. Hasilnya dirata-rata menjadi 4 kriteria dengan skala band 0–9. Jawaban panjang di Bagian 2 dihitung dua kali. Tanpa kamera, aspek penyampaian hanya diperkirakan dan tidak diklaim sebagai kekuatan.

**Q: AI-nya buatan sendiri?**
> Kami tidak membuat model dari nol. Kami memakai model terbuka Qwen, lalu melatihnya ulang dengan metode guru–murid: model besar (Kimi) menulis contoh percakapan ujian yang ideal, dan Qwen dilatih meniru di Google Colab. **[ISI: tunjukkan hasil perbandingan sebelum dan sesudah pelatihan.]**

**Q: Kenapa harus offline?**
> Ada tiga alasan: privasi (suara dan wajah tidak keluar dari perangkat), biaya (tidak ada tagihan API per percakapan), dan keandalan (tetap berjalan di ruang ujian, pameran, atau kampus dengan internet lemah).

**Q: Bagaimana kalau AI salah atau melantur?**
> Alur ujian dikendalikan aplikasi, bukan oleh AI: bagian, waktu, dan pertanyaan inti sudah ditentukan. AI hanya menanggapi dan bertanya lanjutan. Kalau AI gagal menjawab, ada penguji cadangan offline. Peserta juga bisa menilai setiap balasan (bagus/jelek), dan penilaian itu dipakai untuk memperbaiki data latih.

**Q: Apa yang terjadi dengan data pengguna?**
> Pengguna harus menyetujui sebelum mulai. Data tamu, termasuk rekaman, dihapus otomatis setelah beberapa hari (default 7 hari, bisa diatur).

**Q: Siapa yang akan membayar?**
> **[ISI: sesuaikan dengan slide model bisnis.]**
