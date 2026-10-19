<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Data;

final class SendTwoFactorCodeData extends Data
{
    public function __construct(
        #[Required, StringType, Ulid]
        public readonly string $challenge_id,
        #[Required, Enum(TwoFactorChannel::class)]
        public readonly TwoFactorChannel $channel,
    ) {}
}
