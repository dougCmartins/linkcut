<?php

declare(strict_types=1);

namespace Domain\Schedule\Models;

use Domain\Schedule\Enums\BookingStatus;
use Domain\Tenant\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A client's request for a time. `date` is a plain "Y-m-d" and the times "H:i", in the tenant's timezone. */
final class BookingRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_name', 'client_whatsapp', 'service_id', 'service_name', 'duration_minutes',
        'date', 'starts_at', 'ends_at', 'status', 'completed_at', 'client_distance_meters',
        'client_id', 'client_email', 'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'duration_minutes' => 'integer',
            'status' => BookingStatus::class,
            'completed_at' => 'immutable_datetime',
            'client_distance_meters' => 'integer',
            'client_id' => 'integer',
            'reminded_at' => 'immutable_datetime',
        ];
    }

    /**
     * Requests that hold their time: everything except rejected.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blocking());
    }

    /** @param Builder<self> $query */
    public function scopeChronological(Builder $query): void
    {
        $query->orderBy('date')->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Requests on `date` that overlap [startsAt, endsAt).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, string $date, string $startsAt, string $endsAt): void
    {
        $query->where('date', $date)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }
}
