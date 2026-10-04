<?php

namespace App\Models;

use Database\Factories\AiRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'role_type',
    'avatar',
    'description',
    'system_prompt',
    'personality_traits',
    'voice_id',
    'difficulty_level',
    'is_active',
])]
class AiRole extends Model
{
    /** @use HasFactory<AiRoleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personality_traits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the practice sessions conducted with this AI character.
     *
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }
}
