<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Domain\Identity\Enums\TwoFactorChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sign-in attempt between the password and the 2FA code.
 * The code is never stored: only its HMAC (code_hash).
 */
final class TwoFactorChallenge extends Model
{
    use HasUlids;
    use MassPrunable;

    protected $fillable = [
        'user_id', 'channel', 'code_hash', 'code_expires_at', 'expires_at',
        'attempts', 'sends', 'resend_available_at', 'consumed_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'channel' => TwoFactorChannel::class,
            'code_expires_at' => 'datetime',
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
            'sends' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return ! $this->consumed_at && $this->expires_at->isFuture();
    }

    public function isLocked(): bool
    {
        return $this->attempts >= (int) config('identity.two_factor.max_attempts');
    }

    /** Expired for more than a day: deleted by the daily "model:prune" (LGPD: kept only while useful). */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now()->subDay());
    }
}
