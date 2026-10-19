<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** Name shown on the bio link of a new barbershop (the barber changes it later). */
final class DefaultSettingsData extends Data
{
    public function __construct(
        #[StringType, Max(40)]
        public readonly string $name = 'Minha barbearia',
    ) {}
}
