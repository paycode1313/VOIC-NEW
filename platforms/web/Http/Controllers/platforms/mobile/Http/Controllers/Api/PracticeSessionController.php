<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeSessionRequest;
use App\Models\PracticeSession;
use App\Services\AiRoleplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeSessionController extends Controller
{
    protected AiRoleplayService $aiService;

    public function __construct(AiRoleplayService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get list of user's practice sessions
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()->practiceSessions()
            ->with('aiRole')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Start new practice session
     */
    public function store(StorePracticeSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $session = $request->user()->practiceSessions()->create([
            'ai_role_id' => $validated['ai_role_id'],
            'language' => $validated['language'] ?? 'id',
            'difficulty' => $validated['difficulty'] ?? 'intermediate',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Practice session dimulai',
            'data' => $session->load('aiRole'),
        ]);
    }

    /**
     * Get session detail
     */
    public function show(PracticeSession $practiceSession, Request $request): JsonResponse
    {
        if ($practiceSession->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $practiceSession->load(['aiRole', 'messages']),
        ]);
    }

    /**
     * Send message in practice session
     */
    public function message(Request $request, PracticeSession $practiceSession): JsonResponse
    {
        if ($practiceSession->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        try {
            $response = $this->aiService->processMessage(
                $practiceSession,
                $validated['content']
            );

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finish practice session
     */
    public function finish(Request $request, PracticeSession $practiceSession): JsonResponse
    {
        if ($practiceSession->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'overall_score' => 'nullable|numeric|between:0,100',
            'face_score' => 'nullable|numeric|between:0,100',
            'voice_score' => 'nullable|numeric|between:0,100',
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        $practiceSession->update([
            'status' => 'completed',
            'overall_score' => $validated['overall_score'] ?? 0,
            'face_score' => $validated['face_score'] ?? 0,
            'voice_score' => $validated['voice_score'] ?? 0,
            'duration_seconds' => $validated['duration_seconds'] ?? 0,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Practice session selesai',
            'data' => $practiceSession,
        ]);
    }

    /**
     * Export practice sessions as CSV
     */
    public function export(Request $request): JsonResponse
    {
        $sessions = $request->user()->practiceSessions()
            ->with('aiRole')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'Export data ready',
        ]);
    }
}
