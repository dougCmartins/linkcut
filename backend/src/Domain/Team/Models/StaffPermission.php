<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Three boolean keys the owner toggles per barber. All default to false. */
final class StaffPermission extends Model
{
    protected $fillable = [
        'staff_id',
        'see_prices',
        'see_own_commission',
        'see_unit_agenda',
    ];

    protected function casts(): array
    {
        return [
            'see_prices' => 'boolean',
            'see_own_commission' => 'boolean',
            'see_unit_agenda' => 'boolean',
        ];
    }

    /** @return BelongsTo<Staff, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
