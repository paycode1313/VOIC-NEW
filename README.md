# VOIC — Voice & Optics Intelligent Coach

Simulasi ujian lisan dengan AI: **wawancara kerja**, **sidang skripsi**, dan **pitching startup**.
Peserta menjawab dengan suara, AI penguji menanggapi seperti orang sungguhan, lalu peserta
mendapat rapor skor (kelancaran, kosakata, struktur, penyampaian, kontak mata, dan ketenangan wajah).

Semuanya bisa berjalan **100% lokal tanpa internet** (cocok untuk booth pameran):
Laravel untuk web, layanan Python (`backend/ai_service/`) untuk telinga (Whisper), suara (Piper), dan otak AI (Ollama).

---

## Struktur folder

```
VOIC-NEW/
├── frontend/   tampilan web: halaman Blade (views/), JavaScript (js/), CSS (css/), Vite & Tailwind
├── backend/    Laravel (app/, routes/, database/, config/, public/, tests/) + layanan AI Python (ai_service/)
├── mobile/     aplikasi Flutter dan React Native (memakai API /api/v1)
└── docs/       dokumen desain, ERD, DFD, dan panduan
```

Laravel membaca halaman dari `frontend/views` (lihat `backend/config/view.php`), dan `npm run build` di `frontend/`
menaruh hasil build di `backend/public/build`. Perintah PHP dan Python dijalankan dari `backend/`, perintah npm dari `frontend/`.

---

## Fitur utama

- **Ujian 3 bagian** dan **mode coaching 10 menit**, dengan penguji AI yang bertanya lanjutan berdasarkan jawaban.
- **Suara dua arah**: jawab dengan mikrofon, AI menjawab dengan suara.
- **Avatar hidup**: foto penguji bergerak di browser (tanpa model tambahan): mulut mengikuti suara AI, berkedip, mengangguk saat peserta bicara, dan memiringkan kepala saat berpikir. Posisi mata/mulut tiap penguji ada di `config/exam.php` (`rig`).
- **Kamera opsional** (diproses di perangkat peserta): kontak mata dan ekspresi wajah.
  Jika peserta terlihat tegang atau tidak menatap kamera, penguji akan menegur dengan ramah.
- **Rapor & sertifikat**: QR untuk membawa pulang rapor, link sertifikat bertanda tangan (tidak bisa ditebak).
- **Mode booth**: panel operator ber-PIN (`/booth`) dan papan skor (`/booth/papan`).
- **Privasi**: persetujuan peserta dan penghapusan otomatis data tamu setelah `VOIC_RETENTION_DAYS` hari.
- **API mobile** (`/api/v1`) untuk aplikasi Flutter / React Native di folder `mobile/`.

---

## Yang perlu dipasang

| Program | Versi | Untuk |
| --- | --- | --- |
| PHP | 8.3+ | Web Laravel |
| Composer | 2.x | Paket PHP |
| Node.js | 22 LTS (minimal 20.19) | Build tampilan (Vite 8) |
| Python | 3.10+ | Layanan AI (`backend/ai_service/`) |
| Ollama | 0.9+ (terbaru) | Otak AI lokal: Qwen3 ([ollama.com/download](https://ollama.com/download)) |

Di Windows, cara termudah adalah memakai [Laragon](https://laragon.org) (sudah berisi PHP, Composer, dan Node.js).

---

## Cara cepat (Windows)

Klik dua kali **`jalankan_voic.bat`** di folder utama proyek (sejajar dengan `frontend/` dan `backend/`). Launcher ini otomatis:

1. memasang paket PHP dan membangun tampilan,
2. memasang paket Python untuk AI,
3. menyalakan Ollama, layanan AI, web Laravel, dan penjadwal hapus-data otomatis,
4. membuka `http://localhost:8000/latihan` di browser.

Pilih mode **Online** (atau jalankan `jalankan_voic.bat online`) untuk mendapat link publik HTTPS lewat Cloudflare Tunnel.

---

## Cara manual (semua sistem operasi)

### 1. Web Laravel

```bash
cd frontend
npm install
npm run build                # hasilnya masuk ke backend/public/build
cd ../backend
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### 2. Layanan AI (Python)

```bash
cd backend/ai_service
pip install -r requirements.txt
python setup_models.py          # unduh model suara & AI (sekali saja, butuh internet)
python setup_models.py --check  # cek kesiapan
```

Laptop dengan GPU NVIDIA: `python setup_models.py --gpu`.
File `requirements-train.txt` hanya untuk melatih ulang AI (butuh GPU), **tidak perlu** untuk menjalankan aplikasi.
Penjelasan lengkap ada di [`backend/ai_service/README.md`](backend/ai_service/README.md).

### 3. Jalankan (3 terminal, semuanya dari folder `backend/`)

```bash
python ai_service/ai_service.py   # layanan AI di http://127.0.0.1:8001
php artisan serve                 # web di http://localhost:8000
php artisan schedule:work         # penjadwal: hapus data tamu lama setiap jam
```

Saat mengubah tampilan, jalankan juga `npm run dev` di folder `frontend/` agar halaman langsung diperbarui.
Cek status AI kapan saja (dari `backend/`): `php artisan voic:doctor`.

---

## Setelah `git pull` (untuk anggota tim)

```bash
git pull origin main
cd frontend && npm install && npm run build
cd ../backend && composer install && php artisan migrate
```

Lalu bandingkan `backend/.env.example` dengan `backend/.env` milikmu dan tambahkan setting baru yang belum ada.
Jika ada perubahan di `backend/ai_service/requirements.txt`, jalankan lagi `pip install -r ai_service/requirements.txt` dari `backend/`.
Jika model AI diperbarui (mis. pindah ke Qwen3), jalankan lagi `python ai_service/setup_models.py` (dari `backend/`) lalu nyalakan ulang layanan AI.

> **Pindah dari struktur lama?** File `.env`, folder `vendor/`, dan model AI sekarang ada di dalam `backend/`, dan `node_modules/`
> di dalam `frontend/`. Jika memakai Laragon, arahkan *document root* ke `backend/public`.

---

## Pengaturan penting di `backend/.env`

| Nama | Keterangan |
| --- | --- |
| `APP_NAME` | Nama aplikasi (default `VOIC`). |
| `APP_LOCALE` | Bahasa pesan sistem (default `id`). |
| `AI_SERVICE_URL` | Alamat layanan AI Python (default `http://127.0.0.1:8001`). |
| `VOIC_PUBLIC_URL` | Alamat yang dibuka HP pengunjung untuk QR rapor, mis. `http://192.168.1.10:8000`. |
| `VOIC_BOOTH_PIN` | PIN panel operator `/booth`. Kosong = panel nonaktif. |
| `VOIC_RETENTION_DAYS` | Berapa hari data tamu & rekaman disimpan (default 7). |
| `MAIL_*` | Pengiriman email verifikasi akun & lupa kata sandi (lihat di bawah). |
| `GROQ_API_KEY` / `OPENAI_API_KEY` | Opsional: AI cadangan online jika Ollama mati. |

### Email verifikasi & lupa kata sandi

Akun baru wajib memverifikasi email sebelum membuka dashboard dan riwayat latihan
(mode tamu di `/latihan` tetap bisa dipakai tanpa akun).

- `MAIL_MAILER=log` (default): email **tidak terkirim**. Link verifikasi ditulis ke `backend/storage/logs/laravel.log`, cocok untuk uji lokal.
- Agar email benar-benar terkirim, isi pengaturan SMTP. Contoh untuk Gmail ada di `backend/.env.example`
  (gunakan *App Password* Google, bukan kata sandi biasa).

---

## Melatih AI dengan Kimi K3 sebagai guru (Google Colab gratis)

Kimi K3 terlalu besar (±1,5 TB) untuk dilatih di Colab atau dijalankan di laptop, jadi dipakai sebagai **guru**:
Kimi K3 (`kimi-k3:cloud` lewat Ollama) menjadi penguji dalam sesi simulasi, lalu **Qwen3-4B (murid)** dilatih meniru
balasannya di Google Colab dan hasilnya dijalankan **100% lokal di laptop, CPU only**.

1. **Laptop, sekali saja:** login Ollama dan ambil modelnya (butuh akun Ollama yang mendukung Kimi K3):
   ```bash
   ollama signin
   ollama pull kimi-k3:cloud
   ollama pull qwen3:4b
   ```
2. **Laptop (dari folder `backend/`):** nyalakan layanan AI (`python ai_service/ai_service.py`), lalu buat sesi simulasi dan ekspor datanya:
   ```bash
   php artisan voic:teacher-generate --sessions=30
   php artisan voic:export-training-data
   ```
   Kimi K3 berpikir dulu sebelum menjawab, jadi satu sesi bisa beberapa menit. Sesi simulasi dimiliki akun
   `teacher@voic.local` dan tidak dihitung sebagai pengunjung booth.
3. **Google Colab (T4 gratis):** buka `backend/ai_service/colab/latih_voic_coach.ipynb`, unggah
   `backend/ai_service/datasets/sft/voic_exam_sft.jsonl`, `voic_exam_eval.jsonl`, dan `backend/ai_service/finetune_lora.py`,
   lalu jalankan semua sel. Hasilnya (file `.gguf` + `Modelfile`) tersimpan di Google Drive.
4. **Laptop:** di folder hasil unduhan, pasang modelnya dan ukur hasilnya:
   ```bash
   ollama create voic-coach -f Modelfile
   python backend/ai_service/eval_dialogue.py --models voic-coach qwen3:4b   # dari folder utama proyek
   ```
   Biarkan `VOIC_AI_MODEL=` kosong di `.env` agar ujian memakai `voic-coach` lokal.

> Kimi K3 juga bisa langsung jadi penguji saat ujian dengan `VOIC_AI_MODEL=kimi-k3:cloud`, tetapi itu **bukan AI lokal**
> (butuh internet, jawaban peserta dikirim ke server Ollama; teks persetujuan otomatis menyesuaikan).

## Pengembangan

```bash
cd backend
php artisan test                               # test Laravel
vendor/bin/pint                                # rapikan kode PHP
cd ai_service && python -m unittest discover -s tests   # test layanan AI
```

Test berjalan otomatis di GitHub Actions setiap ada push ke `main` dan setiap pull request
(lihat `.github/workflows/tests.yml`).

Dokumen desain ada di folder [`docs/`](docs): PRD, arsitektur, skema database, dan panduan deploy.
