<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Domain\Settings\Enums\ProfileLinkIcon;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** One button on the public page. The array order is the position. */
final class ProfileLinkData extends Data
{
    public function __construct(
        #[Required, StringType, Min(1), Max(60)]
        public readonly string $title,
        #[Required, StringType, Max(300), StartsWith('https://')]
        public readonly string $url,
        #[Nullable, Enum(ProfileLinkIcon::class)]
        public readonly ?ProfileLinkIcon $icon = null,
    ) {}
}
