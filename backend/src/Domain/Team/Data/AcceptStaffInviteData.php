<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class AcceptStaffInviteData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(80)]
        public readonly string $name,
        #[Required, StringType, Min(8), Max(72)]
        public readonly string $password,
    ) {}
}
