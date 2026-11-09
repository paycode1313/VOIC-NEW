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

    def test_clean_reply_keeps_quoted_example_sentences(self):
        text = 'Bagus. Coba bilang: "Saya terbiasa menjelaskan data." Sekarang ulangi.'
        self.assertEqual(exam_engine.clean_reply(text, max_sentences=4), text)

    def test_clean_reply_keeps_a_closing_quote_at_the_end(self):
        text = 'Coba bilang: "Saya membantu tim menaikkan konversi lima belas persen."'
        self.assertEqual(exam_engine.clean_reply(text), text)

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

    def test_prefers_the_newer_qwen3_over_qwen2_5(self):
        self.assertEqual(self.resolve_with(["qwen2.5:3b", "qwen3:4b"]), "qwen3:4b")


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


class ThinkingModelTest(unittest.TestCase):
    """Qwen3 can write <think>...</think> reasoning first; it must never be spoken."""

    def test_strip_think_removes_reasoning_blocks(self):
        raw = "<think>Peserta menyebut YOLOv8, saya tanya akurasi.</think>\nAlasan yang kuat. Bagaimana Anda mengukur akurasinya?"
        self.assertEqual(exam_engine.strip_think(raw), "Alasan yang kuat. Bagaimana Anda mengukur akurasinya?")
        self.assertEqual(exam_engine.strip_think("<think>belum selesai berpikir"), "")
        self.assertEqual(exam_engine.clean_reply("<think>\n\n</think>\n\nBaik. Lanjut?"), "Baik. Lanjut?")

    def test_think_filter_handles_tags_split_across_chunks(self):
        text = "<think>rencana jawaban</think>Kecepatan memang penting. Bagaimana akurasinya?"
        thinking = exam_engine.ThinkFilter()
        out = "".join(thinking.feed(text[i:i + 3]) for i in range(0, len(text), 3)) + thinking.flush()
        self.assertEqual(out, "Kecepatan memang penting. Bagaimana akurasinya?")

    def test_think_filter_passes_plain_text_through(self):
        thinking = exam_engine.ThinkFilter()
        out = thinking.feed("Harga 5 < 10 itu wajar. ") + thinking.feed("Kenapa?") + thinking.flush()
        self.assertEqual(out, "Harga 5 < 10 itu wajar. Kenapa?")

    def test_requests_ask_ollama_not_to_think_and_use_a_larger_context(self):
        sent = {}

        class FakeResponse:
            def raise_for_status(self):
                return None

            def json(self):
                return {"message": {"content": "<think>x</think>Oke."}}

        class FakeClient:
            def __init__(self, *args, **kwargs):
                pass

            async def __aenter__(self):
                return self

            async def __aexit__(self, *exc):
                return False

            async def post(self, url, json):
                sent.update(json)
                return FakeResponse()

        import asyncio

        with mock.patch.object(exam_engine.httpx, "AsyncClient", FakeClient), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            asyncio.run(exam_engine.ollama_chat(MESSAGES, num_predict=50, temperature=0.7))

        self.assertIs(sent["think"], False)
        self.assertGreaterEqual(sent["options"]["num_ctx"], 4096)


class CloudModelTest(unittest.TestCase):
    """kimi-k3:cloud runs on Ollama's servers; when it fails, the local model answers instead."""

    def fake_client(self, calls, fail_models=()):
        class FakeResponse:
            def __init__(self, model):
                self.model = model

            def raise_for_status(self):
                if self.model in fail_models:
                    raise RuntimeError(f"{self.model} unauthorized")

            def json(self):
                return {"message": {"content": f"Jawaban dari {self.model}."}}

        class FakeClient:
            def __init__(self, *args, **kwargs):
                pass

            async def __aenter__(self):
                return self

            async def __aexit__(self, *exc):
                return False

            async def post(self, url, json):
                calls.append(json)
                return FakeResponse(json["model"])

        return FakeClient

    def run_chat(self, calls, fail_models=(), model="kimi-k3:cloud"):
        import asyncio

        stats = {}
        with mock.patch.object(exam_engine.httpx, "AsyncClient", self.fake_client(calls, fail_models)), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            text = asyncio.run(exam_engine.ollama_chat(MESSAGES, num_predict=90, temperature=0.7, stats=stats, model=model))
        return text, stats

    def test_cloud_model_is_detected(self):
        self.assertTrue(exam_engine.is_cloud_model("kimi-k3:cloud"))
        self.assertTrue(exam_engine.is_cloud_model("gpt-oss:120b-cloud"))
        self.assertFalse(exam_engine.is_cloud_model("qwen3:4b"))
        self.assertFalse(exam_engine.is_cloud_model(None))

    def test_cloud_request_skips_local_only_options(self):
        calls = []
        text, stats = self.run_chat(calls)
        self.assertEqual(text, "Jawaban dari kimi-k3:cloud.")
        self.assertEqual(stats["model"], "kimi-k3:cloud")
        self.assertEqual(calls[0]["model"], "kimi-k3:cloud")
        for key in ("think", "keep_alive"):
            self.assertNotIn(key, calls[0])
        self.assertNotIn("num_ctx", calls[0]["options"])
        self.assertNotIn("num_predict", calls[0]["options"])

    def test_falls_back_to_local_model_when_cloud_fails(self):
        calls = []
        text, stats = self.run_chat(calls, fail_models=("kimi-k3:cloud",))
        self.assertEqual([c["model"] for c in calls], ["kimi-k3:cloud", "voic-coach"])
        self.assertEqual(text, "Jawaban dari voic-coach.")
        self.assertEqual(stats["model"], "voic-coach")
        self.assertIs(calls[1]["think"], False)

    def test_without_requested_model_only_local_is_used(self):
        calls = []
        self.run_chat(calls, model=None)
        self.assertEqual([c["model"] for c in calls], ["voic-coach"])

    def test_respond_endpoint_forwards_the_model_and_reports_who_answered(self):
        reply = "Kecepatan real-time memang krusial di pabrik tekstil. Bagaimana Anda mengukur akurasi YOLOv8?"

        async def fake_chat(messages, num_predict, temperature, stats=None, model=None):
            stats["model"] = model
            return reply

        with mock.patch.object(exam_engine, "ollama_chat", fake_chat), \
                mock.patch.object(exam_engine, "harvest"), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            data = client.post("/exam/respond", json={
                "mode": "sidang", "role_type": "dosen_penguji", "directive": "follow_up",
                "answer": ANSWER, "messages": MESSAGES, "model": "kimi-k3:cloud",
            }).json()
        self.assertEqual(data["model"], "kimi-k3:cloud")
        self.assertEqual(data["reply"], reply)

    def test_stream_falls_back_before_any_text_is_spoken(self):
        import asyncio
        import json as jsonlib

        tried = []

        class FakeStream:
            def __init__(self, model):
                self.model = model

            async def __aenter__(self):
                return self

            async def __aexit__(self, *exc):
                return False

            def raise_for_status(self):
                return None

            async def aiter_lines(self):
                if self.model == "kimi-k3:cloud":
                    yield jsonlib.dumps({"error": "this model requires a subscription"})
                    return
                yield jsonlib.dumps({"message": {"content": "Baik, lanjut."}, "done": True})

        class FakeClient:
            def __init__(self, *args, **kwargs):
                pass

            async def __aenter__(self):
                return self

            async def __aexit__(self, *exc):
                return False

            def stream(self, method, url, json):
                tried.append(json["model"])
                return FakeStream(json["model"])

        async def collect():
            used = {}
            chunks = [c async for c in exam_engine.ollama_stream(MESSAGES, 90, 0.7, model="kimi-k3:cloud", used=used)]
            return chunks, used

        with mock.patch.object(exam_engine.httpx, "AsyncClient", FakeClient), \
                mock.patch.object(exam_engine, "resolve_model", mock.AsyncMock(return_value="voic-coach")):
            chunks, used = asyncio.run(collect())

        self.assertEqual(tried, ["kimi-k3:cloud", "voic-coach"])
        self.assertEqual("".join(chunks), "Baik, lanjut.")
        self.assertEqual(used["model"], "voic-coach")
