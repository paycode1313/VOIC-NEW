<?php

namespace App\Http\Controllers;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Services\AiRoleplayService;
use App\Services\CredentialVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DemoSessionController extends Controller
{
    /**
     * Show the Innofest demo/guest practice setup interface.
     */
    public function create(Request $request): View
    {
        $aiRoles = AiRole::where('is_active', true)->orderBy('id')->get();

        $roleQuery = $request->query('role');
        $matchedRole = $roleQuery ? $aiRoles->firstWhere('role_type', $roleQuery) : null;

        $defaultRole = $matchedRole ?? $aiRoles->first() ?? [
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
                'description' => 'Simulasi presentasi tugas akhir/skripsi bersama Dosen Penguji senior. Fokus pada ketenangan, metodologi, dan argumentasi ilmiah.',
                'prompt_guide' => 'Jelaskan latar belakang, rumusan masalah, serta kebaruan (novelty) dari penelitian Anda dalam waktu 2-3 menit.',
                'target_duration' => '120 detik',
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
                'target_duration' => '90 detik',
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
                'name' => 'Presentasi Kilat',
                'icon' => '🎙️',
                'badge' => 'Demo Booth',
                'badge_color' => 'emerald',
                'role_type' => 'dosen_penguji',
                'description' => 'Uji coba kilat berbicara 60-90 detik untuk pengunjung pameran Innofest 2026.',
                'prompt_guide' => 'Perkenalkan diri Anda dan jelaskan inovasi atau hal paling membanggakan yang pernah Anda ciptakan.',
                'target_duration' => '60 detik',
            ],
        ];

        return view('practice.create', [
            'aiRoles' => $aiRoles,
            'defaultRole' => $defaultRole,
            'scenarios' => $scenarios,
            'userLearningProfile' => null,
            'isDemo' => true,
            'defaultGuestName' => session('guest_name', 'Pengunjung Booth'),
            'defaultGuestInstitution' => session('guest_institution', ''),
        ]);
    }

    /**
     * Start a new guest demo session.
     */
    public function start(Request $request, AiRoleplayService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'ai_role_id' => ['required', 'exists:ai_roles,id'],
            'scenario_type' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'in:id,en'],
            'context_material' => ['nullable', 'string', 'max:3000'],
            'guest_name' => ['nullable', 'string', 'max:100'],
            'guest_institution' => ['nullable', 'string', 'max:150'],
        ]);

        /** @var AiRole $role */
        $role = AiRole::findOrFail($validated['ai_role_id']);

        $scenarioType = $validated['scenario_type'] ?? $role->name;
        $language = $validated['language'] ?? 'id';
        $contextMaterial = $validated['context_material'] ?? null;
        $guestName = ! empty($validated['guest_name']) ? trim($validated['guest_name']) : 'Pengunjung Booth';
        $guestInstitution = ! empty($validated['guest_institution']) ? trim($validated['guest_institution']) : null;

        $guestToken = Str::random(40);

        /** @var PracticeSession $session */
        $session = PracticeSession::create([
            'user_id' => null,
            'ai_role_id' => $role->id,
            'scenario_type' => $scenarioType,
            'duration_seconds' => 0,
            'overall_score' => 0,
            'feedback_notes' => [
                'language' => $language,
                'context_material' => $contextMaterial,
                'is_guest' => true,
                'guest_name' => $guestName,
                'guest_institution' => $guestInstitution,
                'guest_token' => $guestToken,
            ],
        ]);

        // Keep session identifiers in browser session
        $request->session()->put('active_guest_session_id', $session->id);
        $request->session()->put('guest_session_token', $guestToken);
        $request->session()->put('pending_claim_session_id', $session->id);
        $request->session()->put('guest_name', $guestName);
        if ($guestInstitution) {
            $request->session()->put('guest_institution', $guestInstitution);
        }

        $greeting = $aiService->getInitialGreeting($role, $scenarioType, $language, $contextMaterial);

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
            'language' => $language,
            'is_demo' => true,
            'guest_name' => $guestName,
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
     * Process an incoming turn for a guest demo session.
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
            'language' => ['nullable', 'string', 'in:id,en'],
        ]);

        $language = $validated['language'] ?? ($practiceSession->feedback_notes['language'] ?? 'id');

        $result = $aiService->processTurn(
            $practiceSession,
            $validated['message'],
            $validated['facial_status'] ?? [],
            (int) $validated['timestamp_seconds'],
            $language
        );

        return response()->json([
            'success' => true,
            'user_message' => $result['user_message'],
            'ai_message' => $result['ai_message'],
            'response_text' => $result['response_text'],
            'audio_url' => $result['audio_url'],
            'facial_critique' => $result['facial_critique'],
            'coherence_score' => $result['coherence_score'] ?? 86.0,
            'coherence_category' => $result['coherence_category'] ?? 'Relevan & Nyambung',
            'salient_entities' => $result['salient_entities'] ?? [],
            'ai_engine' => $result['ai_engine'] ?? 'persona_fallback',
            'stage' => $result['stage'] ?? 'core',
        ]);
    }

    /**
     * Finalize guest demo session evaluation.
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
        ]);

        $faceScore = (float) $validated['face_score'];
        $voiceScore = (float) $validated['voice_score'];
        $overallScore = (float) $validated['overall_score'];
        $existingFeedback = is_array($practiceSession->feedback_notes) ? $practiceSession->feedback_notes : [];
        $feedbackNotes = array_merge($existingFeedback, $validated['feedback_notes'] ?? []);
        $language = $feedbackNotes['language'] ?? 'id';

        $aiConclusion = $aiService->generateFinalConclusion(
            $practiceSession,
            $faceScore,
            $voiceScore,
            $overallScore,
            $feedbackNotes,
            $language
        );

        $practiceSession->update([
            'duration_seconds' => (int) $validated['duration_seconds'],
            'face_score' => $faceScore,
            'voice_score' => $voiceScore,
            'overall_score' => $overallScore,
            'ai_conclusion' => $aiConclusion,
            'feedback_notes' => $feedbackNotes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesi demo berhasil diselesaikan.',
            'redirect_url' => route('demo.show', $practiceSession),
        ]);
    }

    /**
     * Display demo scorecard for guest.
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
            'isDemo' => true,
        ]);
    }

    /**
     * Reset guest booth session state for the next visitor.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['active_guest_session_id', 'guest_session_token', 'guest_name', 'guest_institution']);

        return redirect()->route('demo.create')->with('status', 'Booth siap untuk pengunjung berikutnya!');
    }
}
