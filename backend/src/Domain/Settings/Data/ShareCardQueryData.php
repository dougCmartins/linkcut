<?php

declare(strict_types=1);

namespace Domain\Settings\Data;

use Domain\Settings\Enums\SharedPage;
use Spatie\LaravelData\Data;

final class ShareCardQueryData extends Data
{
    public function __construct(
        /** Already resolved to the current tenant by the tenant.slug middleware. */
        public readonly string $slug,
        public readonly SharedPage $page,
    ) {}
}
