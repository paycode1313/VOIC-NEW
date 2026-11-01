# VOIC Mobile App (React Native Expo)

Aplikasi mobile resmi **VOIC (Voice & Optics Intelligent Coach)** berbasis **React Native (Expo)** yang terhubung secara *Universal API* ke backend Laravel.

---

## 📱 Keunggulan Versi Expo

* **Langsung Jalan di HP Asli**: Tidak perlu install Android Studio atau SDK bergiga-giga di laptop. Cukup download aplikasi gratis **Expo Go** di Play Store / App Store pada smartphone Anda, lalu scan QR code yang muncul di terminal.
* **Bisa Dibuka di Web Laptop**: Bisa langsung dipreview di browser laptop dengan mengetik `npm run web` (atau tekan huruf `w` saat server aktif).
* **Universal Backend (Satu Logika)**: Terhubung langsung ke REST API Laravel (`/api/v1/...`).

---

## 🚀 Cara Menjalankan

### 1. Pastikan Server Laravel Aktif
Buka terminal baru di root project (`c:\laragon\www\VOIC-NEW`):
```bash
php artisan serve
```

### 2. Jalankan Aplikasi Mobile (Expo)
Buka terminal dan masuk ke folder `mobile/react-native`:
```bash
cd mobile/react-native
npm start
```

Setelah perintah di atas dijalankan:
* Akan muncul **QR Code besar** di terminal Anda.
* **Buka di HP Android:** Buka aplikasi **Expo Go** di HP Anda, lalu pilih **Scan QR Code**. Aplikasi VOIC akan langsung terbuka di layar HP Anda!
* **Buka di Web Laptop:** Tekan tombol `w` di keyboard terminal Anda untuk membukanya di browser laptop.

---

## 🌐 Konfigurasi IP Backend untuk HP Fisik

Jika Anda menjalankan aplikasi di **HP fisik melalui jaringan WiFi yang sama dengan laptop**:
Buka file `src/constants/api.js`, dan ubah `API_BASE_URL` sesuai alamat IP laptop Anda:
```javascript
export const API_BASE_URL = 'http://192.168.1.X:8000/api/v1'; // ganti 192.168.1.X dengan IP laptop Anda
```
*(Untuk mengetahui IP laptop Anda, ketik `ipconfig` di command prompt/PowerShell).*
