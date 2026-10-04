# Rules & Collaboration Guidelines - VOIC

## 1. Git Branching Strategy
- `main`: Berisi kode stabil yang siap dipamerkan di Innofest. Jangan push langsung ke sini!
- `dev`: Tempat penggabungan fitur sebelum masuk ke `main`.
- `feature/nama-fitur`: Cabang per anggota tim (contoh: `feature/auth-setup`, `feature/practice-camera`).

## 2. Aturan Database Tim
- **DILARANG** saling kirim file `.sql` via WhatsApp/Drive.
- Setiap ada perubahan struktur tabel, wajib buat file migration baru: 
  `php artisan make:migration deskripsi_perubahan`
- Anggota tim lain cukup menjalankan `php artisan migrate` setelah pull kode terbaru.

## 3. Standar Coding Laravel
- Gunakan bahasa Inggris untuk penamaan variabel, kolom database, dan controller.
- Terapkan Controller yang bersih (hindari logic yang terlalu menumpuk di file Route).