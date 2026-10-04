<?php

namespace App\Models;

use Database\Factories\SessionMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'practice_session_id',
    'sender',
    'message',
    'audio_url',
    'facial_status',
    'timestamp_seconds',
])]
class SessionMessage extends Model
{
    /** @use HasFactory<SessionMessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'facial_status' => 'array',
            'timestamp_seconds' => 'integer',
        ];
    }

    /**
     * Get the practice session that owns the message.
     *
     * @return BelongsTo<PracticeSession, $this>
     */
    public function practiceSession(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class);
    }
}
