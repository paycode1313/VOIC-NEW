<?php

namespace App\Models;

use Database\Factories\PracticeSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'ai_role_id',
    'scenario_type',
    'duration_seconds',
    'face_score',
    'voice_score',
    'overall_score',
    'ai_conclusion',
    'feedback_notes',
])]
class PracticeSession extends Model
{
    /** @use HasFactory<PracticeSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'face_score' => 'float',
            'voice_score' => 'float',
            'overall_score' => 'float',
            'feedback_notes' => 'array',
        ];
    }

    /**
     * Get the user that owns the practice session.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI role associated with the practice session.
     *
     * @return BelongsTo<AiRole, $this>
     */
    public function aiRole(): BelongsTo
    {
        return $this->belongsTo(AiRole::class);
    }

    /**
     * Get the turn-by-turn conversation messages for this session.
     *
     * @return HasMany<SessionMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SessionMessage::class);
    }

    /**
     * Determine if this session is a guest/demo session without an authenticated user.
     */
    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Get the display name of the participant (user name or guest name).
     */
    public function getParticipantName(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        $notes = $this->feedback_notes ?? [];

        return $notes['guest_name'] ?? 'Pengunjung Booth';
    }

    /**
     * Get the institution/affiliation of the participant if specified.
     */
    public function getParticipantInstitution(): ?string
    {
        $notes = $this->feedback_notes ?? [];

        return $notes['guest_institution'] ?? null;
    }
}
