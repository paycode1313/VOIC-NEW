# VOIC — Voice & Optics Intelligent Coach

Simulasi ujian lisan dengan AI: **wawancara kerja**, **sidang skripsi**, dan **pitching startup**.
Peserta menjawab dengan suara, AI penguji menanggapi seperti orang sungguhan, lalu peserta
mendapat rapor skor (kelancaran, kosakata, struktur, penyampaian, kontak mata, dan ketenangan wajah).

Semuanya bisa berjalan **100% lokal tanpa internet** (cocok untuk booth pameran):
Laravel untuk web, layanan Python (`ai_service/`) untuk telinga (Whisper), suara (Piper), dan otak AI (Ollama).

---

## Fitur utama

- **Ujian 3 bagian** dan **mode coaching 10 menit**, dengan penguji AI yang bertanya lanjutan berdasarkan jawaban.
- **Suara dua arah**: jawab dengan mikrofon, AI menjawab dengan suara.
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
| Python | 3.10+ | Layanan AI (`ai_service/`) |
| Ollama | terbaru | Otak AI lokal ([ollama.com/download](https://ollama.com/download)) |

Di Windows, cara termudah adalah memakai [Laragon](https://laragon.org) (sudah berisi PHP, Composer, dan Node.js).

---

## Cara cepat (Windows)

Klik dua kali **`jalankan_voic.bat`** di folder proyek. Launcher ini otomatis:

1. memasang paket PHP dan membangun tampilan,
2. memasang paket Python untuk AI,
3. menyalakan Ollama, layanan AI, web Laravel, dan penjadwal hapus-data otomatis,
4. membuka `http://localhost:8000/latihan` di browser.

Pilih mode **Online** (atau jalankan `jalankan_voic.bat online`) untuk mendapat link publik HTTPS lewat Cloudflare Tunnel.

---

## Cara manual (semua sistem operasi)

### 1. Web Laravel

```bash
composer install
npm install
npm run build
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### 2. Layanan AI (Python)

```bash
cd ai_service
pip install -r requirements.txt
python setup_models.py          # unduh model suara & AI (sekali saja, butuh internet)
python setup_models.py --check  # cek kesiapan
```

Laptop dengan GPU NVIDIA: `python setup_models.py --gpu`.
File `requirements-train.txt` hanya untuk melatih ulang AI (butuh GPU), **tidak perlu** untuk menjalankan aplikasi.
Penjelasan lengkap ada di [`ai_service/README.md`](ai_service/README.md).

### 3. Jalankan (3 terminal)

```bash
python ai_service/ai_service.py   # layanan AI di http://127.0.0.1:8001
php artisan serve                 # web di http://localhost:8000
php artisan schedule:work         # penjadwal: hapus data tamu lama setiap jam
```

Cek status AI kapan saja: `php artisan voic:doctor`.

---

## Setelah `git pull` (untuk anggota tim)

```bash
git pull origin main
composer install
npm install
npm run build
php artisan migrate
```

Lalu bandingkan `.env.example` dengan `.env` milikmu dan tambahkan setting baru yang belum ada.
Jika ada perubahan di `ai_service/requirements.txt`, jalankan lagi `pip install -r ai_service/requirements.txt`.

---

## Pengaturan penting di `.env`

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

- `MAIL_MAILER=log` (default): email **tidak terkirim**. Link verifikasi ditulis ke `storage/logs/laravel.log`, cocok untuk uji lokal.
- Agar email benar-benar terkirim, isi pengaturan SMTP. Contoh untuk Gmail ada di `.env.example`
  (gunakan *App Password* Google, bukan kata sandi biasa).

---

## Pengembangan

```bash
php artisan test                               # test Laravel
vendor/bin/pint                                # rapikan kode PHP
cd ai_service && python -m unittest discover -s tests   # test layanan AI
```

Test berjalan otomatis di GitHub Actions setiap ada push ke `main` dan setiap pull request
(lihat `.github/workflows/tests.yml`).

Dokumen desain ada di folder [`docs/`](docs): PRD, arsitektur, skema database, dan panduan deploy.
