<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Models\User;
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
