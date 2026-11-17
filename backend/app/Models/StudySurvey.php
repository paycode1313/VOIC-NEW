<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The short questionnaire a user-trial participant fills in after their third exam.
 */
class StudySurvey extends Model
{
    protected $fillable = ['code', 'practice_session_id', 'answers', 'comment'];

    protected function casts(): array
    {
        return ['answers' => 'array'];
    }
}
