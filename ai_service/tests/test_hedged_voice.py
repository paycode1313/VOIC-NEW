"""Hedged online-voice requests: a slow edge-tts call gets a parallel twin. Run: python -m unittest discover -s tests"""

import asyncio
import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from voice_engine import _hedged  # noqa: E402


def attempts(*plans):
    """Each plan is (delay, result or Exception); returns make_attempt plus the list of attempts started."""
    started = []

    async def make_attempt():
        delay, outcome = plans[len(started)]
        started.append(outcome)
        await asyncio.sleep(delay)
        if isinstance(outcome, Exception):
            raise outcome
        return outcome

    return make_attempt, started


class HedgedTest(unittest.TestCase):
    def run_hedged(self, make_attempt, hedge_after=0.05, discard=None):
        return asyncio.run(_hedged(make_attempt, hedge_after, discard))

    def test_fast_first_attempt_needs_no_twin(self):
        make, started = attempts((0.0, "a.mp3"), (0.0, "b.mp3"))
        self.assertEqual(self.run_hedged(make), "a.mp3")
        self.assertEqual(started, ["a.mp3"])

    def test_slow_first_attempt_loses_to_the_twin(self):
        make, started = attempts((0.5, "slow.mp3"), (0.01, "fast.mp3"))
        self.assertEqual(self.run_hedged(make), "fast.mp3")
        self.assertEqual(len(started), 2)

    def test_a_third_attempt_rescues_two_stuck_ones(self):
        make, started = attempts((1.0, "stuck1.mp3"), (1.0, "stuck2.mp3"), (0.0, "third.mp3"))
        self.assertEqual(self.run_hedged(make, hedge_after=(0.02, 0.05)), "third.mp3")
        self.assertEqual(len(started), 3)

    def test_quick_failure_starts_the_twin_at_once(self):
        make, _ = attempts((0.0, RuntimeError("403")), (0.0, "retry.mp3"))
        self.assertEqual(self.run_hedged(make, hedge_after=5), "retry.mp3")

    def test_fails_only_when_every_attempt_fails(self):
        make, _ = attempts((0.1, RuntimeError("one")), (0.0, RuntimeError("two")))
        with self.assertRaises(RuntimeError):
            self.run_hedged(make)

    def test_a_second_finished_file_is_discarded_not_the_chosen_one(self):
        discarded = []
        make, _ = attempts((0.06, "first.mp3"), (0.0, "second.mp3"))
        chosen = self.run_hedged(make, hedge_after=0.05, discard=discarded.append)
        self.assertNotIn(chosen, discarded)


if __name__ == "__main__":
    unittest.main()
