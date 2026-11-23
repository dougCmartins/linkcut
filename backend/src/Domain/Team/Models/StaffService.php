<?php

declare(strict_types=1);

namespace Domain\Team\Models;

use Illuminate\Database\Eloquent\Model;

/** A service this barber can perform. */
final class StaffService extends Model
{
    protected $table = 'staff_services';

    protected $fillable = ['staff_id', 'service_id'];

    protected function casts(): array
    {
        return [
            'staff_id' => 'integer',
            'service_id' => 'integer',
        ];
    }
}
