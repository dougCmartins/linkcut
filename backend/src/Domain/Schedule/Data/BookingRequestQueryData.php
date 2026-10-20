<?php

declare(strict_types=1);

namespace Domain\Schedule\Data;

use Domain\Schedule\Enums\BookingStatus;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Data;

/** GET /schedule/requests?status=pending (from today on, in date and time order). */
final class BookingRequestQueryData extends Data
{
    public function __construct(
        #[Enum(BookingStatus::class)]
        public readonly BookingStatus $status = BookingStatus::Pending,
    ) {}
}
