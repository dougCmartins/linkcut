<?php

declare(strict_types=1);

namespace Domain\Identity\Data;

use Domain\Identity\Enums\TwoFactorChannel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** Password accepted: the second step (2FA) starts with this challenge. */
final class LoginChallengeData extends Data
{
    /** @param DataCollection<int, TwoFactorDestinationData> $destinations */
    public function __construct(
        public readonly string $challenge_id,
        public readonly ?TwoFactorChannel $preferred_channel,
        #[DataCollectionOf(TwoFactorDestinationData::class)]
        public readonly DataCollection $destinations,
    ) {}
}
