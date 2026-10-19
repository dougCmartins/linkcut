<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Spatie\LaravelData\Data;

/** A code was sent. Dates in ISO 8601. */
final class CodeDeliveryData extends Data
{
    public function __construct(
        public readonly TwoFactorChannel $channel,
        public readonly string $masked_destination,
        public readonly string $expires_at,
        public readonly string $resend_available_at,
    ) {}
}
