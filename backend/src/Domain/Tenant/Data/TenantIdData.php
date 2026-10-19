<?php

declare(strict_types=1);

namespace Domain\Tenant\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class TenantIdData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $tenant_id,
    ) {}
}
