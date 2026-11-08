<?php

namespace App\Models;

use Database\Factories\PracticeSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

#[Fillable([
    'user_id',
    'ai_role_id',
    'scenario_type',
    'exam_mode',
    'duration_seconds',
    'face_score',
    'voice_score',
    'overall_score',
    'ai_conclusion',
    'feedback_notes',
    'exam_state',
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
            'exam_state' => 'array',
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
     * Determine if this session uses the three-part mock exam flow.
     */
    public function isExam(): bool
    {
        return $this->exam_mode !== null;
    }

    /**
     * URL of the report for this session: the band-score report for exams
     * (or the room if the exam is not finished yet), the legacy report otherwise.
     */
    public function reportUrl(): string
    {
        if ($this->isExam()) {
            return $this->hasReport()
                ? route('exam.result', $this)
                : route('exam.room', $this);
        }

        return $this->isGuest() ? route('demo.show', $this) : route('practice.show', $this);
    }

    /**
     * Signed, read-only link to the report that a phone can open without logging in,
     * using the booth's public address (VOIC_PUBLIC_URL) when set.
     */
    public function shareUrl(int $days = 7): string
    {
        $signed = URL::temporarySignedRoute('exam.shared', now()->addDays($days), ['practiceSession' => $this], absolute: false);

        return rtrim((string) (config('exam.public_url') ?: url('/')), '/').$signed;
    }

    /**
     * Whether a finished report exists (band-score exam or coaching notes).
     */
    public function hasReport(): bool
    {
        return isset($this->feedback_notes['exam_report']) || isset($this->feedback_notes['coach_report']);
    }

    /**
     * Whether this is a free coaching conversation rather than a scored exam.
     */
    public function isCoaching(): bool
    {
        return ($this->exam_state['session_type'] ?? null) === 'coach';
    }

    /**
     * Delete the session, its messages and any recorded answer audio.
     */
    public function deleteWithRecordings(): void
    {
        Storage::disk('local')->deleteDirectory($this->recordingsDirectory());
        $this->delete();
    }

    /**
     * Private storage folder holding this session's recorded answers.
     */
    public function recordingsDirectory(): string
    {
        return 'exam-recordings/'.$this->id;
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

    /**
     * Get unique cryptographic certificate serial number for this session.
     */
    public function getCertificateSerial(): string
    {
        return 'VOIC-CERT-'.strtoupper(substr(md5($this->id.$this->created_at), 0, 10));
    }
}
