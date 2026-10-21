<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Data;

/** Several barbershops at once (batch reads, to avoid one query per barbershop). */
final class TenantIdsData extends Data
{
    /** @param list<int> $ids */
    public function __construct(public readonly array $ids) {}
}
