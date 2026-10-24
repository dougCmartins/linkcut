<?php

declare(strict_types=1);

namespace Domain\Client\Models;

use Domain\Client\Enums\CodePurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** A 6-digit code sent by e-mail (sign-up or new password). One per client and purpose; a new one replaces it. */
final class ClientEmailCode extends Model
{
    use MassPrunable;

    protected $fillable = ['client_id', 'purpose', 'code_hash', 'attempts', 'expires_at', 'sent_at'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => CodePurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    /** Expired for more than a day: deleted by the daily "model:prune" (LGPD: kept only while useful). */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now()->subDay());
    }
}
