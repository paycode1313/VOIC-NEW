"""Unit tests for the spoken-text pronunciation fixes. Run: python -m unittest discover -s tests"""

import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from pronunciation import LEXICON, speech_text  # noqa: E402


class IndonesianTest(unittest.TestCase):
    def test_english_loanwords_are_respelled(self):
        self.assertEqual(speech_text("Ceritakan pengalaman coding kamu.", "id"), "Ceritakan pengalaman koding kamu.")
        self.assertEqual(speech_text("Feedback dari user online", "id"), "Fidbek dari yuser onlain")

    def test_longest_phrase_wins_and_suffixes_stay(self):
        self.assertEqual(speech_text("Tunjukkan pitch deck-nya", "id"), "Tunjukkan pic dek-nya")
        self.assertEqual(speech_text("aplikasinya di-deploy ke cloud", "id"), "aplikasinya di-diploi ke klaud")

    def test_only_whole_words(self):
        self.assertEqual(speech_text("encoding dan codingan", "id"), "encoding dan codingan")

    def test_acronyms_only_when_written_in_capitals(self):
        self.assertEqual(speech_text("Divisi IT dan AI, kirim CV", "id"), "Divisi ai ti dan ei ai, kirim si fi")
        self.assertEqual(speech_text("pantai it", "id"), "pantai it")
        self.assertEqual(speech_text("Desain UI/UX", "id"), "Desain yu ai yu eks")

    def test_nonstandard_spellings_are_spoken_the_kbbi_way(self):
        self.assertEqual(speech_text("Resiko dari praktek ini, silahkan analisa", "id"), "Risiko dari praktik ini, silakan analisis")
        self.assertEqual(speech_text("Ijin di jaman sekarang", "id"), "Izin di zaman sekarang")

    def test_indonesian_acronyms_and_ampersand(self):
        self.assertEqual(speech_text("Target KPI untuk UMKM", "id"), "Target ka pe i untuk u em ka em")
        self.assertEqual(speech_text("Admin & Keuangan, sesi Q&A", "id"), "Admin dan Keuangan, sesi kiu en ei")

    def test_casual_sounds_are_spoken_naturally(self):
        # Piper reads "oke" with a schwa and spells "Hmm" letter by letter.
        self.assertEqual(speech_text("Hmm, oke, relate banget", "id"), "Hemm, okei, rileit banget")

    def test_rupiah_and_abbreviations(self):
        self.assertEqual(speech_text("Modal Rp 5.000.000 dll.", "id"), "Modal 5000000 rupiah dan lain-lain.")
        self.assertEqual(speech_text("Omzet Rp2,5 juta per bulan", "id"), "Omzet 2,5 juta rupiah per bulan")

    def test_ranges_are_spoken_as_ranges(self):
        # Seen live: the coach suggested "Rp 12-15 juta", the voice said "12 rupiah-15 juta".
        self.assertEqual(speech_text("Gaji Rp 12-15 juta per bulan", "id"), "Gaji 12 sampai 15 juta rupiah per bulan")
        self.assertEqual(speech_text("kisaran 12‑15 juta, 2025–2026", "id"), "kisaran 12 sampai 15 juta, 2025 sampai 2026")
        self.assertEqual(speech_text("latensi turun 30 %", "id"), "latensi turun 30%")
        # Words keep their hyphen (the model sometimes writes a non-breaking one).
        self.assertEqual(speech_text("pakai time‑boxing dan e-commerce", "id"), "pakai time-boxing dan e-commerce")


class EnglishTest(unittest.TestCase):
    def test_indonesian_terms_and_rupiah(self):
        self.assertEqual(speech_text("Pak Budi asks about your skripsi.", "en"), "Pahk Budi asks about your skrip-see.")
        self.assertEqual(speech_text("We raised Rp 1.500.000 so far", "en"), "We raised 1500000 rupiah so far")
        self.assertEqual(speech_text("Somewhere around Rp 12-15 juta, 3-5 years", "en"), "Somewhere around 12 to 15 juta rupiah, 3 to 5 years")

    def test_indonesian_respellings_do_not_leak_into_english(self):
        self.assertEqual(speech_text("Tell me about your coding project online.", "en"), "Tell me about your coding project online.")


class LexiconTest(unittest.TestCase):
    def test_every_language_has_entries_and_no_empty_spellings(self):
        self.assertEqual(set(LEXICON), {"id", "en"})
        for words in LEXICON.values():
            for key, spoken in words.items():
                self.assertTrue(key.strip() and spoken.strip(), key)
                self.assertNotIn("é", spoken, f"{key}: Piper ignores é")


if __name__ == "__main__":
    unittest.main()
