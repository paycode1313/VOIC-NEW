# Teaser Video VOIC (Innofest 2026)

Motion graphic teaser VOIC dibuat dengan [Remotion](https://github.com/remotion-dev/remotion) (video dari kode React).
Satu teaser, dua format:

| Format | Ukuran | Untuk | Hasil |
|---|---|---|---|
| `Teaser-Web` | 1920 × 1080 (16:9) | website, layar booth, YouTube, presentasi | `out/voic-teaser-web.mp4` |
| `Teaser-Mobile` | 1080 × 1920 (9:16) | Instagram Reels/Story, TikTok, WhatsApp Status | `out/voic-teaser-mobile.mp4` |

Durasi ±43 detik, 30 fps, H.264 + AAC (bisa diputar di mana saja).

## Alur cerita

1. **Hook**: "Deg-degan mau sidang?", "Grogi pas interview?", "Ditanya investor, blank?" (warna tiap penguji, garis detak jantung).
2. **Reveal**: musik masuk, logo VOIC, "Tenang. Latihan dulu, bareng penguji kami."
3. **3 penguji, 3 karakter**: tiap penguji memperkenalkan diri dengan **suaranya sendiri dari aplikasi** dan kalimat andalannya.
4. **Simulasi panggilan**: peserta menjawab, penguji bereaksi "Hmm, baik." lalu bertanya balik (cincin avatar bergerak mengikuti suara asli).
5. **Rapor**: skor 7.5 terisi, 4 kriteria, fokus latihan, QR bawa pulang.
6. **Ajakan**: "Berani ditelepon penguji kami?" · Coba gratis di booth VOIC · Innofest 2026.

## Menjalankan

```bash
cd teaser
npm install
npm run studio          # pratinjau & edit langsung di browser (http://localhost:3000)
npm run render          # render kedua versi ke folder out/
npm run render:web      # hanya versi 16:9
npm run render:mobile   # hanya versi 9:16
```

## Suara dan musik

Suara dibuat dalam dua langkah:

```bash
# 1. Suara narator dan penguji dengan OmniVoice (mesin bawaan VoiceStudio), sekali saja, ±1 jam di CPU.
#    Butuh Python terpisah, lihat petunjuk di awal skripnya. Hasilnya: voices/<kunci>.wav
%USERPROFILE%\omnivoice-env\Scripts\python scripts/make_voices_omnivoice.py
# 2. Potong suara, susun timeline, buat musik dan efek suara ke public/audio/
npm run audio
```

- **Empat suara berbeda, semuanya berbahasa Indonesia asli**: narator (hangat, santai), HRD (perempuan muda, ceria),
  dosen (berat, pelan, berwibawa), investor (muda, cepat). Tiap persona dikloning dari rekaman acuan suara Indonesia
  (`id-ID-GadisNeural` / `id-ID-ArdiNeural`) yang diucapkan sesuai karakternya lalu digeser nadanya, jadi lafalnya
  Indonesia dan intonasinya lebih hidup. Pengaturannya di `PERSONAS` dalam `scripts/make_audio.py`.
- **Kenapa bukan suara multilingual (Emma/Ava/Brian/Andrew)?** Suara itu menebak bahasa per kalimat; Whisper mendengar
  "Grogi, pas interview?" sebagai bahasa Prancis dan "latihan dulu aja" sebagai Tagalog.
- **Cek pelafalan sebelum dipakai**: transkrip dengan Whisper **tanpa** memaksa bahasa; kalimat yang benar terdeteksi `id`.
  Memaksa bahasa Indonesia menyembunyikan lafal yang salah.
- Mengulang satu kalimat: `...python scripts/make_voices_omnivoice.py hrd` (tambahkan `--seeds 2,3` untuk versi lain; semua versi tersimpan di `voices/_takes/`, salin yang terbaik ke `voices/`).
  Kalau file di `voices/` tidak ada, `npm run audio` memakai suara Indonesia edge-tts persona itu sebagai cadangan.
- **Musik**: dibuat sendiri oleh skrip (120 BPM, sintesis numpy), jadi **bebas hak cipta**. Musik otomatis mengecil saat ada yang bicara.
- **Waktu adegan dihitung dari panjang suara** dan disimpan di `src/timeline.json`; musik ditulis mengikuti timeline itu
  (drop tepat di logo, hentakan akhir di ajakan). Jadi kalau teks narasi diubah, cukup jalankan `npm run audio` lagi.

Ingin pakai musik sendiri? Ganti `public/audio/music.wav` dengan lagu berdurasi minimal sama (±43 detik), lalu render ulang.

## Mengubah isi

| Yang diubah | File |
|---|---|
| Teks narasi / kalimat penguji | `scripts/make_audio.py` (`LINES`), lalu buat ulang suaranya (langkah 1 untuk kunci itu) dan `npm run audio` |
| Nama, warna, sifat, kalimat andalan penguji | `src/theme.ts` (`EXAMINERS`) |
| Adegan | `src/scenes/*.tsx` |
| Transisi, musik, efek suara | `src/Teaser.tsx` |

## Lisensi

- Remotion gratis untuk perorangan dan tim kecil (sampai 3 orang); tim/perusahaan yang lebih besar perlu lisensi
  perusahaan. Detailnya di [remotion.dev/license](https://www.remotion.dev/license).
- Font (Bricolage Grotesque, Figtree): SIL Open Font License.
- Suara edge-tts adalah layanan suara Microsoft yang juga dipakai aplikasi VOIC.
