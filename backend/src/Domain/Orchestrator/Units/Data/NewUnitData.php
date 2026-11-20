<?php

declare(strict_types=1);

namespace Domain\Orchestrator\Units\Data;

use Domain\Settings\Data\AddressInputData;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "Nova filial": its name, its own link, its address and whether it starts with a copy of the main unit's settings. */
final class NewUnitData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(40)]
        public readonly string $name,
        /** linkcut.app/{slug}: the branch's own link. */
        #[Required, StringType, Min(3), Max(40), Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public readonly string $slug,
        #[Required]
        public readonly AddressInputData $address,
        #[BooleanType]
        public readonly bool $copy_settings = false,
    ) {}
}
