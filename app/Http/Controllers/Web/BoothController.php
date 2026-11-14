<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExaminerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Operator tools for the exhibition booth: AI status, today's numbers, a
 * leaderboard for a second screen, and a one-click reset between visitors.
 */
class BoothController extends Controller
{
    public function __construct(protected ExamBlueprint $blueprint) {}

    /**
     * Operator dashboard (or the PIN form).
     */
    public function index(Request $request, ExaminerService $examiner): View
    {
        if (! $this->unlocked($request)) {
            return view('booth.unlock', ['enabled' => $this->pin() !== null]);
        }

        $today = PracticeSession::query()->takenByPeople()->whereNotNull('exam_mode')->whereDate('created_at', today())->get();
        $finished = $today->filter(fn (PracticeSession $session) => isset($session->feedback_notes['exam_report']));

        return view('booth.index', [
            'status' => $examiner->availability(),
            'stats' => [
                'started' => $today->count(),
                'finished' => $finished->count(),
                'average' => $finished->isEmpty() ? null : round($finished->avg(fn ($session) => $session->feedback_notes['exam_report']['overall_band']), 1),
            ],
            'leaders' => $this->leaders($finished),
            'startUrl' => rtrim((string) (config('exam.public_url') ?: url('/')), '/').'/latihan',
        ]);
    }

    /**
     * Full-screen leaderboard for a second monitor (auto-refreshes).
     */
    public function board(Request $request): View|RedirectResponse
    {
        if (! $this->unlocked($request)) {
            return redirect()->route('booth.index');
        }

        $finished = PracticeSession::query()->takenByPeople()->whereNotNull('exam_mode')->whereDate('created_at', today())->get()
            ->filter(fn (PracticeSession $session) => isset($session->feedback_notes['exam_report']));

        return view('booth.board', [
            'leaders' => $this->leaders($finished, 10),
            'startUrl' => rtrim((string) (config('exam.public_url') ?: url('/')), '/').'/latihan',
        ]);
    }

    /**
     * Public welcome screen for the booth laptop: an examiner "calling in" on a loop.
     * Shows no personal data, only today's session count.
     */
    public function attract(): View
    {
        return view('booth.attract', [
            'modes' => $this->blueprint->modes(),
            'today' => PracticeSession::query()->takenByPeople()->whereNotNull('exam_mode')->whereDate('created_at', today())->count(),
            'startUrl' => rtrim((string) (config('exam.public_url') ?: url('/')), '/').'/latihan',
        ]);
    }

    /**
     * A visitor picks up the call: forget the previous visitor, then go to that examiner's setup.
     */
    public function pickUp(Request $request): RedirectResponse
    {
        $mode = (string) $request->input('mode');
        abort_unless(array_key_exists($mode, config('exam.modes')), 404);

        $this->forgetVisitor($request);

        return redirect()->route('exam.setup', $mode);
    }

    /**
     * Check the PIN.
     */
    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['pin' => ['required', 'string', 'max:32']]);
        $pin = $this->pin();

        if ($pin === null || ! hash_equals($pin, (string) $request->input('pin'))) {
            return back()->withErrors(['pin' => 'PIN salah.']);
        }

        $request->session()->put('booth_unlocked', true);

        return redirect()->route('booth.index');
    }

    /**
     * Clear the current visitor's guest session so the next person starts fresh.
     */
    public function nextVisitor(Request $request): RedirectResponse
    {
        abort_unless($this->unlocked($request), 403);

        $this->forgetVisitor($request);

        return redirect()->route('exam.index')->with('status', 'Siap untuk pengunjung berikutnya!');
    }

    /**
     * Drop the guest's access to their session so the next visitor cannot open it.
     */
    protected function forgetVisitor(Request $request): void
    {
        $request->session()->forget(['active_guest_session_id', 'guest_session_token', 'pending_claim_session_id', 'guest_name', 'guest_institution', 'study_code']);
    }

    /**
     * Lock the operator page again.
     */
    public function lock(Request $request): RedirectResponse
    {
        $request->session()->forget('booth_unlocked');

        return redirect()->route('booth.index');
    }

    /**
     * Whether this browser entered the operator PIN.
     */
    public static function isUnlocked(Request $request): bool
    {
        return (bool) $request->session()->get('booth_unlocked', false);
    }

    protected function unlocked(Request $request): bool
    {
        return $this->pin() !== null && self::isUnlocked($request);
    }

    protected function pin(): ?string
    {
        $pin = trim((string) config('exam.booth_pin', ''));

        return $pin === '' ? null : $pin;
    }

    /**
     * Today's best results; only first names are shown on screen.
     *
     * @param  Collection<int, PracticeSession>  $finished
     * @return list<array{name: string, mode: string, band: float, time: string}>
     */
    protected function leaders(Collection $finished, int $limit = 8): array
    {
        return $finished
            ->sortByDesc(fn (PracticeSession $session) => $session->feedback_notes['exam_report']['overall_band'])
            ->take($limit)
            ->map(fn (PracticeSession $session) => [
                'name' => Str::of($session->getParticipantName())->explode(' ')->first() ?: 'Pengunjung',
                'mode' => $this->blueprint->text($this->blueprint->mode($session->exam_mode)['title'], 'id'),
                'band' => (float) $session->feedback_notes['exam_report']['overall_band'],
                'time' => $session->created_at->format('H:i'),
            ])
            ->values()
            ->all();
    }
}
