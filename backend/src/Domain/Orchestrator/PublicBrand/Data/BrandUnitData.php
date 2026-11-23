<?php

declare(strict_types=1);

namespace Domain\Orchestrator\PublicBrand\Data;

use Domain\Settings\Data\AddressData;
use Spatie\LaravelData\Data;

/** One active unit as listed on the public brand page. */
final class BrandUnitData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_main,
        public readonly ?AddressData $address,
        public readonly ?string $photo_url,
    ) {}
}
