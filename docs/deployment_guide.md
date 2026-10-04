# 🚀 Panduan Deployment & Demo Booth - VOIC (Innofest 2026)

Panduan ini berisi instruksi teknis untuk menjalankan aplikasi **VOIC** secara optimal saat sesi demonstrasi di hadapan juri dan pengunjung pameran Innofest.

---

## 1. Menjalankan Demo di Komputer Lokal (Laptop Booth)

Cara paling stabil dan direkomendasikan saat pameran adalah menjalankan aplikasi langsung di localhost:

1. **Buka Laragon** dan pastikan service **Apache** dan **MySQL** aktif.
2. Buka terminal di folder project `c:\laragon\www\VOIC-NEW`:
   ```bash
   php artisan serve --port=8000
   ```
3. Buka browser (Chrome / Edge / Firefox) dan akses:
   ```
   http://localhost:8000
   ```
4. **Login Akun Demo Cepat:**
   - Buka halaman `/login`
   - Klik tombol **"Isi Otomatis"** di bawah formulir login
   - Email: `test@example.com`
   - Password: `password`

---

## 2. Berbagi Akses ke Jaringan Wi-Fi Booth (Akses dari HP Pengunjung)

Jika ingin membiarkan juri atau pengunjung mencoba dari smartphone/laptop mereka sendiri melalui Wi-Fi yang sama:

1. Cari IP lokal laptop Anda (buka PowerShell, ketik `ipconfig`, misal: `192.168.1.50`).
2. Jalankan server Laravel dengan binding host `0.0.0.0`:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. **PENTING Terkait Izin Kamera (Secure Context Browser):**
   > ⚠️ **Perhatian Teknis:** Browser modern (Chrome/Edge/Safari) **hanya mengizinkan akses Webcam/Mic** pada konteks aman (`https://` atau `http://localhost`).  
   > Jika pengunjung membuka via IP mentah (`http://192.168.1.50:8000`), browser ponsel akan memblokir kamera demi privasi.

   **Solusi Cepat & Gratis untuk Booth (Tunneling HTTPS):**
   Gunakan **Cloudflare Tunnel** atau **Ngrok** untuk mendapatkan URL HTTPS instan:
   
   - Menggunakan Cloudflare Tunnel (tanpa registrasi):
     ```bash
     npx cloudflared tunnel --url http://localhost:8000
     ```
   - Menggunakan Ngrok:
     ```bash
     ngrok http 8000
     ```
   Bagikan link HTTPS yang dihasilkan (misal `https://voic-demo.trycloudflare.com`) atau jadikan QR Code di meja booth agar pengunjung cukup scan HP!

---

## 3. Perintah Optimasi & Reset Database Cepat

Jika sewaktu-waktu database ingin di-reset ke kondisi awal yang bersih dengan 6 riwayat sesi demo yang rapi:

```bash
# Reset database dan seed data demo bawaan
php artisan migrate:fresh --seed

# Kompilasi aset frontend terbaru
npm run build

# Bersihkan cache Laravel
php artisan optimize:clear
```

---

## 4. Checklist Kesiapan Stand Booth Innofest

- [ ] Laptop terhubung ke charger (jangan gunakan baterai hemat daya agar FPS kamera lancar).
- [ ] Pengaturan pencahayaan ruangan booth cukup menerangi wajah presenter.
- [ ] Mikrofon laptop/headset sudah diuji kejelasannya.
- [ ] Tab browser utama telah dibuka pada halaman `http://localhost:8000/dashboard`.
- [ ] Lembar cetak laporan contoh sudah disiapkan di meja booth sebagai bukti fisik hasil analisis AI VOIC.
