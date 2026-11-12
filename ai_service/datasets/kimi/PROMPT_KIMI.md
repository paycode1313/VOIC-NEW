# Paket prompt Kimi — minggu 1 (30 prompt, ±60 dialog, ±700 contoh latih)

**Cara pakai (ulangi untuk setiap prompt):**
1. Buka https://www.kimi.com, mulai **chat baru** untuk setiap prompt (supaya Kimi tidak meniru dialog sebelumnya).
2. Klik ikon salin di kanan atas kotak prompt, tempel ke Kimi, kirim.
3. Salin blok JSON jawaban Kimi, simpan di folder ini (`ai_service/datasets/kimi/`) dengan nama file yang tertulis di judul prompt.
4. Setiap selesai beberapa file, cek kualitasnya:

```bash
python ai_service/datasets/kimi/cek_kimi.py
```

5. Kalau semua sudah terkumpul: `php artisan voic:export-training-data`, lalu `python ai_service/colab/buat_notebook_otomatis.py`, lalu Run all di Colab.

Kalau jawaban Kimi terpotong di tengah (JSON tidak lengkap), ketik di chat yang sama: `Lanjutkan JSON-nya persis dari titik terakhir.` lalu gabungkan.

## Prompt 01 — wawancara → simpan sebagai `wawancara_01.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Software Engineer backend di perusahaan logistik", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri; dan peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian.
- Dialog 2: topik "Customer Service di bank digital", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan).

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis SELURUH dialog (question, answer, reply) dalam bahasa Inggris, dan isi "language": "en". Topik boleh diterjemahkan.

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 02 — sidang → simpan sebagai `sidang_02.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Sistem pakar diagnosa penyakit kucing metode forward chaining", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding; dan peserta menyebut metode tanpa alasan memilihnya.
- Dialog 2: topik "Pengaruh gaji dan lingkungan kerja terhadap kinerja karyawan pabrik", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: peserta memakai klaim terlalu umum seperti "pasti" atau "selalu"; dan peserta bingung ditanya keterbatasan penelitian.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 03 — pitching → simpan sebagai `pitching_03.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "KosHub, aplikasi cari dan bayar kos untuk mahasiswa", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: peserta bilang "tidak ada pesaing"; dan peserta bilang pertumbuhannya "pesat" tanpa angka.
- Dialog 2: topik "PanenLink, menghubungkan petani sayur langsung ke restoran", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta menyebut dana yang diminta tanpa rincian penggunaannya; dan angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya.

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 04 — wawancara → simpan sebagai `wawancara_04.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Akuntan junior di KAP", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian; dan cerita pengalaman berhenti tanpa menyebut hasil akhirnya.
- Dialog 2: topik "Social Media Specialist di brand skincare", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta tidak paham pertanyaan dan minta diulang.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 05 — sidang → simpan sebagai `sidang_05.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Analisis sentimen ulasan aplikasi ojek online dengan Naive Bayes", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta menyebut metode tanpa alasan memilihnya; dan peserta tidak menyebut jumlah data/sampel/responden.
- Dialog 2: topik "Rancang bangun sistem informasi posyandu berbasis web", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta bingung ditanya keterbatasan penelitian; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 06 — pitching → simpan sebagai `pitching_06.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "BengkelGo, servis motor panggilan", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta bilang pertumbuhannya "pesat" tanpa angka; dan peserta bilang pasarnya "semua orang" atau "semua UMKM".
- Dialog 2: topik "LansiaCare, pendamping lansia berbasis aplikasi", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya; dan peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan).

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 07 — wawancara → simpan sebagai `wawancara_07.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Guru SD di sekolah swasta", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: cerita pengalaman berhenti tanpa menyebut hasil akhirnya; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.
- Dialog 2: topik "Perawat di rumah sakit swasta", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta tidak paham pertanyaan dan minta diulang; dan peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 08 — sidang → simpan sebagai `sidang_08.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Pengaruh literasi keuangan terhadap perilaku menabung mahasiswa", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta tidak menyebut jumlah data/sampel/responden; dan peserta memakai klaim terlalu umum seperti "pasti" atau "selalu".
- Dialog 2: topik "Prediksi harga cabai dengan LSTM", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 09 — pitching → simpan sebagai `pitching_09.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "SisaMakan, jual makanan sisa hari ini dari restoran dengan harga murah", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: peserta bilang pasarnya "semua orang" atau "semua UMKM"; dan peserta menyebut dana yang diminta tanpa rincian penggunaannya.
- Dialog 2: topik "TutorDekat, les privat SD dari mahasiswa sekitar", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta bilang "tidak ada pesaing".

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 10 — wawancara → simpan sebagai `wawancara_10.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "HR Generalist di pabrik garmen", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan).
- Dialog 2: topik "Sales Executive alat berat", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri; dan peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 11 — sidang → simpan sebagai `sidang_11.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Efektivitas pembelajaran berbasis proyek terhadap hasil belajar IPA siswa SMP", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta memakai klaim terlalu umum seperti "pasti" atau "selalu"; dan peserta bingung ditanya keterbatasan penelitian.
- Dialog 2: topik "Monitoring kualitas air tambak udang berbasis IoT", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding; dan peserta menyebut metode tanpa alasan memilihnya.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 12 — pitching → simpan sebagai `pitching_12.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "UMKMBooks, pembukuan otomatis lewat WhatsApp", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta menyebut dana yang diminta tanpa rincian penggunaannya; dan angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya.
- Dialog 2: topik "RentKamera, sewa kamera dan lensa antar mahasiswa", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: peserta bilang "tidak ada pesaing"; dan peserta bilang pertumbuhannya "pesat" tanpa angka.

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 13 — wawancara → simpan sebagai `wawancara_13.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Quality Control di pabrik makanan", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta tidak paham pertanyaan dan minta diulang.
- Dialog 2: topik "Mobile Developer Flutter di startup kesehatan", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian; dan cerita pengalaman berhenti tanpa menyebut hasil akhirnya.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 14 — sidang → simpan sebagai `sidang_14.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Strategi komunikasi pemasaran UMKM batik melalui Instagram", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta bingung ditanya keterbatasan penelitian; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.
- Dialog 2: topik "Perbandingan algoritma K-Means dan DBSCAN untuk segmentasi pelanggan", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta menyebut metode tanpa alasan memilihnya; dan peserta tidak menyebut jumlah data/sampel/responden.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis SELURUH dialog (question, answer, reply) dalam bahasa Inggris, dan isi "language": "en". Topik boleh diterjemahkan.

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 15 — pitching → simpan sebagai `pitching_15.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "AirBersih, filter air isi ulang untuk desa", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya; dan peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan).
- Dialog 2: topik "HalalCek, scan produk untuk cek sertifikat halal", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta bilang pertumbuhannya "pesat" tanpa angka; dan peserta bilang pasarnya "semua orang" atau "semua UMKM".

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 16 — wawancara → simpan sebagai `wawancara_16.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Admin gudang di distributor sembako", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta tidak paham pertanyaan dan minta diulang; dan peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri.
- Dialog 2: topik "Content Writer di media online", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: cerita pengalaman berhenti tanpa menyebut hasil akhirnya; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 17 — sidang → simpan sebagai `sidang_17.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Pengaruh beban kerja terhadap stres perawat ICU", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding.
- Dialog 2: topik "Aplikasi absensi karyawan dengan face recognition", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta tidak menyebut jumlah data/sampel/responden; dan peserta memakai klaim terlalu umum seperti "pasti" atau "selalu".

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 18 — pitching → simpan sebagai `pitching_18.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "KantinKu, pre-order makanan kantin sekolah", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta bilang "tidak ada pesaing".
- Dialog 2: topik "ParkirPintar, sensor slot parkir di mal", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: peserta bilang pasarnya "semua orang" atau "semua UMKM"; dan peserta menyebut dana yang diminta tanpa rincian penggunaannya.

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 19 — wawancara → simpan sebagai `wawancara_19.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Business Analyst di perusahaan asuransi", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri; dan peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian.
- Dialog 2: topik "Barista dan shift leader di kedai kopi", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan).

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 20 — sidang → simpan sebagai `sidang_20.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Analisis kelayakan usaha budidaya jamur tiram", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding; dan peserta menyebut metode tanpa alasan memilihnya.
- Dialog 2: topik "Implementasi enkripsi AES pada aplikasi chat", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta memakai klaim terlalu umum seperti "pasti" atau "selalu"; dan peserta bingung ditanya keterbatasan penelitian.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 21 — pitching → simpan sebagai `pitching_21.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "SewaTenda, marketplace perlengkapan acara", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta bilang "tidak ada pesaing"; dan peserta bilang pertumbuhannya "pesat" tanpa angka.
- Dialog 2: topik "FitKampung, kelas olahraga murah di balai warga", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta menyebut dana yang diminta tanpa rincian penggunaannya; dan angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya.

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 22 — wawancara → simpan sebagai `wawancara_22.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Staf administrasi di kantor desa", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian; dan cerita pengalaman berhenti tanpa menyebut hasil akhirnya.
- Dialog 2: topik "IT Support di kampus", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta tidak paham pertanyaan dan minta diulang.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 23 — sidang → simpan sebagai `sidang_23.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Pengaruh harga dan promosi terhadap keputusan pembelian di marketplace", tipe peserta: kuat dan detail, penguji tetap menggali lebih dalam. Wajib ada situasi: peserta menyebut metode tanpa alasan memilihnya; dan peserta tidak menyebut jumlah data/sampel/responden.
- Dialog 2: topik "Deteksi berita hoaks berbahasa Indonesia dengan IndoBERT", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta bingung ditanya keterbatasan penelitian; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 24 — pitching → simpan sebagai `pitching_24.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "DaurTekstil, olah limbah kain jadi produk", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: peserta bilang pertumbuhannya "pesat" tanpa angka; dan peserta bilang pasarnya "semua orang" atau "semua UMKM".
- Dialog 2: topik "CekKos, review kos terverifikasi", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya; dan peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan).

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis SELURUH dialog (question, answer, reply) dalam bahasa Inggris, dan isi "language": "en". Topik boleh diterjemahkan.

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 25 — wawancara → simpan sebagai `wawancara_25.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Graphic Designer di agensi iklan", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: cerita pengalaman berhenti tanpa menyebut hasil akhirnya; dan angka di jawaban belakang berbeda dari jawaban sebelumnya.
- Dialog 2: topik "Teller bank BUMN", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta tidak paham pertanyaan dan minta diulang; dan peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 26 — sidang → simpan sebagai `sidang_26.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Tinjauan yuridis perlindungan data pribadi konsumen e-commerce", tipe peserta: bertele-tele dan sering melenceng dari pertanyaan. Wajib ada situasi: peserta tidak menyebut jumlah data/sampel/responden; dan peserta memakai klaim terlalu umum seperti "pasti" atau "selalu".
- Dialog 2: topik "Perancangan ulang tata letak gudang dengan metode ABC", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 27 — pitching → simpan sebagai `pitching_27.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "TernakPintar, pakan otomatis untuk peternak ayam", tipe peserta: sering bilang "tidak tahu" atau minta pertanyaan diulang. Wajib ada situasi: peserta bilang pasarnya "semua orang" atau "semua UMKM"; dan peserta menyebut dana yang diminta tanpa rincian penggunaannya.
- Dialog 2: topik "GuruAI, bank soal adaptif untuk guru SMA", tipe peserta: jawabannya umum dan kurang detail. Wajib ada situasi: peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan); dan peserta bilang "tidak ada pesaing".

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 28 — wawancara → simpan sebagai `wawancara_28.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: wawancara. Penguji: Kak Nadia, HR Recruiter, menyapa peserta dengan "kamu".
Bagian: part 1 = Perkenalan, part 2 = Cerita Pengalaman, part 3 = Diskusi Mendalam.
- Dialog 1: topik "Management Trainee di perusahaan retail", tipe peserta: suka balik bertanya ke penguji. Wajib ada situasi: angka di jawaban belakang berbeda dari jawaban sebelumnya; dan peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan).
- Dialog 2: topik "Digital Marketing di UMKM fashion", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta bercerita pakai "kami" terus tanpa jelas perannya sendiri; dan peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian.

Cara penguji menggali: Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang "kami", tanyakan apa peran DIA sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, tanyakan dengan sopan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 29 — sidang → simpan sebagai `sidang_29.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: sidang. Penguji: Prof. Hendra, dosen penguji sidang skripsi, menyapa peserta dengan "Anda".
Bagian: part 1 = Pembukaan, part 2 = Presentasi Inti, part 3 = Tanya Jawab Penguji.
- Dialog 1: topik "Pengaruh kepemimpinan transformasional terhadap kepuasan kerja guru", tipe peserta: angka atau faktanya tidak konsisten antar jawaban. Wajib ada situasi: peserta memakai klaim terlalu umum seperti "pasti" atau "selalu"; dan peserta bingung ditanya keterbatasan penelitian.
- Dialog 2: topik "Sistem pendukung keputusan pemilihan beasiswa metode SAW", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta bilang hasilnya "lebih akurat/lebih baik" tanpa pembanding; dan peserta menyebut metode tanpa alasan memilihnya.

Cara penguji menggali: Kalau peserta bilang "lebih akurat/lebih baik", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum ("pasti", "selalu"), tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```

## Prompt 30 — pitching → simpan sebagai `pitching_30.json`

```text
Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: pitching. Penguji: Pak William, investor, menyapa peserta dengan "Anda".
Bagian: part 1 = Founder & Masalah, part 2 = Elevator Pitch, part 3 = Due Diligence.
- Dialog 1: topik "LogistikDesa, kurir antar desa", tipe peserta: lancar dan percaya diri, tapi beberapa klaimnya kurang bukti. Wajib ada situasi: peserta menyebut dana yang diminta tanpa rincian penggunaannya; dan angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya.
- Dialog 2: topik "ResepSehat, katering diet untuk penderita diabetes", tipe peserta: gugup, jawabannya pendek-pendek. Wajib ada situasi: peserta bilang "tidak ada pesaing"; dan peserta bilang pertumbuhannya "pesat" tanpa angka.

Cara penguji menggali: Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang "tidak ada pesaing", tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya "semua orang"/"semua UMKM", tanyakan segmen pertama yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar.

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu mengulang pertanyaannya. "question" giliran berikutnya HARUS sama persis dengan pertanyaan yang diulang itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- Tulis dalam bahasa Indonesia, "language": "id".

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

[
  {
    "mode": "wawancara",
    "language": "id",
    "topic": "Frontend Developer di startup fintech",
    "turns": [
      {"part": 1, "question": "Boleh perkenalkan diri dulu? Ceritakan singkat latar belakang pendidikan dan pengalamanmu.", "answer": "Halo Kak, saya Dimas, lulusan informatika Universitas Brawijaya. Dua tahun terakhir saya kerja sebagai web developer di agensi digital, kebanyakan pegang proyek e-commerce pakai React.", "directive": "react", "reply": "Dua tahun pegang proyek e-commerce pakai React itu pengalaman yang relevan buat posisi ini."},
      {"part": 1, "question": "Apa yang bikin kamu tertarik melamar posisi ini?", "answer": "Jujur karena saya pengen kerja di produk yang dipakai terus-menerus, bukan proyek yang selesai lalu ditinggal.", "directive": "follow_up", "reply": "Masuk akal, capek juga ya kalau proyeknya selalu putus di tengah jalan. Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"},
      {"part": 1, "question": "Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?", "answer": "Eee, maksudnya gimana ya Kak?", "directive": "clarify", "reply": "Maksud saya begini, coba ingat aplikasi dompet digital yang kamu pakai. Ada halaman yang bikin kesal karena susah dipakai? Dari produk fintech yang pernah kamu pakai, bagian mana yang menurutmu paling perlu diperbaiki dari sisi tampilan?"}
    ]
  }
]
```
