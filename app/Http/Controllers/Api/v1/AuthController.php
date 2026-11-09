<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and issue a personal access token for mobile.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $deviceName = $validated['device_name'] ?? 'VOIC-Mobile-Client';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_completed_onboarding' => (bool) $user->has_completed_onboarding,
                'onboarding_goal' => $user->onboarding_goal,
                'onboarding_experience' => $user->onboarding_experience,
                'ai_learning_profile' => $user->getAiLearningProfile(),
            ],
        ]);
    }

    /**
     * Register a new user account and return a personal access token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'has_completed_onboarding' => false,
        ]);

        event(new Registered($user));

        $deviceName = $validated['device_name'] ?? 'VOIC-Mobile-Client';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_completed_onboarding' => false,
            ],
        ], 201);
    }

    /**
     * Revoke the current access token upon mobile logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get the authenticated mobile user profile and stats summary.
     */
    public function user(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $totalSessions = $user->practiceSessions()->count();
        $completedSessions = $user->practiceSessions()->where('duration_seconds', '>', 0)->count();
        $avgScore = $completedSessions > 0
            ? round((float) $user->practiceSessions()->where('duration_seconds', '>', 0)->avg('overall_score'), 1)
            : 0;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'has_completed_onboarding' => (bool) $user->has_completed_onboarding,
                'onboarding_goal' => $user->onboarding_goal,
                'onboarding_experience' => $user->onboarding_experience,
                'ai_learning_profile' => $user->getAiLearningProfile(),
                'stats' => [
                    'total_sessions' => $totalSessions,
                    'completed_sessions' => $completedSessions,
                    'avg_score' => $avgScore,
                ],
            ],
        ]);
    }
}
