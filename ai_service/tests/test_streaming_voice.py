"""Streaming & voice tests (no Ollama / Whisper / Piper needed). Run: python -m unittest discover -s tests"""

import json
import os
import sys
import tempfile
import unittest
from unittest import mock

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from fastapi import FastAPI  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

import exam_engine  # noqa: E402
import voice_engine  # noqa: E402

app = FastAPI()
app.include_router(exam_engine.router)
client = TestClient(app)

ANSWER = "Saya memilih YOLOv8 karena deteksi real-time di pabrik tekstil butuh kecepatan tinggi."
BODY = {
    "mode": "sidang", "role_type": "dosen_penguji", "language": "id", "directive": "next", "part": 1,
    "question": "Kenapa?", "answer": ANSWER,
    "messages": [{"role": "system", "content": "x"}, {"role": "user", "content": "y"}],
}


def fake_stream(text):
    async def gen(*args, **kwargs):
        # Kirim per 3 karakter, seperti token Ollama.
        for i in range(0, len(text), 3):
            yield text[i:i + 3]
    return gen


class SplitSentencesTest(unittest.TestCase):
    def test_keeps_incomplete_tail_in_buffer(self):
        sentences, rest = exam_engine.split_sentences("Kecepatan real-time memang penting. Lalu bagaimana Anda")
        self.assertEqual(sentences, ["Kecepatan real-time memang penting."])
        self.assertEqual(rest, "Lalu bagaimana Anda")

    def test_does_not_split_on_titles_or_tiny_fragments(self):
        sentences, _ = exam_engine.split_sentences("Oke. Saya Prof. Hendra dari teknik informatika. Mari mulai ")
        self.assertEqual(sentences, ["Oke. Saya Prof. Hendra dari teknik informatika."])

    def test_final_flush(self):
        sentences, rest = exam_engine.split_sentences("Bagaimana Anda memvalidasinya?", final=True)
        self.assertEqual(sentences, ["Bagaimana Anda memvalidasinya?"])
        self.assertEqual(rest, "")


class StreamEndpointTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.patches = [
            mock.patch.object(exam_engine, "HARVEST_FILE", os.path.join(self.tmp.name, "h.jsonl")),
            mock.patch.object(voice_engine, "synthesize", mock.AsyncMock(side_effect=lambda t, *a, **k: f"tts_{len(t)}.wav")),
        ]
        for p in self.patches:
            p.start()

    def tearDown(self):
        for p in self.patches:
            p.stop()
        self.tmp.cleanup()

    def events(self, text, body=None):
        with mock.patch.object(exam_engine, "ollama_stream", fake_stream(text)):
            response = client.post("/exam/respond/stream", json=body or BODY)
        return [json.loads(line) for line in response.text.splitlines() if line.strip()]

    def test_sentences_stream_with_audio_and_stop_at_first_question(self):
        events = self.events(
            "Kecepatan real-time di pabrik tekstil memang alasan kuat. "
            "Ngomong-ngomong, apa yang bikin Anda tertarik meneliti cacat kain? "
            "Ini kalimat yang seharusnya tidak pernah diucapkan."
        )
        sentences = [e for e in events if e["type"] == "sentence"]
        self.assertEqual(len(sentences), 2)
        self.assertTrue(sentences[0]["audio_file"].endswith(".wav"))
        self.assertTrue(sentences[1]["text"].endswith("?"))
        done = events[-1]
        self.assertEqual(done["type"], "done")
        self.assertEqual(done["issues"], [])
        self.assertIsNotNone(done["first_sentence_ms"])

    def test_short_fragment_is_not_dropped_with_the_question(self):
        events = self.events("Kecepatan real-time di pabrik tekstil memang penting. Oke. Lalu bagaimana Anda mengukur akurasinya?")
        texts = [e["text"] for e in events if e["type"] == "sentence"]
        self.assertTrue(texts[-1].endswith("akurasinya?"), texts)

    def test_react_directive_never_speaks_a_question(self):
        body = dict(BODY, directive="react")
        events = self.events("Apakah YOLOv8 cepat? Kecepatan real-time di pabrik tekstil itu krusial.", body)
        texts = [e["text"] for e in events if e["type"] == "sentence"]
        self.assertEqual(texts, ["Kecepatan real-time di pabrik tekstil itu krusial."])

    def test_ollama_failure_reports_error_then_done(self):
        async def broken(*args, **kwargs):
            raise ConnectionError("ollama down")
            yield  # pragma: no cover

        with mock.patch.object(exam_engine, "ollama_stream", broken):
            response = client.post("/exam/respond/stream", json=BODY)
        events = [json.loads(line) for line in response.text.splitlines()]
        self.assertEqual([e["type"] for e in events], ["error", "done"])
        self.assertEqual(events[-1]["reply"], "")


class InlineAudioTest(unittest.TestCase):
    def test_inline_audio_is_embedded_and_file_removed(self):
        with tempfile.TemporaryDirectory() as tmp, mock.patch.object(voice_engine, "AUDIO_OUTPUT_DIR", tmp):
            path = os.path.join(tmp, "tts_1_a.wav")
            with open(path, "wb") as handle:
                handle.write(b"RIFFdata")
            payload = exam_engine.audio_payload("tts_1_a.wav", inline=True)
            self.assertEqual(payload["audio_data"], "data:audio/wav;base64,UklGRmRhdGE=")
            self.assertFalse(os.path.exists(path))
            self.assertEqual(exam_engine.audio_payload("tts_2.mp3", inline=False), {"audio_file": "tts_2.mp3"})


class VoiceEngineTest(unittest.TestCase):
    def test_tts_falls_back_when_piper_voice_missing(self):
        with mock.patch.dict(voice_engine.PIPER_VOICES, {"id": "/does/not/exist.onnx"}):
            self.assertIsNone(voice_engine.synthesize_piper("Halo", "hrd", "id", tempfile.gettempdir()))

    def test_whisper_silence_hallucination_is_dropped(self):
        segment = mock.Mock(text=" Terima kasih.")
        model = mock.Mock()
        model.transcribe.return_value = ([segment], mock.Mock(duration=1.2))
        with mock.patch.object(voice_engine, "_load_stt", return_value=model):
            result = voice_engine.transcribe(b"x" * 2000, "id")
        self.assertEqual(result["text"], "")

    def test_transcribe_passes_language_and_topic_prompt(self):
        segment = mock.Mock(text=" Saya memakai YOLOv8 ")
        model = mock.Mock()
        model.transcribe.return_value = ([segment], mock.Mock(duration=3.0))
        with mock.patch.object(voice_engine, "_load_stt", return_value=model):
            result = voice_engine.transcribe(b"x" * 2000, "id", prompt="YOLOv8, cacat kain")
        self.assertEqual(result["text"], "Saya memakai YOLOv8")
        kwargs = model.transcribe.call_args.kwargs
        self.assertEqual(kwargs["language"], "id")
        self.assertEqual(kwargs["initial_prompt"], "YOLOv8, cacat kain")


if __name__ == "__main__":
    unittest.main()


class CoachTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.patches = [
            mock.patch.object(exam_engine, "HARVEST_FILE", os.path.join(self.tmp.name, "h.jsonl")),
            mock.patch.object(voice_engine, "synthesize", mock.AsyncMock(return_value=None)),
        ]
        for p in self.patches:
            p.start()

    def tearDown(self):
        for p in self.patches:
            p.stop()
        self.tmp.cleanup()

    def test_coach_turn_streams_several_sentences_and_does_not_stop_at_first_question(self):
        text = ("Pakai YOLOv8 untuk pabrik tekstil itu menarik banget. "
                "Tapi coba buka jawabanmu dengan masalahnya dulu, ya? "
                "Misalnya: \"Di pabrik kami, dua belas persen cacat kecil lolos ke pengemasan.\" "
                "Sekarang coba ucapkan ulang dengan pembuka itu.")
        body = dict(BODY, directive="coach")
        with mock.patch.object(exam_engine, "ollama_stream", fake_stream(text)):
            response = client.post("/exam/respond/stream", json=body)
        sentences = [json.loads(l) for l in response.text.splitlines() if '"sentence"' in l]
        self.assertEqual(len(sentences), 4)

    def test_coach_summary_parses_sections(self):
        raw = ("BAGUS:\n- Kamu sudah memberi contoh nyata dari pabrik.\n- Tempo bicaramu tenang.\n\n"
               "LATIH:\n- Sebutkan angka hasil penelitian.\n\n"
               "KALIMAT:\n- \"Penelitian ini menurunkan cacat yang lolos dari 12 menjadi 3 persen.\"\n")
        with mock.patch.object(exam_engine, "ollama_chat", mock.AsyncMock(return_value=raw)), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            data = client.post("/exam/coach-summary", json={
                "mode": "sidang", "role_type": "dosen_penguji", "language": "id",
                "transcript": [{"role": "user", "content": "Saya pakai YOLOv8."}, {"role": "coach", "content": "Bagus."}],
            }).json()
        self.assertEqual(data["strengths"], ["Kamu sudah memberi contoh nyata dari pabrik.", "Tempo bicaramu tenang."])
        self.assertEqual(data["practice"], ["Sebutkan angka hasil penelitian."])
        self.assertEqual(data["phrases"], ["Penelitian ini menurunkan cacat yang lolos dari 12 menjadi 3 persen."])
