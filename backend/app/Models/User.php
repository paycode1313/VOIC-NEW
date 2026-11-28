<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'has_completed_onboarding', 'onboarding_goal', 'onboarding_experience', 'ai_learning_profile'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Deleting an account removes the recorded answers too: the database cascade only removes the rows,
     * the voice files live on disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->practiceSessions()->select('id')->each(
                fn (PracticeSession $session) => Storage::disk('local')->deleteDirectory($session->recordingsDirectory())
            );
        });
    }

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
     * Classes this user created as a mentor.
     *
     * @return HasMany<Classroom, $this>
     */
    public function ownedClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'owner_id');
    }

    /**
     * Classes this user joined as a student (their mentors can see their practice reports).
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function joinedClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_members')->withTimestamps();
    }

    /**
     * Whether this user mentors a class that the given student joined.
     */
    public function mentors(User $student): bool
    {
        return $this->id !== $student->id
            && $this->ownedClassrooms()->whereHas('members', fn ($query) => $query->whereKey($student->id))->exists();
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

    /**
     * Get the current consecutive daily practice streak.
     *
     * @return array{streak: int, has_practiced_today: bool, is_streak_at_risk: bool}
     */
    public function getStreakData(): array
    {
        $dates = $this->practiceSessions()
            ->latest('created_at')
            ->pluck('created_at')
            ->map(fn ($dt) => ($dt instanceof CarbonInterface ? $dt : Carbon::parse($dt))->format('Y-m-d'))
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return [
                'streak' => 0,
                'has_practiced_today' => false,
                'is_streak_at_risk' => false,
            ];
        }

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $hasPracticedToday = $dates->contains($today);
        $hasPracticedYesterday = $dates->contains($yesterday);

        // If user hasn't practiced today or yesterday, streak is reset to 0
        if (! $hasPracticedToday && ! $hasPracticedYesterday) {
            return [
                'streak' => 0,
                'has_practiced_today' => false,
                'is_streak_at_risk' => false,
            ];
        }

        $streak = 0;
        $checkDate = $hasPracticedToday ? now() : now()->subDay();

        foreach ($dates as $dateStr) {
            if ($dateStr === $checkDate->format('Y-m-d')) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        return [
            'streak' => max(1, $streak),
            'has_practiced_today' => $hasPracticedToday,
            'is_streak_at_risk' => ! $hasPracticedToday && $hasPracticedYesterday,
        ];
    }
}
