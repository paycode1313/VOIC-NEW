# 📱 VOIC Mobile Applications Directory

Direktori ini menampung seluruh implementasi aplikasi klien mobile untuk ekosistem **VOIC (Voice & Optics Intelligent Coach)**. Seluruh aplikasi mobile terhubung secara langsung ke backend REST API Laravel melalui route `/api/v1/...`.

---

## 📂 Struktur Aplikasi Mobile

| Direktori | Platform / Framework | Status | Catatan Penggunaan |
| :--- | :--- | :--- | :--- |
| [`flutter/`](./flutter) | **Flutter (Dart)** | Siap Digunakan | Aplikasi native performa tinggi dengan rendering 60fps untuk simulasi wawancara & audio orb. |
| [`react-native/`](./react-native) | **React Native (Expo)** | Siap Digunakan | Aplikasi cross-platform yang dapat dijalankan secara instan via **Expo Go** di HP tanpa install Android Studio. |

---

## 🔗 Integrasi Backend API

Kedua aplikasi mobile mengonsumsi endpoint Laravel Sanctum yang sama:
- **Autentikasi & Registrasi**: `POST /api/v1/auth/login`, `POST /api/v1/auth/register`
- **Daftar Role AI**: `GET /api/v1/roles`
- **Sesi Simulasi**: `POST /api/v1/practice/start`, `POST /api/v1/practice/{id}/message`, `POST /api/v1/practice/{id}/finish`
- **Detail & Skor Sesi**: `GET /api/v1/practice/{id}`

Untuk panduan cara menjalankan masing-masing aplikasi, silakan buka file README di dalam masing-masing subfolder:
- Panduan Flutter: [`mobile/flutter/README.md`](./flutter/README.md)
- Panduan React Native (Expo): [`mobile/react-native/README.md`](./react-native/README.md)

