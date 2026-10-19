<?php

declare(strict_types=1);

namespace Domain\Settings\Models;

use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Booking rules: slot grid, how far ahead clients can book and the vacation lock (one row per tenant). */
final class BookingSettings extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'slot_interval_minutes', 'booking_window_days', 'vacation_enabled', 'vacation_starts_on', 'vacation_ends_on', 'vacation_message'];

    protected function casts(): array
    {
        return ['slot_interval_minutes' => 'integer', 'booking_window_days' => 'integer', 'vacation_enabled' => 'boolean', 'vacation_starts_on' => 'date:Y-m-d', 'vacation_ends_on' => 'date:Y-m-d'];
    }
}
