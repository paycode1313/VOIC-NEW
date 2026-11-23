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
- **Mode ujian**: `GET /api/v1/exam/modes`
- **Ujian 3 bagian** (mesin yang sama dengan web): `POST /api/v1/exam/{mode}/start` (`consent: true`), `POST /api/v1/exam/{id}/answer` (teks hasil pengenalan suara HP, atau file `audio` untuk Whisper lokal), `POST /api/v1/exam/{id}/finish`, `GET /api/v1/exam/{id}`
- **Riwayat**: `GET /api/v1/practice`, `GET /api/v1/practice/{id}`
- **Status AI** (publik): `GET /api/v1/exam/status`: mesin yang sedang menjawab (cloud, AI lokal, atau penyusun offline)
- **Reaksi singkat penguji**: `GET /api/v1/exam/{id}/backchannels`: klip suara "Hmm…", "Oke, oke." untuk diputar saat peserta selesai bicara
- **Tambahan rapor**: `GET /api/v1/exam/{id}/improvements`: versi jawaban yang lebih kuat (ujian) atau catatan coach (coaching), ditulis setelah rapor muncul (panggil lagi beberapa detik kemudian jika masih kosong)
- **Rekaman jawaban**: `GET /api/v1/exam/{id}/recordings/{messageId}`
- **Nilai balasan penguji**: `POST /api/v1/exam/{id}/messages/{messageId}/rate` (`rating: good|bad`)
- **Hapus data sesi** (transkrip + rekaman): `DELETE /api/v1/exam/{id}`

Skor dan rapor dihitung server dari jawaban asli; aplikasi tidak lagi mengirim skor sendiri. Endpoint lama `/practice/start|message|finish` (dialog template) sudah dihapus.

Untuk panduan cara menjalankan masing-masing aplikasi, silakan buka file README di dalam masing-masing subfolder:
- Panduan Flutter: [`mobile/flutter/README.md`](./flutter/README.md)
- Panduan React Native (Expo): [`mobile/react-native/README.md`](./react-native/README.md)

