<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Mark the user onboarding as completed and store initial focus preferences.
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'onboarding_goal' => ['nullable', 'string', 'in:sidang_skripsi,wawancara_kerja,pitching_startup,public_speaking'],
            'onboarding_experience' => ['nullable', 'string', 'in:pemula,menengah,mahir'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $goal = $validated['onboarding_goal'] ?? 'sidang_skripsi';
        $experience = $validated['onboarding_experience'] ?? 'pemula';

        $user->update([
            'has_completed_onboarding' => true,
            'onboarding_goal' => $goal,
            'onboarding_experience' => $experience,
        ]);

        $roleMap = [
            'sidang_skripsi' => 'dosen_penguji',
            'wawancara_kerja' => 'hrd',
            'pitching_startup' => 'investor',
            'public_speaking' => 'dosen_penguji',
        ];

        $recommendedRole = $roleMap[$goal] ?? 'dosen_penguji';

        return response()->json([
            'success' => true,
            'recommended_role' => $recommendedRole,
            'redirect_url' => route('practice.create', ['role' => $recommendedRole]),
        ]);
    }

    /**
     * Reset onboarding so the user can review the questionnaire and tutorial again.
     */
    public function reset(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'has_completed_onboarding' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tutorial panduan pemakaian dibuka kembali.',
        ]);
    }
}
