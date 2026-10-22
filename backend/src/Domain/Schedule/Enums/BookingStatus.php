<?php

declare(strict_types=1);

namespace Domain\Schedule\Enums;

/**
 * pending → confirmed (accept) or rejected (reject); confirmed → completed (conclude, today only);
 * completed → confirmed (undo). Anything else is an invalid transition.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Completed = 'completed';
    /** The client cancelled from the client area: the time is free again. */
    case Cancelled = 'cancelled';

    /** The client can still cancel these (until the cutoff before the time). */
    public function isCancellableByClient(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }

    /** @return list<self> */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::Completed];
    }
}
