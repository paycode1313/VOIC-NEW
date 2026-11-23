<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A class a mentor (lecturer, supervisor, career centre) creates so students can share their practice reports.
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $join_code
 */
class Classroom extends Model
{
    /** Letters and digits that cannot be mistaken for each other when read aloud or copied from a slide. */
    protected const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = ['owner_id', 'name', 'join_code'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_members')->withTimestamps();
    }

    /**
     * A fresh six-character join code that no other class uses.
     */
    public static function newJoinCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /**
     * Codes are typed by hand: ignore case, spaces and dashes.
     */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    public function joinUrl(): string
    {
        return route('classrooms.join', $this->join_code);
    }
}
