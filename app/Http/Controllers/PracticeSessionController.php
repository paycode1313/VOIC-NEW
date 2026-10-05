<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeSessionRequest;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use App\Services\AiRoleplayService;
use App\Services\CredentialVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PracticeSessionController extends Controller
{
    /**
     * Show the practice setup, AI role selection, and live AI camera session interface.
     */
    public function create(Request $request): View
    {
        $aiRoles = AiRole::where('is_active', true)->orderBy('id')->get();

        $defaultRole = $aiRoles->first() ?? [
            'id' => 1,
            'name' => 'VOIC-Dosen Penguji',
            'role_type' => 'dosen_penguji',
            'difficulty_level' => 'Sulit',
            'voice_id' => 'id-ID-ArdiNeural',
            'description' => 'Dosen Penguji Sidang Skripsi senior di bidang informatika.',
            'personality_traits' => ['kritis & berwibawa', 'fokus metodologi'],
        ];

        $scenarios = [
            [
                'id' => 'sidang-skripsi',
                'name' => 'Sidang Skripsi',
                'icon' => '🎓',
                'badge' => 'Akademik',
                'badge_color' => 'indigo',
                'role_type' => 'dosen_penguji',
                'description' => 'Latihan presentasi tugas akhir/skripsi bersama Dosen Penguji senior. Fokus pada ketenangan, metodologi, dan argumentasi ilmiah.',
                'prompt_guide' => 'Jelaskan latar belakang, rumusan masalah, serta kebaruan (novelty) dari penelitian Anda dalam waktu 2-3 menit.',
                'target_duration' => '180 detik',
            ],
            [
                'id' => 'wawancara-kerja',
                'name' => 'Wawancara Kerja',
                'icon' => '💼',
                'badge' => 'Karier',
                'badge_color' => 'amber',
                'role_type' => 'hrd',
                'description' => 'Simulasi menjawab pertanyaan interview bersama HRD Recruiter dengan metode STAR. Fokus pada ekspresi ramah, kontak mata, dan kejelasan jawaban.',
                'prompt_guide' => 'Ceritakan pengalaman nyata saat menghadapi tantangan besar dalam tim dan bagaimana Anda mengatasinya.',
                'target_duration' => '120 detik',
            ],
            [
                'id' => 'pitching-startup',
                'name' => 'Pitching Startup',
                'icon' => '🚀',
                'badge' => 'Bisnis',
                'badge_color' => 'purple',
                'role_type' => 'investor',
                'description' => 'Simulasi presentasi elevator pitch di hadapan Venture Capitalist/Angel Investor. Fokus pada problem-solution fit, bisnis model, dan daya tarik pasar.',
                'prompt_guide' => 'Sampaikan elevator pitch: masalah pasar, keunikan solusi produk Anda, dan model bisnis dalam 1-2 menit.',
                'target_duration' => '90 detik',
            ],
            [
                'id' => 'presentasi-umum',
                'name' => 'Presentasi Umum',
                'icon' => '🎙️',
                'badge' => 'Public Speaking',
                'badge_color' => 'emerald',
                'role_type' => 'dosen_penguji',
                'description' => 'Latihan berbicara di depan publik/seminar. Fokus pada transisi kalimat yang mengalir, senyum ramah, dan keterikatan audiens.',
                'prompt_guide' => 'Buka presentasi Anda dengan hook cerita menarik, lalu jelaskan poin inti materi yang ingin Anda sampaikan.',
                'target_duration' => '120 detik',
            ],
        ];

        return view('practice.create', [
            'aiRoles' => $aiRoles,
            'defaultRole' => $defaultRole,
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Start a new interactive AI roleplay session.
     */
    public function start(Request $request, AiRoleplayService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'ai_role_id' => ['required', 'exists:ai_roles,id'],
            'scenario_type' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var AiRole $role */
        $role = AiRole::findOrFail($validated['ai_role_id']);

        $scenarioType = $validated['scenario_type'] ?? $role->name;

        /** @var PracticeSession $session */
        $session = $user->practiceSessions()->create([
            'ai_role_id' => $role->id,
            'scenario_type' => $scenarioType,
            'duration_seconds' => 0,
            'overall_score' => 0,
        ]);

        $greeting = $aiService->getInitialGreeting($role);

        // Store opening turn from the AI character
        $initialMessage = $session->messages()->create([
            'sender' => 'ai',
            'message' => $greeting['message'],
            'audio_url' => $greeting['audio_url'],
            'facial_status' => null,
            'timestamp_seconds' => 0,
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'role_type' => $role->role_type,
                'avatar' => $role->avatar,
                'voice_id' => $role->voice_id,
            ],
            'initial_message' => [
                'id' => $initialMessage->id,
                'sender' => 'ai',
                'message' => $greeting['message'],
                'audio_url' => $greeting['audio_url'],
                'timestamp_seconds' => 0,
            ],
        ], 201);
    }

    /**
     * Process an incoming conversational turn (speech text + facial telemetry).
     */
    public function message(Request $request, PracticeSession $practiceSession, AiRoleplayService $aiService): JsonResponse
    {
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'facial_status' => ['nullable', 'array'],
            'facial_status.status' => ['nullable', 'string'],
            'facial_status.eye_contact_score' => ['nullable', 'numeric'],
            'facial_status.is_smiling' => ['nullable', 'boolean'],
            'facial_status.face_detected' => ['nullable', 'boolean'],
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $result = $aiService->processTurn(
            $practiceSession,
            $validated['message'],
            $validated['facial_status'] ?? [],
            (int) $validated['timestamp_seconds']
        );

        return response()->json([
            'success' => true,
            'user_message' => $result['user_message'],
            'ai_message' => $result['ai_message'],
            'response_text' => $result['response_text'],
            'audio_url' => $result['audio_url'],
            'facial_critique' => $result['facial_critique'],
        ]);
    }

    /**
     * Finalize an interactive practice session, save evaluation scores and generate AI conclusion.
     */
    public function finish(Request $request, PracticeSession $practiceSession, AiRoleplayService $aiService): JsonResponse
    {
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'face_score' => ['required', 'numeric', 'between:0,100'],
            'voice_score' => ['required', 'numeric', 'between:0,100'],
            'overall_score' => ['required', 'numeric', 'between:0,100'],
            'feedback_notes' => ['nullable', 'array'],
            'feedback_notes.summary' => ['nullable', 'string', 'max:1000'],
            'feedback_notes.eye_contact_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.smile_rate' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.pace_wpm' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.clarity_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.avg_volume' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.total_words' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_count' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_list' => ['nullable', 'array'],
            'feedback_notes.filler_words_list.*' => ['nullable', 'string', 'max:50'],
            'feedback_notes.strengths' => ['nullable', 'array'],
            'feedback_notes.strengths.*' => ['nullable', 'string', 'max:500'],
            'feedback_notes.improvements' => ['nullable', 'array'],
            'feedback_notes.improvements.*' => ['nullable', 'string', 'max:500'],
        ]);

        $faceScore = (float) $validated['face_score'];
        $voiceScore = (float) $validated['voice_score'];
        $overallScore = (float) $validated['overall_score'];

        $aiConclusion = $aiService->generateFinalConclusion(
            $practiceSession,
            $faceScore,
            $voiceScore,
            $overallScore,
            $validated['feedback_notes'] ?? []
        );

        $practiceSession->update([
            'duration_seconds' => (int) $validated['duration_seconds'],
            'face_score' => $faceScore,
            'voice_score' => $voiceScore,
            'overall_score' => $overallScore,
            'ai_conclusion' => $aiConclusion,
            'feedback_notes' => $validated['feedback_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil diselesaikan.',
            'redirect_url' => route('practice.show', $practiceSession),
        ]);
    }

    /**
     * Store a newly created practice session result in MySQL (legacy/direct submit).
     */
    public function store(StorePracticeSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $session = $user->practiceSessions()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sesi latihan berhasil disimpan.',
            'session_id' => $session->id,
            'redirect_url' => route('practice.show', $session),
        ], 201);
    }

    /**
     * Display detailed post-practice feedback and performance metrics.
     */
    public function show(PracticeSession $practiceSession, CredentialVerificationService $verificationService): View
    {
        Gate::authorize('view', $practiceSession);

        $practiceSession->load(['aiRole', 'messages']);
        $benchmarkData = $verificationService->getBenchmarkData($practiceSession);

        return view('practice.show', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'messages' => $practiceSession->messages,
            'benchmarkData' => $benchmarkData,
        ]);
    }

    /**
     * Public verification page for certificate authentication (scanned via QR code).
     */
    public function verify(PracticeSession $practiceSession, CredentialVerificationService $verificationService): View
    {
        $practiceSession->load(['aiRole', 'user']);
        $benchmarkData = $verificationService->getBenchmarkData($practiceSession);

        return view('practice.verify', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'user' => $practiceSession->user,
            'benchmarkData' => $benchmarkData,
        ]);
    }
}
