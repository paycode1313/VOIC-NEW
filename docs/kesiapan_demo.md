# Checklist Kesiapan Demo Innofest

Tujuannya: demo 3 menit yang **selalu berhasil**, apa pun yang terjadi di lokasi.
Skenario demonya ada di [pitch_deck_innofest.md](pitch_deck_innofest.md#skenario-demo-booth-3-menit).

---

## 1. Laptop demo & kecepatan AI

Ukur dulu di laptop yang akan dibawa:
```bash
php artisan voic:latency --live
```

| Hasil (90% giliran) | Artinya | Tindakan |
|---|---|---|
| < 3 dtk | Terasa seperti ngobrol | Siap demo |
| 3–8 dtk | Masih bisa, terasa menunggu | Isi jeda dengan avatar "berpikir"; latih narasi selama menunggu |
| > 8 dtk | Demo terasa macet | Pilih salah satu solusi di bawah |

Hasil di laptop pengembangan saat ini (i5-8250U, RAM 8 GB, tanpa GPU): **median ±25 detik**, masuk kategori macet.

Pilihan solusi, urut dari yang paling disarankan:
1. **Laptop dengan GPU NVIDIA** (pinjam dari anggota tim/kampus). Jalankan `python ai_service/setup_models.py --gpu`.
   Model 3B di GPU biasanya menjawab dalam 1–2 detik.
2. **Model lebih kecil** (mis. 1,5B) untuk laptop tanpa GPU: kira-kira 2× lebih cepat, kualitas sedikit turun.
   Model hasil distilasi (`voic-coach`) sebaiknya dilatih dari model kecil ini.
3. **AI cloud saat demo.** `GROQ_API_KEY` sudah didukung, tetapi hanya sebagai cadangan otomatis ketika AI lokal tidak menjawab.
   Alternatifnya `VOIC_AI_MODEL=openrouter/...` (penguji langsung lewat OpenRouter; kuota gratis 50 permintaan per hari).
   Sangat cepat, tetapi butuh internet, dan klaim "100% offline" harus diganti menjadi "bisa offline, demo ini memakai mode online".

Pastikan juga ketiga komponen lokal siap:
```bash
php artisan voic:doctor
```
- **Whisper (suara → teks)** dan **Piper (suara penguji)** harus ✓. Jika belum: `cd ai_service && python setup_models.py`
  (butuh internet sekali untuk mengunduh model).
- Tanpa Piper, suara penguji jatuh ke cadangan online (edge-tts), yang saat ini **ditolak server (HTTP 403)**, lalu ke suara
  bawaan browser. Tanpa Whisper, pengenalan suara memakai layanan online browser. Keduanya butuh internet.
- Ubah `AI_SERVICE_TIMEOUT=90` di `.env` untuk laptop tanpa GPU (saran dari `voic:doctor`).

---

## 2. H-7

- [ ] `voic:latency --live` di laptop demo memenuhi target (lihat bagian 1)
- [ ] `voic:doctor` semua ✓ tanpa internet (matikan Wi-Fi, lalu cek lagi)
- [ ] Data uji coba pengguna terkumpul, `voic:study-report` sudah dijalankan, angka masuk pitch deck
- [ ] Rekam **video demo cadangan 2 menit** (layar + suara): satu ujian singkat sampai rapor dan QR
- [ ] Siapkan 2 skenario topik yang sudah terbukti lancar (mis. "Data Analyst di e-commerce", "Aplikasi kasir untuk warung")
- [ ] Latih demo 3 menit minimal 5 kali dengan stopwatch, termasuk saat AI sengaja dimatikan

## 3. H-1

- [ ] `git pull`, `composer install`, `npm run build`, `php artisan migrate`
- [ ] `jalankan_voic.bat` berjalan sampai halaman `/latihan` terbuka
- [ ] Atur `VOIC_BOOTH_PIN` dan `VOIC_PUBLIC_URL` (IP laptop di jaringan booth) di `.env`
- [ ] Coba QR rapor dari HP di jaringan yang sama
- [ ] Isi daya laptop, bawa charger dan stop kontak

## 4. Perlengkapan

- [ ] **Headset dengan mikrofon** (minimal 2). Booth pameran bising; mikrofon laptop akan menangkap suara sekitar.
- [ ] Speaker kecil (agar penonton ikut mendengar penguji) atau splitter audio
- [ ] Layar kedua untuk papan skor `/booth/papan`
- [ ] HP cadangan sebagai hotspot (jika memakai solusi AI cloud)
- [ ] Flashdisk berisi video demo cadangan dan PDF pitch deck

## 5. Hari H: 10 menit sebelum juri datang

1. Jalankan `jalankan_voic.bat`, lalu `php artisan voic:doctor`.
2. Lakukan **satu latihan pemanasan** sampai selesai. Model AI dimuat ke memori dan giliran pertama juri tidak lambat.
3. Buka `/booth` (masukkan PIN) dan `/booth/papan` di layar kedua.
4. Pasang headset, cek volume.

## 6. Jika terjadi masalah saat demo

| Masalah | Tindakan cepat |
|---|---|
| Ruangan terlalu bising | Pakai headset; atau tekan **"⌨️ Ketik saja"** di ruang ujian dan juri menjawab dengan mengetik |
| AI lambat menjawab | Ceritakan yang terjadi selama menunggu: "AI-nya sedang berpikir di laptop ini, tanpa internet" |
| AI mati / error | Aplikasi otomatis memakai penguji cadangan offline, demo tetap berjalan. Setelahnya cek `voic:doctor` |
| Suara penguji tidak keluar | Cek volume/headset; teks balasan tetap muncul di layar |
| Laptop hang | Putar video demo cadangan sambil restart |
| Juri minta coba sendiri | Pindai QR di papan skor dengan HP juri |
