"""Unit tests for the exam engine. Run: python -m unittest discover -s tests"""

import os
import sys
import tempfile
import unittest
from unittest import mock

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from fastapi import FastAPI  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402

import exam_engine  # noqa: E402

app = FastAPI()
app.include_router(exam_engine.router)
client = TestClient(app)

MESSAGES = [
    {"role": "system", "content": "Kamu adalah Prof. Hendra."},
    {"role": "user", "content": "Pertanyaan: Kenapa memilih metode itu?\nJawaban peserta: Saya memilih YOLOv8 karena deteksi real-time di pabrik tekstil butuh kecepatan tinggi.\n[Balas ...]"},
]
ANSWER = "Saya memilih YOLOv8 karena deteksi real-time di pabrik tekstil butuh kecepatan tinggi."


class QualityGateTest(unittest.TestCase):
    def test_grounded_follow_up_passes(self):
        reply = "Kecepatan real-time memang krusial di lini produksi. Bagaimana Anda mengukur akurasi YOLOv8 dibanding model lain?"
        self.assertEqual(exam_engine.quality_issues(reply, ANSWER, "follow_up", "id"), [])

    def test_legacy_template_is_rejected(self):
        reply = "Mengenai pembahasan 'YOLOv8' yang Saudara sampaikan barusan, poin tersebut saya catat. Namun bagaimana Saudara membuktikan validitasnya?"
        self.assertIn("banned_template", exam_engine.quality_issues(reply, ANSWER, "follow_up", "id"))

    def test_generic_reply_is_not_grounded(self):
        reply = "Baik, terima kasih atas penjelasan yang bagus sekali. Silakan lanjutkan ke poin berikutnya?"
        self.assertIn("not_grounded_in_answer", exam_engine.quality_issues(reply, ANSWER, "follow_up", "id"))

    def test_unsure_answer_does_not_need_grounding(self):
        reply = "Santai saja, pertanyaan ini memang sering bikin ragu, kita bahas pelan-pelan."
        self.assertEqual(exam_engine.quality_issues(reply, "Hmm saya nggak tahu harus jawab berapa untuk gaji itu.", "react", "id"), [])

    def test_follow_up_needs_exactly_one_question(self):
        self.assertIn("follow_up_without_question", exam_engine.quality_issues("YOLOv8 pilihan yang masuk akal untuk pabrik.", ANSWER, "follow_up", "id"))
        self.assertIn("multiple_questions", exam_engine.quality_issues("YOLOv8 ya? Kenapa? Bagaimana datanya?", ANSWER, "follow_up", "id"))

    def test_next_turn_must_ask_the_next_topic(self):
        good = "YOLOv8 untuk pabrik tekstil itu pilihan yang praktis. Ngomong-ngomong, apa yang membuat Anda tertarik meneliti cacat kain?"
        self.assertEqual(exam_engine.quality_issues(good, ANSWER, "next", "id"), [])
        self.assertIn("follow_up_without_question", exam_engine.quality_issues("YOLOv8 untuk pabrik tekstil itu praktis.", ANSWER, "next", "id"))

    def test_clarify_does_not_need_grounding(self):
        reply = "Maksud saya, kenapa Anda memilih metode itu dan bukan metode lain?"
        self.assertEqual(exam_engine.quality_issues(reply, "Maaf Prof, maksud pertanyaan Bapak bagaimana ya kalau boleh tahu?", "clarify", "id"), [])

    def test_react_must_not_ask(self):
        self.assertIn("react_with_question", exam_engine.quality_issues("Kecepatan YOLOv8 di pabrik tekstil masuk akal ya?", ANSWER, "react", "id"))

    def test_clean_reply_strips_markdown_and_labels(self):
        self.assertEqual(exam_engine.clean_reply("Prof. Hendra: **Baik**, saya paham. [catatan]"), "Baik, saya paham.")


class RespondEndpointTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.harvest_patch = mock.patch.object(exam_engine, "HARVEST_FILE", os.path.join(self.tmp.name, "h.jsonl"))
        self.harvest_patch.start()

    def tearDown(self):
        self.harvest_patch.stop()
        self.tmp.cleanup()

    def post(self, directive="follow_up"):
        return client.post("/exam/respond", json={
            "mode": "sidang", "role_type": "dosen_penguji", "language": "id", "directive": directive,
            "part": 3, "question": "Kenapa memilih metode itu?", "answer": ANSWER, "messages": MESSAGES,
        })

    def test_good_reply_is_returned_and_harvested(self):
        good = "Kecepatan real-time di pabrik tekstil memang alasan kuat. Bagaimana Anda memvalidasi akurasi YOLOv8 pada data cacat kain?"
        with mock.patch.object(exam_engine, "ollama_chat", mock.AsyncMock(return_value=good)):
            data = self.post().json()
        self.assertTrue(data["passed_quality"])
        self.assertEqual(data["reply"], good)
        with open(exam_engine.HARVEST_FILE, encoding="utf-8") as handle:
            self.assertEqual(len(handle.readlines()), 1)

    def test_bad_first_attempt_is_retried(self):
        bad = "Mengenai pembahasan 'YOLOv8' yang Saudara sampaikan barusan, poin tersebut saya catat. Namun bagaimana Saudara membuktikan validitasnya?"
        good = "Alasan kecepatan untuk pabrik tekstil cukup masuk akal. Berapa FPS YOLOv8 yang Anda capai saat diuji?"
        fake = mock.AsyncMock(side_effect=[bad, good])
        with mock.patch.object(exam_engine, "ollama_chat", fake):
            data = self.post().json()
        self.assertEqual(fake.await_count, 2)
        self.assertEqual(data["reply"], good)

    def test_ollama_down_returns_empty_reply(self):
        with mock.patch.object(exam_engine, "ollama_chat", mock.AsyncMock(side_effect=ConnectionError("down"))):
            data = self.post().json()
        self.assertEqual(data["reply"], "")
        self.assertFalse(os.path.exists(exam_engine.HARVEST_FILE))


if __name__ == "__main__":
    unittest.main()


class ResolveModelTest(unittest.TestCase):
    def setUp(self):
        exam_engine._model_cache.update(name=None, checked=0.0)

    def tearDown(self):
        exam_engine._model_cache.update(name=None, checked=0.0)

    def resolve_with(self, names):
        import asyncio

        response = mock.Mock(status_code=200)
        response.json.return_value = {"models": [{"name": n} for n in names]}
        client = mock.AsyncMock()
        client.get.return_value = response
        client.__aenter__.return_value = client
        with mock.patch.object(exam_engine.httpx, "AsyncClient", return_value=client):
            return asyncio.run(exam_engine.resolve_model())

    def test_uses_voic_coach_when_created(self):
        self.assertEqual(self.resolve_with(["qwen2.5:3b", "voic-coach:latest"]), exam_engine.EXAM_MODEL)

    def test_falls_back_to_available_qwen_when_voic_coach_missing(self):
        self.assertEqual(self.resolve_with(["nomic-embed-text", "qwen2.5:3b"]), "qwen2.5:3b")


class SlowModelTest(unittest.TestCase):
    def test_timeout_is_reported_with_a_clear_reason(self):
        import httpx

        with mock.patch.object(exam_engine, "ollama_chat", mock.AsyncMock(side_effect=httpx.ReadTimeout("slow"))):
            data = client.post("/exam/respond", json={
                "mode": "sidang", "role_type": "dosen_penguji", "language": "id", "directive": "follow_up",
                "part": 1, "question": "Q", "answer": ANSWER, "messages": MESSAGES,
            }).json()
        self.assertEqual(data["reply"], "")
        self.assertTrue(data["error"].startswith("timeout"))

    def test_warmup_reports_load_time(self):
        async def fake_chat(messages, num_predict, temperature, stats=None):
            stats.update({"load_ms": 12000, "tokens_per_second": 14.2})
            return "Hai"

        with mock.patch.object(exam_engine, "ollama_chat", fake_chat), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            data = client.post("/exam/warmup").json()
        self.assertTrue(data["ok"])
        self.assertEqual(data["stats"]["load_ms"], 12000)

    def test_ollama_stats_converts_nanoseconds(self):
        stats = exam_engine.ollama_stats({"load_duration": 2_000_000_000, "eval_count": 40, "eval_duration": 4_000_000_000, "prompt_eval_count": 600, "prompt_eval_duration": 3_000_000_000})
        self.assertEqual(stats["load_ms"], 2000)
        self.assertEqual(stats["tokens_per_second"], 10.0)
        self.assertEqual(stats["prompt_ms"], 3000)
