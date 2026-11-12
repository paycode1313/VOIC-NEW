"""Buat paket prompt Kimi (minggu 1): 30 prompt siap salin-tempel, masing-masing 2 dialog.

    python ai_service/datasets/kimi/buat_prompt_kimi.py

Hasil: ai_service/datasets/kimi/PROMPT_KIMI.md. Setiap prompt sudah berisi topik, tipe peserta, dan situasi
yang WAJIB muncul, dipilih supaya data latih menutup celah yang dicari detektor ConversationSignals
(peran "kami", klaim tanpa bukti, klaim tanpa angka, "lebih akurat" tanpa pembanding, kontradiksi, dst.).
Total target: 60 dialog x ~12 giliran = ~700 contoh latih.
"""
import os

HERE = os.path.dirname(os.path.abspath(__file__))
OUTPUT = os.path.join(HERE, "PROMPT_KIMI.md")

PERSONA = {
    "wawancara": ("Kak Nadia, HR Recruiter", "kamu", ["Perkenalan", "Cerita Pengalaman", "Diskusi Mendalam"]),
    "sidang": ("Prof. Hendra, dosen penguji sidang skripsi", "Anda", ["Pembukaan", "Presentasi Inti", "Tanya Jawab Penguji"]),
    "pitching": ("Pak William, investor", "Anda", ["Founder & Masalah", "Elevator Pitch", "Due Diligence"]),
}

CALLED = {
    "wawancara": ("Kak Nadia", "Ms. Nadia"),
    "sidang": ("Prof. Hendra", "Prof. Hendra"),
    "pitching": ("Pak William", "Mr. William"),
}

# Cara menggali yang sama dengan playbook di ExaminerService, supaya data Kimi mengajarkan perilaku yang sama.
PLAYBOOK = {
    "wawancara": (
        "Gali dengan pola STAR (situasi, tugas, aksi, hasil). Kalau peserta banyak bilang \"kami\", tanyakan apa peran DIA "
        "sendiri. Kalau peserta mengaku punya sifat (teliti, disiplin, sabar), minta satu kejadian nyata sebagai bukti. "
        "Kalau cerita berhenti tanpa hasil, tanyakan hasil akhirnya. Kalau angka atau fakta berubah dari jawaban sebelumnya, "
        "tanyakan dengan sopan mana yang benar."
    ),
    "sidang": (
        "Kalau peserta bilang \"lebih akurat/lebih baik\", tanyakan dibanding apa. Tanyakan alasan memilih metode, jumlah "
        "data atau sampel, cara validasi, dan keterbatasan penelitian. Kalau klaimnya terlalu umum (\"pasti\", \"selalu\"), "
        "tanyakan batasannya. Kritis tapi tetap membimbing, bukan menjatuhkan."
    ),
    "pitching": (
        "Setiap klaim harus ada angkanya (pengguna, pendapatan, pertumbuhan). Kalau peserta bilang \"tidak ada pesaing\", "
        "tanyakan pelanggan sekarang memakai cara apa. Kalau pasarnya \"semua orang\"/\"semua UMKM\", tanyakan segmen pertama "
        "yang paling spesifik. Tanyakan dana yang diminta dipakai untuk apa saja. Kalau angka berubah, tanyakan mana yang benar."
    ),
}

TOPICS = {
    "wawancara": [
        "Software Engineer backend di perusahaan logistik", "Customer Service di bank digital", "Akuntan junior di KAP",
        "Social Media Specialist di brand skincare", "Guru SD di sekolah swasta", "Perawat di rumah sakit swasta",
        "HR Generalist di pabrik garmen", "Sales Executive alat berat", "Quality Control di pabrik makanan",
        "Mobile Developer Flutter di startup kesehatan", "Admin gudang di distributor sembako", "Content Writer di media online",
        "Business Analyst di perusahaan asuransi", "Barista dan shift leader di kedai kopi", "Staf administrasi di kantor desa",
        "IT Support di kampus", "Graphic Designer di agensi iklan", "Teller bank BUMN",
        "Management Trainee di perusahaan retail", "Digital Marketing di UMKM fashion",
    ],
    "sidang": [
        "Sistem pakar diagnosa penyakit kucing metode forward chaining",
        "Pengaruh gaji dan lingkungan kerja terhadap kinerja karyawan pabrik",
        "Analisis sentimen ulasan aplikasi ojek online dengan Naive Bayes",
        "Rancang bangun sistem informasi posyandu berbasis web",
        "Pengaruh literasi keuangan terhadap perilaku menabung mahasiswa",
        "Prediksi harga cabai dengan LSTM",
        "Efektivitas pembelajaran berbasis proyek terhadap hasil belajar IPA siswa SMP",
        "Monitoring kualitas air tambak udang berbasis IoT",
        "Strategi komunikasi pemasaran UMKM batik melalui Instagram",
        "Perbandingan algoritma K-Means dan DBSCAN untuk segmentasi pelanggan",
        "Pengaruh beban kerja terhadap stres perawat ICU",
        "Aplikasi absensi karyawan dengan face recognition",
        "Analisis kelayakan usaha budidaya jamur tiram",
        "Implementasi enkripsi AES pada aplikasi chat",
        "Pengaruh harga dan promosi terhadap keputusan pembelian di marketplace",
        "Deteksi berita hoaks berbahasa Indonesia dengan IndoBERT",
        "Tinjauan yuridis perlindungan data pribadi konsumen e-commerce",
        "Perancangan ulang tata letak gudang dengan metode ABC",
        "Pengaruh kepemimpinan transformasional terhadap kepuasan kerja guru",
        "Sistem pendukung keputusan pemilihan beasiswa metode SAW",
    ],
    "pitching": [
        "KosHub, aplikasi cari dan bayar kos untuk mahasiswa", "PanenLink, menghubungkan petani sayur langsung ke restoran",
        "BengkelGo, servis motor panggilan", "LansiaCare, pendamping lansia berbasis aplikasi",
        "SisaMakan, jual makanan sisa hari ini dari restoran dengan harga murah", "TutorDekat, les privat SD dari mahasiswa sekitar",
        "UMKMBooks, pembukuan otomatis lewat WhatsApp", "RentKamera, sewa kamera dan lensa antar mahasiswa",
        "AirBersih, filter air isi ulang untuk desa", "HalalCek, scan produk untuk cek sertifikat halal",
        "KantinKu, pre-order makanan kantin sekolah", "ParkirPintar, sensor slot parkir di mal",
        "SewaTenda, marketplace perlengkapan acara", "FitKampung, kelas olahraga murah di balai warga",
        "DaurTekstil, olah limbah kain jadi produk", "CekKos, review kos terverifikasi",
        "TernakPintar, pakan otomatis untuk peternak ayam", "GuruAI, bank soal adaptif untuk guru SMA",
        "LogistikDesa, kurir antar desa", "ResepSehat, katering diet untuk penderita diabetes",
    ],
}

TYPES = [
    "lancar dan percaya diri, tapi beberapa klaimnya kurang bukti",
    "gugup, jawabannya pendek-pendek",
    "bertele-tele dan sering melenceng dari pertanyaan",
    "sering bilang \"tidak tahu\" atau minta pertanyaan diulang",
    "jawabannya umum dan kurang detail",
    "kuat dan detail, penguji tetap menggali lebih dalam",
    "suka balik bertanya ke penguji",
    "angka atau faktanya tidak konsisten antar jawaban",
]

SITUATIONS = {
    "wawancara": [
        "peserta bercerita pakai \"kami\" terus tanpa jelas perannya sendiri",
        "peserta mengaku punya sifat (misalnya teliti/sabar/disiplin) tanpa contoh kejadian",
        "cerita pengalaman berhenti tanpa menyebut hasil akhirnya",
        "angka di jawaban belakang berbeda dari jawaban sebelumnya",
        "peserta balik bertanya soal posisi/perusahaan (jawab singkat dulu, lalu kembali ke pertanyaan)",
        "peserta tidak paham pertanyaan dan minta diulang",
    ],
    "sidang": [
        "peserta bilang hasilnya \"lebih akurat/lebih baik\" tanpa pembanding",
        "peserta menyebut metode tanpa alasan memilihnya",
        "peserta tidak menyebut jumlah data/sampel/responden",
        "peserta memakai klaim terlalu umum seperti \"pasti\" atau \"selalu\"",
        "peserta bingung ditanya keterbatasan penelitian",
        "angka di jawaban belakang berbeda dari jawaban sebelumnya",
    ],
    "pitching": [
        "peserta bilang \"tidak ada pesaing\"",
        "peserta bilang pertumbuhannya \"pesat\" tanpa angka",
        "peserta bilang pasarnya \"semua orang\" atau \"semua UMKM\"",
        "peserta menyebut dana yang diminta tanpa rincian penggunaannya",
        "angka pengguna/pendapatan di jawaban belakang berbeda dari sebelumnya",
        "peserta balik bertanya soal syarat investasi (jawab singkat dulu, lalu kembali ke pertanyaan)",
    ],
}

# 3 prompt berbahasa Inggris (persona & mode sama), sisanya Indonesia.
ENGLISH = {(0, "wawancara"), (4, "sidang"), (7, "pitching")}

EXAMPLE = """[
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
]"""


def prompt(number: int, mode: str, index: int) -> str:
    examiner, address, parts = PERSONA[mode]
    english = (index, mode) in ENGLISH
    topics = TOPICS[mode][index * 2: index * 2 + 2]
    types = [TYPES[(index * 2 + offset + number) % len(TYPES)] for offset in (0, 1)]
    situations = SITUATIONS[mode]
    picks = [[situations[(index + k) % len(situations)] for k in (0, 1)],
             [situations[(index + k + 3) % len(situations)] for k in (0, 1)]]
    language_rule = (
        'Tulis SELURUH dialog (question, answer, reply) dalam bahasa Inggris, dan isi "language": "en". Topik boleh diterjemahkan. Tidak perlu membuat versi terjemahan bahasa Indonesia.'
        if english else 'Tulis dalam bahasa Indonesia, "language": "id".'
    )
    dialogs = "\n".join(
        f"- Dialog {k + 1}: topik \"{topics[k]}\", tipe peserta: {types[k]}. Wajib ada situasi: {picks[k][0]}; dan {picks[k][1]}."
        for k in range(2)
    )
    return f"""Kamu adalah penulis data latih untuk AI penguji lisan. Tulis 2 dialog simulasi lengkap.

Mode: {mode}. Penguji: {examiner}, menyapa peserta dengan "{address}". Peserta memanggil penguji "{CALLED[mode][1 if english else 0]}".
Bagian: part 1 = {parts[0]}, part 2 = {parts[1]}, part 3 = {parts[2]}.
{dialogs}

Cara penguji menggali: {PLAYBOOK[mode]}

Aturan isi:
- Tiap dialog 10 sampai 14 giliran, mencakup part 1, 2, dan 3 berurutan.
- Tiap giliran: penguji bertanya ("question"), peserta menjawab ("answer"), penguji menanggapi ("reply").
- "reply" WAJIB nyambung: sebut detail spesifik dari jawaban peserta (angka, nama alat, contoh). Jangan pujian umum seperti "bagus sekali", jangan memulai dua reply berturut-turut dengan kata yang sama, jangan mengulang pertanyaan yang sudah pernah ditanyakan.
- Kalimat pendek dan enak diucapkan (akan diubah jadi suara). Tanpa emoji, markdown, atau daftar bernomor.
- "directive" salah satu:
  - "react": reply 1-2 kalimat tanggapan, TANPA tanda tanya sama sekali.
  - "follow_up": tanggapan singkat + TEPAT SATU pertanyaan lanjutan yang menggali bagian paling lemah dari jawaban. "question" giliran berikutnya HARUS sama persis dengan pertanyaan lanjutan itu.
  - "clarify": dipakai saat peserta bingung, bilang tidak tahu, minta diulang, atau balik bertanya. Reply menjelaskan dengan kata lebih sederhana atau menjawab singkat pertanyaan peserta, lalu menutup dengan pertanyaannya, cukup ditulis SATU kali (jangan diulang dua kali berturut-turut). "question" giliran berikutnya HARUS sama persis dengan pertanyaan penutup itu.
- Campuran wajar: sekitar 40% react, 45% follow_up, 15% clarify. Situasi wajib di atas harus benar-benar muncul di jawaban peserta, dan penguji harus menggalinya dengan follow_up.
- {language_rule}

Format keluaran: HANYA satu blok JSON valid (array berisi 2 dialog), tanpa penjelasan apa pun sebelum atau sesudahnya. Contoh format (isinya jangan ditiru):

{EXAMPLE}"""


def main() -> None:
    sections = []
    number = 0
    for index in range(10):
        for mode in ("wawancara", "sidang", "pitching"):
            number += 1
            sections.append(f"## Prompt {number:02d} — {mode} → simpan sebagai `{mode}_{number:02d}.json`\n\n```text\n{prompt(number, mode, index)}\n```\n")
    header = """# Paket prompt Kimi — minggu 1 (30 prompt, ±60 dialog, ±700 contoh latih)

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

"""
    with open(OUTPUT, "w", encoding="utf-8", newline="\n") as handle:
        handle.write(header + "\n".join(sections))
    print(f"✓ {OUTPUT} ({number} prompt)")


if __name__ == "__main__":
    main()
