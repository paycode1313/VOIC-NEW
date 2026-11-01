<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use App\Services\AiRoleplayService;
use App\Services\CredentialVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PracticeSessionController extends Controller
{
    /**
     * List user's past practice sessions with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = $user->practiceSessions()
            ->with('aiRole')
            ->where('duration_seconds', '>', 0)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Start a new interactive AI roleplay session for mobile.
     */
    public function start(Request $request, AiRoleplayService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'ai_role_id' => ['required', 'exists:ai_roles,id'],
            'scenario_type' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'in:id,en'],
            'context_material' => ['nullable', 'string', 'max:3000'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var AiRole $role */
        $role = AiRole::findOrFail($validated['ai_role_id']);

        $scenarioType = $validated['scenario_type'] ?? $role->name;
        $language = $validated['language'] ?? 'id';
        $contextMaterial = $validated['context_material'] ?? null;

        /** @var PracticeSession $session */
        $session = $user->practiceSessions()->create([
            'ai_role_id' => $role->id,
            'scenario_type' => $scenarioType,
            'duration_seconds' => 0,
            'overall_score' => 0,
            'feedback_notes' => [
                'language' => $language,
                'context_material' => $contextMaterial,
            ],
        ]);

        $greeting = $aiService->getInitialGreeting($role, $scenarioType, $language, $contextMaterial);

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
            'language' => $language,
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
     * Process incoming turn from mobile (speech transcript + facial telemetry).
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
     * Finalize an interactive practice session, save evaluation scores, and return comprehensive report.
     */
    public function finish(Request $request, PracticeSession $practiceSession, AiRoleplayService $aiService, CredentialVerificationService $credentialService): JsonResponse
    {
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'face_score' => ['required', 'numeric', 'between:0,100'],
            'voice_score' => ['required', 'numeric', 'between:0,100'],
            'overall_score' => ['required', 'numeric', 'between:0,100'],
            'feedback_notes' => ['nullable', 'array'],
            'feedback_notes.language' => ['nullable', 'string', 'in:id,en'],
            'feedback_notes.context_material' => ['nullable', 'string', 'max:3000'],
            'feedback_notes.summary' => ['nullable', 'string', 'max:1000'],
            'feedback_notes.eye_contact_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.smile_rate' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.pace_wpm' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.pitch_dynamics_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.clarity_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.avg_volume' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.total_words' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_count' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_list' => ['nullable', 'array'],
            'feedback_notes.filler_words_list.*' => ['nullable', 'string', 'max:50'],
            'feedback_notes.timeline_events' => ['nullable', 'array'],
            'feedback_notes.badges' => ['nullable', 'array'],
            'feedback_notes.badges.*' => ['nullable', 'string', 'max:100'],
            'feedback_notes.strengths' => ['nullable', 'array'],
            'feedback_notes.strengths.*' => ['nullable', 'string', 'max:500'],
            'feedback_notes.improvements' => ['nullable', 'array'],
            'feedback_notes.improvements.*' => ['nullable', 'string', 'max:500'],
            'feedback_notes.detected_uptalking' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.awkward_pauses' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.current_stage' => ['nullable', 'string', 'max:50'],
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

        $verification = $credentialService->getVerificationPayload($practiceSession);

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil diselesaikan.',
            'session' => [
                'id' => $practiceSession->id,
                'scenario_type' => $practiceSession->scenario_type,
                'duration_seconds' => $practiceSession->duration_seconds,
                'face_score' => $practiceSession->face_score,
                'voice_score' => $practiceSession->voice_score,
                'overall_score' => $practiceSession->overall_score,
                'ai_conclusion' => $practiceSession->ai_conclusion,
                'feedback_notes' => $practiceSession->feedback_notes,
                'verification_url' => $verification['verification_url'] ?? route('practice.verify', $practiceSession),
                'certificate_qr_data' => $verification['qr_data'] ?? null,
            ],
        ]);
    }

    /**
     * Get detail of a specific practice session including all conversation turns.
     */
    public function show(PracticeSession $practiceSession, Request $request, CredentialVerificationService $credentialService): JsonResponse
    {
        Gate::authorize('view', $practiceSession);

        $practiceSession->load(['aiRole', 'messages']);
        $verification = $credentialService->getVerificationPayload($practiceSession);

        return response()->json([
            'success' => true,
            'session' => $practiceSession,
            'verification' => $verification,
        ]);
    }
}
