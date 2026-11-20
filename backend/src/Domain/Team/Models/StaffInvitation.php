<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The e-mail invitation of a barber: only the hash of its token is kept; it works once and expires. */
final class StaffInvitation extends Model
{
    use BelongsToTenant;

    public const TTL_DAYS = 7;

    protected $fillable = ['tenant_id', 'staff_id', 'token_hash', 'expires_at', 'accepted_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Staff, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public static function hashOf(string $token): string
    {
        return hash('sha256', $token);
    }
}
