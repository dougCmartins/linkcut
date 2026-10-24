<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** The code that confirms the e-mail of a barber signing up (only its HMAC is stored). */
final class SignUpCode extends Model
{
    use MassPrunable;

    protected $fillable = ['email', 'code_hash', 'attempts', 'expires_at', 'sent_at'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'expires_at' => 'immutable_datetime', 'sent_at' => 'immutable_datetime'];
    }

    /** Expired for more than a day: deleted by the daily "model:prune" (LGPD: kept only while useful). */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now()->subDay());
    }
}
