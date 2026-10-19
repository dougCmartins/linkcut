<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Opaque refresh token (only its SHA-256 is stored). Rotated on every use;
 * all tokens born from the same sign-in share a family_id, so a reused token
 * can revoke the whole family.
 */
final class RefreshToken extends Model
{
    protected $fillable = ['user_id', 'family_id', 'token_hash', 'expires_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** @param Builder<self> $query */
    public function scopeOfFamily(Builder $query, string $familyId): void
    {
        $query->where('family_id', $familyId);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at');
    }
}
