<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Opening hours of one weekday (0 = Sunday … 6 = Saturday). */
final class BusinessHour extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'weekday', 'is_open', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'is_open' => 'boolean'];
    }
}
