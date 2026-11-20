<?php

declare(strict_types=1);

namespace Domain\Team\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/** "Adicionar barbeiro": who is invited (no limit of barbers for now). */
final class NewStaffData extends Data
{
    public function __construct(
        #[Required, StringType, Min(2), Max(60)]
        public readonly string $name,
        #[Required, StringType, Email, Max(120)]
        public readonly string $email,
    ) {}
}
