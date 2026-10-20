<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/** Address of the barbershop. Coordinates are null when the point on the map is unknown. */
final class AddressData extends Data
{
    public function __construct(
        public readonly string $zip_code,
        public readonly string $street,
        public readonly string $number,
        public readonly ?string $complement,
        public readonly string $district,
        public readonly string $city,
        public readonly string $state,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
    ) {}
}
