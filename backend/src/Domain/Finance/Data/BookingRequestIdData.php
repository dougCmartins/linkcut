<?php

declare(strict_types=1);

namespace Domain\Finance\Data;

use Spatie\LaravelData\Data;

final class BookingRequestIdData extends Data
{
    public function __construct(public readonly int $booking_request_id) {}
}
