<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

final class TenantData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $active,
        public readonly string $timezone,
        /** Finished the first-run wizard (WhatsApp, address, services). */
        public readonly bool $onboarded = true,
        /** The brand this unit belongs to (docs/adr/0002). */
        public readonly ?int $organization_id = null,
        /** The main unit of the brand (false for a branch). */
        public readonly bool $is_main = true,
    ) {}
}
