<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Domain\Team\Enums\StaffMoveKind;
use Domain\Team\Enums\StaffMoveStatus;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A barber of one unit (tenant). user_id is null until the invite is accepted. */
final class Staff extends Model
{
    use BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'avatar',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Bookable on a day: not a borrowed barber whose loan ends before it (docs/decisoes/0003). A subquery, so the
     * list of barbers stays one query.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBookableOn(Builder $query, string $day): void
    {
        $query->whereNotExists(fn ($loans) => $loans->selectRaw('1')->from('staff_moves')
            ->whereColumn('staff_moves.to_staff_id', 'staff.id')
            ->where('staff_moves.kind', StaffMoveKind::Loan->value)
            ->where('staff_moves.status', StaffMoveStatus::Active->value)
            ->where('staff_moves.ends_on', '<', $day));
    }

    /** @return HasOne<StaffPermission, $this> */
    public function permissions(): HasOne
    {
        return $this->hasOne(StaffPermission::class);
    }

    /** @return HasMany<StaffInvite, $this> */
    public function invites(): HasMany
    {
        return $this->hasMany(StaffInvite::class);
    }
}
