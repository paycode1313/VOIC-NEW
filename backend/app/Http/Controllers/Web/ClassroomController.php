<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\PracticeSession;
use App\Models\User;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mentor dashboard: a lecturer, thesis supervisor or career centre creates a class, students join with its code,
 * and the mentor follows their mock-exam reports (read-only). Students choose to join and can leave at any time.
 */
class ClassroomController extends Controller
{
    public function __construct(protected ExamProgressService $progress, protected ExamBlueprint $blueprint) {}

    /**
     * Classes I mentor and classes I joined.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('classrooms.index', [
            'owned' => $user->ownedClassrooms()->withCount('members')->latest()->get(),
            'joined' => $user->joinedClassrooms()->with('owner')->latest('classroom_members.created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80']]);

        $classroom = $request->user()->ownedClassrooms()->create([
            'name' => trim($validated['name']),
            'join_code' => Classroom::newJoinCode(),
        ]);

        return redirect()->route('classrooms.show', $classroom)->with('status', 'Kelas dibuat. Bagikan kodenya ke mahasiswa.');
    }

    /**
     * The class at a glance: every student's latest band, change since the previous exam and weakest criterion.
     */
    public function show(Request $request, Classroom $classroom): View
    {
        $this->authorizeOwner($request, $classroom);

        $members = $classroom->members()->orderBy('name')->get()->map(fn (User $student) => $this->summary($student));

        $practised = $members->filter(fn (array $row) => $row['latest'] !== null);

        return view('classrooms.show', [
            'classroom' => $classroom,
            'members' => $members,
            'overview' => [
                'members' => $members->count(),
                'active_week' => $members->filter(fn (array $row) => $row['last_at']?->greaterThanOrEqualTo(now()->subDays(7)))->count(),
                'sessions' => $members->sum('sessions'),
                'average' => $practised->isEmpty() ? null : round($practised->avg('latest'), 1),
                'improving' => $practised->filter(fn (array $row) => ($row['delta'] ?? 0) > 0)->count(),
            ],
            'labels' => $members->reduce(fn (array $carry, array $row) => $carry + $row['labels'], []),
        ]);
    }

    /**
     * One student's progress and reports, for the mentor.
     */
    public function member(Request $request, Classroom $classroom, User $student): View
    {
        $this->authorizeOwner($request, $classroom);
        abort_unless($classroom->members()->whereKey($student->id)->exists(), 404);

        $sessions = $student->practiceSessions()
            ->whereNotNull('exam_mode')
            ->latest()
            ->take(30)
            ->get()
            ->filter(fn (PracticeSession $session) => $session->hasReport())
            ->values();

        return view('classrooms.member', [
            'classroom' => $classroom,
            'student' => $student,
            'summary' => $this->summary($student),
            'progress' => $this->progress->forUser($student),
            'sessions' => $sessions,
            'blueprint' => $this->blueprint,
        ]);
    }

    public function destroy(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorizeOwner($request, $classroom);
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('status', 'Kelas dihapus. Rapor mahasiswa tetap tersimpan di akun mereka.');
    }

    public function removeMember(Request $request, Classroom $classroom, User $student): RedirectResponse
    {
        $this->authorizeOwner($request, $classroom);
        $classroom->members()->detach($student->id);

        return redirect()->route('classrooms.show', $classroom)->with('status', $student->name.' dikeluarkan dari kelas.');
    }

    /**
     * Join page, usually opened from the class link or QR (the code is already filled in).
     */
    public function joinForm(Request $request, ?string $code = null): View
    {
        $classroom = $code ? Classroom::with('owner')->where('join_code', Classroom::normalizeCode($code))->first() : null;

        return view('classrooms.join', [
            'code' => $code ? Classroom::normalizeCode($code) : '',
            'classroom' => $classroom,
            'alreadyMember' => $classroom && $classroom->members()->whereKey($request->user()->id)->exists(),
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Centang persetujuan dulu: pembuat kelas akan bisa melihat rapormu.',
        ]);

        $classroom = Classroom::where('join_code', Classroom::normalizeCode($validated['code']))->first();
        if (! $classroom) {
            return back()->withInput()->withErrors(['code' => 'Kode kelas tidak ditemukan. Cek lagi ke dosenmu.']);
        }
        if ($classroom->owner_id === $request->user()->id) {
            return redirect()->route('classrooms.show', $classroom)->with('status', 'Ini kelasmu sendiri.');
        }

        $classroom->members()->syncWithoutDetaching([$request->user()->id]);

        return redirect()->route('classrooms.index')->with('status', 'Kamu bergabung ke kelas '.$classroom->name.'.');
    }

    public function leave(Request $request, Classroom $classroom): RedirectResponse
    {
        $classroom->members()->detach($request->user()->id);

        return redirect()->route('classrooms.index')->with('status', 'Kamu keluar dari kelas '.$classroom->name.'. Rapormu tidak lagi terlihat oleh pembuat kelas.');
    }

    /**
     * @return array{student: User, sessions: int, latest: ?float, delta: ?float, best: ?float, weakest: ?string, labels: array<string, string>, last_at: ?\Illuminate\Support\Carbon, last_url: ?string}
     */
    protected function summary(User $student): array
    {
        $progress = $this->progress->forUser($student);
        $finished = $student->practiceSessions()->whereNotNull('exam_mode')->latest()->get()
            ->filter(fn (PracticeSession $session) => isset($session->feedback_notes['exam_report']));
        $last = $finished->first();

        return [
            'student' => $student,
            'sessions' => $finished->count(),
            'latest' => $progress['latest'],
            'delta' => $progress['delta'],
            'best' => $progress['best'],
            'weakest' => $progress['weakest'],
            'labels' => $progress['labels'],
            'last_at' => $last?->created_at,
            'last_url' => $last ? route('exam.result', $last) : null,
        ];
    }

    protected function authorizeOwner(Request $request, Classroom $classroom): void
    {
        abort_unless($classroom->owner_id === $request->user()->id, 403);
    }
}
