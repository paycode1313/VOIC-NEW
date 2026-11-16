<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One finished, scored exam of a user-trial participant (anonymous: code only).
 */
class StudyAttempt extends Model
{
    protected $fillable = [
        'code', 'practice_session_id', 'mode', 'language', 'overall_band', 'criteria',
        'answers', 'speaking_seconds', 'fillers_per_minute', 'average_wpm', 'eye_contact',
    ];

    protected function casts(): array
    {
        return [
            'overall_band' => 'float',
            'criteria' => 'array',
            'answers' => 'integer',
            'speaking_seconds' => 'integer',
            'fillers_per_minute' => 'float',
            'average_wpm' => 'integer',
            'eye_contact' => 'float',
        ];
    }

    public function practiceSession(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class);
    }
}
