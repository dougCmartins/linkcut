<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Spatie\LaravelData\Data;

/**
 * The starting settings of a new branch: its name and address, and optionally a one-time copy of the settings of
 * another unit of the same brand (the caller guarantees it is one).
 */
final class UnitSettingsData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly AddressInputData $address,
        public readonly ?int $copy_from_tenant_id = null,
    ) {}
}
