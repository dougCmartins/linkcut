<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Domain\Settings\Enums\SocialNetwork;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Empty url = the network is not shown. */
final class SocialLinkData extends Data
{
    public function __construct(
        #[Required, Enum(SocialNetwork::class)]
        public readonly SocialNetwork $network,
        #[Nullable, StringType, Max(255), StartsWith('https://')]
        public readonly ?string $url,
    ) {}
}
