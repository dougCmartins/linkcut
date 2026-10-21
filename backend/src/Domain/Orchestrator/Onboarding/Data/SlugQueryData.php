<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Onboarding\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** GET /sign-up/slug?slug=… (same rules as the sign-up). */
final class SlugQueryData extends Data
{
    public function __construct(
        #[Required, StringType, Min(3), Max(40), Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public readonly string $slug,
    ) {}
}
