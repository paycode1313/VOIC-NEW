<?php

namespace App\Http\Controllers\Mobile\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'stats' => [
                    'total_sessions' => $user->practiceSessions()->count(),
                    'average_score' => round((float) $user->practiceSessions()->avg('overall_score'), 1) ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        if (isset($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Get user dashboard stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = $user->practiceSessions()->with('aiRole')->get();

        $totalSessions = $sessions->count();
        $averageScore = $totalSessions > 0 ? round((float) $sessions->avg('overall_score'), 1) : 0;
        $bestScore = $totalSessions > 0 ? round((float) $sessions->max('overall_score'), 1) : 0;
        $averageEyeScore = $totalSessions > 0 ? round((float) $sessions->whereNotNull('face_score')->avg('face_score'), 1) : 0;
        $averageVoiceScore = $totalSessions > 0 ? round((float) $sessions->whereNotNull('voice_score')->avg('voice_score'), 1) : 0;
        $totalDurationMinutes = (int) $sessions->sum('duration_seconds') / 60;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'average_score' => $averageScore,
                'best_score' => $bestScore,
                'average_eye_score' => $averageEyeScore,
                'average_voice_score' => $averageVoiceScore,
                'total_duration_minutes' => round($totalDurationMinutes, 1),
            ],
        ]);
    }
}
