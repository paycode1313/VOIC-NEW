# Panduan Uji Coba Pengguna & Bukti untuk Juri

Tiga bukti yang dicari juri, dan perintah yang menghasilkannya:

| Bukti | Perintah | Masuk ke slide |
|---|---|---|
| Pengguna membaik setelah latihan | `php artisan voic:study-report` | Slide 7 (Bukti dari pengguna) |
| AI hasil pelatihan lebih nyambung | `php artisan voic:compare-models` | Slide 6 (Inovasi teknis) |
| Penguji menjawab cukup cepat | `php artisan voic:latency` | Slide 6 dan skenario demo |

---

## 1. Uji coba ke pengguna (20–30 peserta)

### Persiapan
1. Siapkan daftar kode peserta: `P01`, `P02`, … Catat di kertas/spreadsheet tim **siapa memegang kode apa**.
   Di aplikasi hanya kodenya yang tersimpan (tanpa nama, tanpa rekaman).
2. Pilih peserta yang sesuai target: mahasiswa tingkat akhir (sidang/wawancara) atau tim startup (pitching).
3. Jelaskan ke peserta: ikut 3 kali latihan ujian (bukan mode coaching), sebaiknya di hari berbeda atau minimal dengan jeda.

### Pelaksanaan
1. Peserta membuka `/latihan`, memilih mode, lalu memilih **ujian** (bukan coaching).
2. Di bagian **"Ikut uji coba VOIC?"**, isi **Kode peserta** (mis. `P07`). Kode diingat untuk latihan berikutnya di perangkat yang sama.
3. Setelah latihan, rapor menampilkan "latihan ke-1 dari 3". Ulangi sampai 3 kali.
4. Setelah latihan ke-3, rapor menampilkan **kuesioner 6 pertanyaan** (skala 1–5 + komentar). Pastikan peserta mengisinya.

Tips supaya datanya kuat:
- Mode dan topik sebaiknya sama di ketiga latihan, supaya perbandingannya adil.
- Nyalakan kamera di ketiga latihan (kalau tidak, kriteria Delivery hanya perkiraan).
- Jangan bantu peserta menjawab. Biarkan rapor dan saran VOIC yang membantu.
- Minta 2–3 peserta menulis testimoni singkat beserta nama dan jurusan (dengan izin mereka) untuk slide.

### Rekap
```bash
php artisan voic:study-report
```
Hasilnya:
- Tabel rata-rata latihan ke-1 vs ke-3 (skor band, 4 kriteria, kata pengisi per menit, kata per menit).
- Rata-rata kuesioner dan persentase "setuju" (nilai 4–5).
- **Kalimat siap pakai** untuk pitch deck.
- File di `storage/app/study/`: `peserta.csv`, `kuesioner.csv`, `grafik.html` (buka di browser, screenshot untuk slide).

Data uji coba tidak ikut terhapus oleh penghapusan otomatis data tamu (`voic:prune`), karena hanya berisi kode dan skor.

> Catatan kejujuran: peningkatan skor bisa juga karena peserta makin terbiasa dengan formatnya. Kalau juri menanyakan ini,
> jawab terus terang, dan bila sempat, tambahkan kelompok pembanding (peserta yang latihan dengan cara biasa) atau
> tanyakan ke peserta apakah mereka merasa terbantu (kuesioner).

---

## 2. Model lama vs model hasil pelatihan (`voic-coach`)

1. Kumpulkan data latih (dialog Kimi di `ai_service/datasets/kimi/`, lihat README di sana), lalu:
   ```bash
   php artisan voic:export-training-data
   ```
   Perintah ini juga membuat **set uji** `voic_exam_eval.jsonl` yang tidak dipakai saat melatih.
2. Latih di Colab, pasang di Ollama sebagai `voic-coach`.
3. Buat lembar penilaian buta:
   ```bash
   php artisan voic:compare-models --a=voic-qwen --b=voic-coach --limit=30
   ```
   Kedua model menjawab giliran uji yang sama. File `storage/app/compare/penilaian.csv` berisi dua balasan per baris
   dengan **urutan diacak**, jadi penilai tidak tahu mana model lama dan mana model baru.
4. Minta 2–3 orang (sebaiknya bukan pembuat model) mengisi nilai 1–5 untuk *nyambung*, *alami*, dan *tepat*.
   Satu file per penilai: `penilaian_rina.csv`, `penilaian_budi.csv`, dst. **Jangan buka `kunci.json`.**
5. Hitung hasilnya:
   ```bash
   php artisan voic:compare-models --score
   ```
   Keluar rata-rata per model, persentase model baru dinilai lebih baik, waktu menjawab, dan kalimat untuk pitch deck.

Opsional: tambahkan `--judge=openrouter/nvidia/nemotron-3-ultra-550b-a55b:free` agar model besar ikut menilai.
Ingat batas 50 permintaan per hari untuk akun OpenRouter gratis (1 permintaan per contoh).

---

## 3. Kecepatan penguji

- **Otomatis dari ujian sungguhan:** setiap jawaban mencatat waktu suara → teks, sampai suara penguji terdengar, dan total.
  ```bash
  php artisan voic:latency
  ```
- **Uji sekarang di laptop demo** (tanpa peserta):
  ```bash
  php artisan voic:latency --live
  ```

Target: penguji mulai bicara **di bawah 3 detik** untuk 90% giliran.

Hasil pengukuran pertama (11 Nov 2026, laptop Intel i5-8250U, RAM 8 GB, tanpa GPU, model `qwen2.5:3b`):
**median ±25 detik**. CPU ini hanya membaca prompt ±15 token/detik dan menulis ±4–5 token/detik. Pilihan perbaikan
ada di [kesiapan_demo.md](kesiapan_demo.md#1-laptop-demo--kecepatan-ai).
