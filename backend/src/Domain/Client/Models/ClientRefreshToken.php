<?php

declare(strict_types=1);

namespace Domain\Client\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Opaque refresh token of a client session (only the SHA-256 is stored), rotated on every use. */
final class ClientRefreshToken extends Model
{
    protected $fillable = ['client_id', 'family_id', 'token_hash', 'expires_at', 'revoked_at'];

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
