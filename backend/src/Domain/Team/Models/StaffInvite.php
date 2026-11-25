<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One-time invite e-mail for a staff row. Only the HMAC of the token is stored. */
final class StaffInvite extends Model
{
    use MassPrunable;

    protected $fillable = [
        'staff_id',
        'email',
        'token_hash',
        'expires_at',
        'accepted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Staff, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Expired for more than a day: deleted by the daily model:prune (LGPD). */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now()->subDay());
    }
}
