# Data latih manual dari Kimi (gratis, lewat kimi.com)

Kimi terlalu besar untuk dilatih di Colab, jadi Kimi dipakai sebagai **guru yang menulis contoh percakapan**.
Model kecil (Qwen) dilatih meniru contoh-contoh itu di Colab, lalu dimasukkan ke Ollama sebagai `voic-coach`.

## Langkah (paling mudah: pakai paket prompt)

1. Buka **`PROMPT_KIMI.md`** di folder ini: 30 prompt siap salin, masing-masing menghasilkan 2 dialog dengan
   topik, tipe peserta, dan situasi yang sudah dipilihkan (total ±60 dialog, ±700 contoh latih).
2. Untuk setiap prompt: buka https://www.kimi.com (gratis), **chat baru**, tempel prompt, kirim.
3. Salin blok JSON jawaban Kimi, simpan di folder ini dengan nama yang tertulis di judul prompt (misalnya `sidang_05.json`).
4. Cek kualitasnya kapan saja:
   ```
   python ai_service/datasets/kimi/cek_kimi.py
   ```
   File bertanda ✗ (RUSAK) dibuat ulang di Kimi. Balasan yang gagal mutu otomatis dibuang saat membuat notebook.
5. Kalau sudah terkumpul:
   ```
   php artisan voic:export-training-data
   python ai_service/colab/buat_notebook_otomatis.py
   ```
   lalu di Colab: File → Upload notebook → `ai_service/datasets/sft/VOIC_latih_otomatis.ipynb` → Runtime → Run all.

Paket prompt dibuat oleh `buat_prompt_kimi.py`; ubah daftar topik di sana lalu jalankan ulang kalau ingin topik lain.
Prompt manual di bawah tetap bisa dipakai untuk topik di luar paket.

Mode yang tersedia dan bagian-bagiannya:

| Mode | Penguji | Bagian 1 | Bagian 2 | Bagian 3 |
|---|---|---|---|---|
| `wawancara` | Kak Nadia (HR Recruiter) | Perkenalan | Cerita Pengalaman | Diskusi Mendalam |
| `sidang` | Prof. Hendra (dosen penguji) | Pembukaan | Presentasi Inti | Tanya Jawab Penguji |
| `pitching` | Pak William (investor) | Founder & Masalah | Elevator Pitch | Due Diligence |

Variasikan tipe peserta: lancar dan percaya diri · kurang detail · gugup dan pendek-pendek ·
sering bilang "tidak tahu" atau minta pertanyaan diulang · bertele-tele · sangat singkat · menjawab melenceng dari pertanyaan.

---

## PROMPT (salin mulai dari baris di bawah ini)

Kamu adalah penulis data latih untuk AI penguji lisan berbahasa Indonesia. Tulis 1 dialog simulasi lengkap.

Mode: <MODE>   (wawancara = wawancara kerja oleh Kak Nadia, HR Recruiter · sidang = sidang skripsi oleh Prof. Hendra · pitching = pitching startup ke investor Pak William)
Topik: <TOPIK>   (contoh: "Data Analyst di e-commerce", "Deteksi cacat kain dengan YOLOv8", "Aplikasi kasir untuk warung kelontong")
Tipe peserta: <TIPE PESERTA>

Aturan isi:
- Ada 3 bagian (part 1, 2, 3), total 10 sampai 14 giliran. Part 1 pembuka/perkenalan, part 2 inti, part 3 pendalaman/tanya jawab kritis.
- Setiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), lalu penguji menanggapi ("reply").
- "reply" WAJIB nyambung dengan isi jawaban peserta: sebut detail spesifik yang dikatakan peserta (angka, nama alat, contoh), jangan pujian umum seperti "bagus sekali".
- Bahasa santai tapi sopan, seperti orang Indonesia sungguhan bicara. Kalimat pendek, enak diucapkan (ini akan diubah jadi suara). Tanpa emoji, tanpa markdown, tanpa daftar bernomor.
- "directive" diisi salah satu:
  - "react"     → reply hanya tanggapan singkat 1-2 kalimat atas jawaban, TANPA pertanyaan. Giliran berikutnya memakai pertanyaan baru.
  - "follow_up" → reply berisi tanggapan singkat + SATU pertanyaan lanjutan yang menggali jawaban peserta. Giliran berikutnya "question"-nya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify"   → dipakai kalau peserta bingung, bilang tidak tahu, atau minta diulang. reply menjelaskan ulang pertanyaan dengan kata yang lebih sederhana atau memberi contoh/petunjuk. Giliran berikutnya "question"-nya HARUS sama persis dengan pertanyaan yang dijelaskan ulang itu.
- Campur ketiganya secara wajar (kira-kira 50% react, 35% follow_up, 15% clarify). Jawaban peserta harus sesuai tipe peserta di atas, termasuk jawaban yang lemah atau melenceng; penguji menanggapinya dengan tetap ramah dan mengarahkan.

Format keluaran: HANYA satu blok JSON valid, tanpa penjelasan apa pun sebelum atau sesudahnya, persis seperti contoh berikut:

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {
        "part": 1,
        "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.",
        "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.",
        "directive": "react",
        "reply": "Wah, dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan banget buat posisi ini."
      },
      {
        "part": 1,
        "question": "Apa yang bikin kamu tertarik melamar posisi Frontend Developer di startup fintech ini?",
        "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.",
        "directive": "follow_up",
        "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"
      },
      {
        "part": 1,
        "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?",
        "answer": "Eee, maksudnya gimana ya Kak?",
        "directive": "clarify",
        "reply": "Maksud saya begini, coba ingat aplikasi dompet digital atau m-banking yang kamu pakai. Ada nggak halaman yang bikin kamu kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"
      }
    ]
  }
]
