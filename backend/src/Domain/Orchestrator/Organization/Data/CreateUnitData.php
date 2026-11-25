<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Organization\Data;

use Domain\Settings\Data\AddressInputData;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CreateUnitData extends Data
{
    public function __construct(
        #[Required, StringType, Max(120)]
        public readonly string $name,
        #[Required, StringType, Min(3), Max(40), Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public readonly string $slug,
        public readonly AddressInputData $address,
        #[BooleanType]
        public readonly bool $copy_settings = false,
    ) {}
}
