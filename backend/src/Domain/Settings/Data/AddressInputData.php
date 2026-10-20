<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Address in the "Link" tab (required: clients must see where the barbershop is).
 * Coordinates come only from "Estou na barbearia" (the device location); otherwise the API finds them.
 * Nullable fields have no default value on purpose: with a default, absent fields skip their rules
 * (e.g. "both coordinates together" would not run).
 */
final class AddressInputData extends Data
{
    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public function __construct(
        /** CEP, 8 digits (formatting is removed by the SPA). */
        #[Required, StringType, Regex('/^\d{8}$/')]
        public readonly string $zip_code,
        #[Required, StringType, Max(120)]
        public readonly string $street,
        #[Required, StringType, Max(10)]
        public readonly string $number,
        #[Required, StringType, Max(60)]
        public readonly string $district,
        #[Required, StringType, Max(60)]
        public readonly string $city,
        #[Required, StringType, In(self::STATES)]
        public readonly string $state,
        #[Nullable, StringType, Max(60)]
        public readonly ?string $complement,
        #[Nullable, Numeric, Between(-90, 90), RequiredWith('longitude')]
        public readonly ?float $latitude,
        #[Nullable, Numeric, Between(-180, 180), RequiredWith('latitude')]
        public readonly ?float $longitude,
    ) {}
}
