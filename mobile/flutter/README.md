# VOIC Mobile App (Flutter Client)

Aplikasi mobile resmi **VOIC (Voice & Optics Intelligent Coach)** yang terhubung secara *Universal API* ke backend Laravel 13.

---

## 📱 Arsitektur & Prinsip Desain

* **Universal Brain (Satu Logika)**: Semua prompt karakter AI (Dosen Penguji, HRD, Investor), kalkulasi skor wajah/suara, dan sertifikat QR code diproses di Laravel Backend (`/api/v1/...`).
* **Mobile-Native UX (9:16 Portrait)**:
  * **Header**: Indikator Sesi, Role AI, dan Timer Real-time.
  * **Center Stage**: Audio Orb dinamis berdenyut saat AI berbicara + Balasan AI.
  * **Floating PiP Camera**: Preview kamera depan untuk *eye-contact tracking* dan status ekspresi wajah (senyum/rileks).
  * **Thumb Zone**: Tombol Mic melingkar besar (*Tap to talk*) dan tombol *Selesaikan Sesi Latihan*.
  * **Rapor Akhir**: Skor Keseluruhan, Skor Wajah, Skor Suara, dan QR Code Sertifikat Verifikasi.

---

## 🛠️ Persyaratan Lingkungan (Prerequisites)

1. **Flutter SDK**: Versi `>= 3.0.0`
2. **Android Studio** atau **VS Code** dengan ekstensi Flutter & Dart.

---

## 🚀 Cara Menjalankan

### 1. Masuk ke Direktori Flutter Mobile
```bash
cd mobile/flutter
```

### 2. Unduh Paket Dependensi
```bash
flutter pub get
```

### 3. Konfigurasi Alamat API Backend
Buka file `lib/constants/api_config.dart`:
* **Jika menggunakan Android Emulator**:
  ```dart
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1';
  ```
* **Jika menggunakan HP Fisik (WiFi yang sama dengan laptop)**:
  ```dart
  static const String baseUrl = 'http://192.168.1.X:8000/api/v1'; // ganti dengan IP laptop Anda
  ```

### 4. Jalankan Aplikasi
```bash
flutter run
```

---

## 📦 Build File APK Android

Untuk membuat installer file `.apk` siap pasang di smartphone:
```bash
flutter build apk --release
```
File APK akan berada di `build/app/outputs/flutter-apk/app-release.apk`.
