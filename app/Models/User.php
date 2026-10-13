<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'has_completed_onboarding', 'onboarding_goal', 'onboarding_experience', 'ai_learning_profile'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_completed_onboarding' => 'boolean',
            'ai_learning_profile' => 'array',
        ];
    }

    /**
     * Get the practice sessions for the user.
     *
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    /**
     * Get the structured AI deep learning profile for the user.
     *
     * @return array<string, mixed>
     */
    public function getAiLearningProfile(): array
    {
        $default = [
            'learning_level' => 1,
            'total_turns_learned' => 0,
            'adaptive_mastery_rate' => 70,
            'identified_weaknesses' => [],
            'mastered_topics' => [],
            'preferred_pace_wpm' => 120,
            'recent_coherence_scores' => [],
            'last_trained_at' => null,
        ];

        return array_merge($default, $this->ai_learning_profile ?? []);
    }
}
