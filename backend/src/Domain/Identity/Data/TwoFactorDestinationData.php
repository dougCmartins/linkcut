<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Spatie\LaravelData\Data;

/** Where a code can be sent, already masked ("ra****@linkcut.app"). */
final class TwoFactorDestinationData extends Data
{
    public function __construct(
        public readonly TwoFactorChannel $channel,
        public readonly string $masked,
    ) {}
}
